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

namespace ApiPlatform\Elasticsearch\State;

use ApiPlatform\Elasticsearch\Extension\RequestBodySearchCollectionExtensionInterface;
use ApiPlatform\Elasticsearch\Metadata\ElasticsearchDocument;
use ApiPlatform\Elasticsearch\Paginator;
use ApiPlatform\Elasticsearch\Util\ElasticsearchVersion;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Util\Inflector;
use Elasticsearch\Client;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @internal
 */
final class ElasticsearchCollectionProvider implements ProviderInterface
{
    /**
     * @param RequestBodySearchCollectionExtensionInterface[] $collectionExtensions
     */
    public function __construct(private readonly Client $client, private readonly DenormalizerInterface $denormalizer, private readonly Pagination $pagination, private readonly iterable $collectionExtensions = [])
    {
    }

    /**
     * {@inheritdoc}
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Paginator
    {
        $documentConfiguration = $operation->getPersistenceMeans();
        if (!$documentConfiguration instanceof ElasticsearchDocument) {
            throw new \LogicException(sprintf('Operation‘s persistence means must be instance of %s, but got %s', ElasticsearchDocument::class, get_debug_type($documentConfiguration)));
        }
        $resourceClass = $operation->getClass();
        $body = [];

        foreach ($this->collectionExtensions as $collectionExtension) {
            $body = $collectionExtension->applyToCollection($body, $resourceClass, $operation, $context);
        }

        if (!isset($body['query']) && !isset($body['aggs'])) {
            $body['query'] = ['match_all' => new \stdClass()];
        }

        $limit = $body['size'] ??= $this->pagination->getLimit($operation, $context);
        $offset = $body['from'] ??= $this->pagination->getOffset($operation, $context);

        $params = [
            'index' => $documentConfiguration->index ?? Inflector::tableize($operation->getShortName()),
            'body' => $body,
        ];

        if (isset($documentConfiguration->type) && ElasticsearchVersion::supportsMappingType()) {
            $params['type'] = $documentConfiguration->type;
        }

        $documents = $this->client->search($params);

        return new Paginator(
            $this->denormalizer,
            $documents,
            $resourceClass,
            $limit,
            $offset,
            $context
        );
    }
}
