<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Hydra\Serializer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Hypermedia\ContextBuilderInterface;
use ApiPlatform\Core\JsonLd\Serializer\JsonLdContextTrait;
use ApiPlatform\Core\Serializer\ContextTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

/**
 * This normalizer handles collections.
 *
 * @author Kevin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
final class CollectionNormalizer implements NormalizerInterface, SerializerAwareInterface
{
    use ContextTrait;
    use JsonLdContextTrait;
    use SerializerAwareTrait;

    const FORMAT = 'jsonld';

    private $contextBuilder;
    private $resourceClassResolver;
    private $iriConverter;

    public function __construct(ContextBuilderInterface $contextBuilder, ResourceClassResolverInterface $resourceClassResolver, IriConverterInterface $iriConverter)
    {
        $this->contextBuilder = $contextBuilder;
        $this->resourceClassResolver = $resourceClassResolver;
        $this->iriConverter = $iriConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        if (self::FORMAT !== $format) {
            return false;
        }

        return is_array($data) || ($data instanceof \Traversable && $data instanceof \Countable);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if (!$this->serializer instanceof NormalizerInterface) {
            throw new RuntimeException('The serializer must implement the NormalizerInterface.');
        }

        if (isset($context['api_sub_level'])) {
            $data = [];
            foreach ($object as $index => $obj) {
                $data[$index] = $this->serializer->normalize($obj, $format, $context);
            }

            return $data;
        }

        $resourceClass = $this->resourceClassResolver->getResourceClass($object, $context['resource_class'] ?? null, true);
        $data = $this->addJsonLdContext($this->contextBuilder, $resourceClass, $context);
        $context = $this->initContext($resourceClass, $context);

        $data['@id'] = $this->iriConverter->getIriFromResourceClass($resourceClass);
        $data['@type'] = 'hydra:Collection';

        $data['hydra:member'] = [];
        foreach ($object as $obj) {
            $data['hydra:member'][] = $this->serializer->normalize($obj, $format, $context);
        }

        $data['hydra:totalItems'] = $object instanceof PaginatorInterface ? $object->getTotalItems() : count($object);

        return $data;
    }
}
