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

namespace ApiPlatform\Core\Metadata\Extractor;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use Symfony\Component\Config\Util\XmlUtils;

/**
 * Extracts an array of metadata from a list of XML files.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class XmlExtractor extends AbstractExtractor
{
    const RESOURCE_SCHEMA = __DIR__.'/../schema/metadata.xsd';

    /**
     * {@inheritdoc}
     */
    protected function extractPath(string $path)
    {
        try {
            $xml = simplexml_import_dom(XmlUtils::loadFile($path, self::RESOURCE_SCHEMA));
        } catch (\InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        foreach ($xml->resource as $resource) {
            $resourceClass = (string) $resource['class'];

            $this->resources[$resourceClass] = [
                'shortName' => $this->phpize($resource, 'shortName', 'string'),
                'description' => $this->phpize($resource, 'description', 'string'),
                'iri' => $this->phpize($resource, 'iri', 'string'),
                'itemOperations' => $this->getOperations($resource, 'itemOperation'),
                'collectionOperations' => $this->getOperations($resource, 'collectionOperation'),
                'subresourceOperations' => $this->getOperations($resource, 'subresourceOperation'),
                'graphql' => $this->getOperations($resource, 'operation'),
                'attributes' => $this->getAttributes($resource, 'attribute') ?: null,
                'properties' => $this->getProperties($resource) ?: null,
                'routePrefix' => $this->phpize($resource, 'routePrefix', 'string'),
            ];
        }
    }

    /**
     * Returns the array containing configured operations. Returns NULL if there is no operation configuration.
     *
     * @return array|null
     */
    private function getOperations(\SimpleXMLElement $resource, string $operationType)
    {
        $graphql = 'operation' === $operationType;
        if (!$graphql && $legacyOperations = $this->getAttributes($resource, $operationType)) {
            @trigger_error(
                sprintf('Configuring "%1$s" tags without using a parent "%1$ss" tag is deprecated since API Platform 2.1 and will not be possible anymore in API Platform 3', $operationType),
                E_USER_DEPRECATED
            );

            return $legacyOperations;
        }

        $operationsParent = $graphql ? 'graphql' : "{$operationType}s";
        if (!isset($resource->$operationsParent)) {
            return null;
        }

        return $this->getAttributes($resource->$operationsParent, $operationType, true);
    }

    /**
     * Recursively transforms an attribute structure into an associative array.
     */
    private function getAttributes(\SimpleXMLElement $resource, string $elementName, bool $topLevel = false): array
    {
        $attributes = [];
        foreach ($resource->$elementName as $attribute) {
            $value = isset($attribute->attribute[0]) ? $this->getAttributes($attribute, 'attribute') : XmlUtils::phpize($attribute);
            // allow empty operations definition, like <collectionOperation name="post" />
            if ($topLevel && '' === $value) {
                $value = [];
            }
            if (isset($attribute['name'])) {
                $attributes[(string) $attribute['name']] = $value;
            } else {
                $attributes[] = $value;
            }
        }

        return $attributes;
    }

    /**
     * Gets metadata of a property.
     */
    private function getProperties(\SimpleXMLElement $resource): array
    {
        $properties = [];
        foreach ($resource->property as $property) {
            $properties[(string) $property['name']] = [
                'description' => $this->phpize($property, 'description', 'string'),
                'readable' => $this->phpize($property, 'readable', 'bool'),
                'writable' => $this->phpize($property, 'writable', 'bool'),
                'readableLink' => $this->phpize($property, 'readableLink', 'bool'),
                'writableLink' => $this->phpize($property, 'writableLink', 'bool'),
                'required' => $this->phpize($property, 'required', 'bool'),
                'identifier' => $this->phpize($property, 'identifier', 'bool'),
                'iri' => $this->phpize($property, 'iri', 'string'),
                'attributes' => $this->getAttributes($property, 'attribute'),
                'subresource' => $property->subresource ? [
                    'collection' => $this->phpize($property->subresource, 'collection', 'bool'),
                    'resourceClass' => $this->phpize($property->subresource, 'resourceClass', 'string'),
                ] : null,
            ];
        }

        return $properties;
    }

    /**
     * Transforms an XML attribute's value in a PHP value.
     *
     * @return bool|string|null
     */
    private function phpize(\SimpleXMLElement $array, string $key, string $type)
    {
        if (!isset($array[$key])) {
            return null;
        }

        switch ($type) {
            case 'string':
                return (string) $array[$key];
            case 'bool':
                return (bool) XmlUtils::phpize($array[$key]);
        }

        return null;
    }
}
