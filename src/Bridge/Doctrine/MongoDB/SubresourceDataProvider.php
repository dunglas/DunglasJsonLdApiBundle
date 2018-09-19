<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Core\Bridge\Doctrine\MongoDB;

use ApiPlatform\Core\Bridge\Doctrine\MongoDB\Extension\AggregationCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\MongoDB\Extension\AggregationItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\MongoDB\Extension\AggregationResultCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\MongoDB\Extension\AggregationResultItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\IdentifierManagerTrait;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Identifier\IdentifierConverterInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * Subresource data provider for the Doctrine MongoDB ODM.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class SubresourceDataProvider implements SubresourceDataProviderInterface
{
    use IdentifierManagerTrait;

    private $managerRegistry;
    private $collectionExtensions;
    private $itemExtensions;

    /**
     * @param AggregationCollectionExtensionInterface[] $collectionExtensions
     * @param AggregationItemExtensionInterface[]       $itemExtensions
     */
    public function __construct(ManagerRegistry $managerRegistry, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, iterable $collectionExtensions = [], iterable $itemExtensions = [])
    {
        $this->managerRegistry = $managerRegistry;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->collectionExtensions = $collectionExtensions;
        $this->itemExtensions = $itemExtensions;
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    public function getSubresource(string $resourceClass, array $identifiers, array $context, string $operationName = null)
    {
        $manager = $this->managerRegistry->getManagerForClass($resourceClass);
        if (null === $manager) {
            throw new ResourceClassNotSupportedException(sprintf('The object manager associated with the "%s" resource class cannot be retrieved.', $resourceClass));
        }

        $repository = $manager->getRepository($resourceClass);
        if (!method_exists($repository, 'createAggregationBuilder')) {
            throw new RuntimeException('The repository class must have a "createAggregationBuilder" method.');
        }

        if (!isset($context['identifiers'], $context['property'])) {
            throw new ResourceClassNotSupportedException('The given resource class is not a subresource.');
        }

        $aggregationBuilder = $this->buildAggregation($identifiers, $context, $repository->createAggregationBuilder(), \count($context['identifiers']));

        if (true === $context['collection']) {
            foreach ($this->collectionExtensions as $extension) {
                $extension->applyToCollection($aggregationBuilder, $resourceClass, $operationName, $context);
                if ($extension instanceof AggregationResultCollectionExtensionInterface && $extension->supportsResult($resourceClass, $operationName, $context)) {
                    return $extension->getResult($aggregationBuilder, $resourceClass, $operationName, $context);
                }
            }
        } else {
            foreach ($this->itemExtensions as $extension) {
                $extension->applyToItem($aggregationBuilder, $resourceClass, $identifiers, $operationName, $context);
                if ($extension instanceof AggregationResultItemExtensionInterface && $extension->supportsResult($resourceClass, $operationName, $context)) {
                    return $extension->getResult($aggregationBuilder, $resourceClass, $operationName, $context);
                }
            }
        }

        $iterator = $aggregationBuilder->hydrate($resourceClass)->execute();

        return $context['collection'] ? $iterator->toArray() : $iterator->getSingleResult();
    }

    /**
     * @throws RuntimeException
     */
    private function buildAggregation(array $identifiers, array $context, Builder $previousAggregationBuilder, int $remainingIdentifiers, Builder $topAggregationBuilder = null): Builder
    {
        if ($remainingIdentifiers <= 0) {
            return $previousAggregationBuilder;
        }

        $topAggregationBuilder = $topAggregationBuilder ?? $previousAggregationBuilder;

        list($identifier, $identifierResourceClass) = $context['identifiers'][$remainingIdentifiers - 1];
        $previousAssociationProperty = $context['identifiers'][$remainingIdentifiers][0] ?? $context['property'];

        $manager = $this->managerRegistry->getManagerForClass($identifierResourceClass);

        if (!$manager instanceof DocumentManager) {
            throw new RuntimeException("The manager for $identifierResourceClass must be a DocumentManager.");
        }

        $classMetadata = $manager->getClassMetadata($identifierResourceClass);

        if (!$classMetadata instanceof ClassMetadata) {
            throw new RuntimeException(
                "The class metadata for $identifierResourceClass must be an instance of ClassMetadata."
            );
        }

        $aggregation = $manager->createAggregationBuilder($identifierResourceClass);
        $normalizedIdentifiers = [];

        if (isset($identifiers[$identifier])) {
            // if it's an array it's already normalized, the IdentifierManagerTrait is deprecated
            if ($context[IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER] ?? false) {
                $normalizedIdentifiers = $identifiers[$identifier];
            } else {
                $normalizedIdentifiers = $this->normalizeIdentifiers($identifiers[$identifier], $manager, $identifierResourceClass);
            }
        }

        if ($classMetadata->hasAssociation($previousAssociationProperty)) {
            $aggregation->lookup($previousAssociationProperty)->alias($previousAssociationProperty);
            foreach ($normalizedIdentifiers as $key => $value) {
                $aggregation->match()->field($key)->equals($value);
            }
        } elseif ($classMetadata->isIdentifier($previousAssociationProperty)) {
            foreach ($normalizedIdentifiers as $key => $value) {
                $aggregation->match()->field($key)->equals($value);
            }

            return $aggregation;
        }

        // Recurse aggregations
        $aggregation = $this->buildAggregation($identifiers, $context, $aggregation, --$remainingIdentifiers, $topAggregationBuilder);

        $results = $aggregation->execute()->toArray();
        $in = array_reduce($results, function ($in, $result) use ($previousAssociationProperty) {
            return $in + array_map(function ($result) {
                return $result['_id'];
            }, $result[$previousAssociationProperty] ?? []);
        }, []);
        $previousAggregationBuilder->match()->field('_id')->in($in);

        return $previousAggregationBuilder;
    }
}
