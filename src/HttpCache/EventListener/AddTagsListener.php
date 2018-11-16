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

namespace ApiPlatform\Core\HttpCache\EventListener;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Util\ClassInfoTrait;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Sets the list of resources' IRIs included in this response in the "Cache-Tags" HTTP header.
 *
 * The "Cache-Tags" is used because it is supported by CloudFlare.
 *
 * @see https://support.cloudflare.com/hc/en-us/articles/206596608-How-to-Purge-Cache-Using-Cache-Tags-Enterprise-only-
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @experimental
 */
final class AddTagsListener
{
    use ClassInfoTrait;
    private $iriConverter;
    /**
     * @var ResourceMetadataFactoryInterface
     */
    private $resourceMetadataFactory;

    public function __construct(IriConverterInterface $iriConverter, ResourceMetadataFactoryInterface $resourceMetadataFactory = null)
    {
        $this->iriConverter = $iriConverter;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * Adds the "Cache-Tags" header.
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if (
            !$request->isMethodCacheable()
            || !$response->isCacheable()
            || (!$attributes = RequestAttributesExtractor::extractAttributes($request))
        ) {
            return;
        }

        $resources = $request->attributes->get('_resources');
        $resourceClasses = $this->getAvailableResourceClasses($resources);
        if (isset($attributes['collection_operation_name']) || ($attributes['subresource_context']['collection'] ?? false)) {
            // Allows to purge collections
            $iri = $this->iriConverter->getIriFromResourceClass($attributes['resource_class']);
            $resources[$iri] = $iri;
        }

        if (!$resources) {
            return;
        }

        $resources = $this->removeDisabledResourcesFromCacheTags($resources, $resourceClasses);

        $response->headers->set('Cache-Tags', implode(',', $resources));
    }

    private function removeDisabledResourcesFromCacheTags($resources, $resourceClasses)
    {
        $resourceCacheHeadersPerResourceClass = [];
        foreach ($resourceClasses as $resourceClass) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            $resourceCacheHeadersPerResourceClass[$resourceClass] = $resourceMetadata->getAttribute('cache_headers', []);
        }

        $filteredResources = $resources;
        foreach ($resourceCacheHeadersPerResourceClass as $resourceClass => $attributes) {
            if (false === $attributes['tags'] ?? true) {
                $iri = $this->iriConverter->getIriFromResourceClass($resourceClass);
                $matches = preg_grep('/^\\'.$iri.'\/{0,1}.*/', $filteredResources);
                $filteredResources = array_diff($filteredResources, $matches);
            }
        }

        return $filteredResources;
    }

    private function getAvailableResourceClasses(array $resources)
    {
        $resourceClasses = [];
        foreach ($resources as $resource) {
            $resourceClasses[] = $this->getObjectClass($this->iriConverter->getItemFromIri($resource));
        }

        return array_unique($resourceClasses);
    }
}
