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

namespace ApiPlatform\Metadata;

use ApiPlatform\Metadata\GraphQl\Operation as GraphQlOperation;

trait WithResourceTrait
{
    public function withResource(ApiResource $resource): self
    {
        return $this->copyFrom($resource);
    }

    /**
     * @param ApiResource|Operation|GraphQlOperation $resource
     *
     * @otdo merge me with AttributePropertyMetadataFactory::createMetadata
     */
    private function copyFrom($resource): self
    {
        $self = clone $this;
        foreach (get_class_methods($resource) as $method) {
            if (
                // TODO: remove these checks for deprecated methods in 3.0
                'getAttribute' !== $method &&
                'isChildInherited' !== $method &&
                'getSubresource' !== $method &&
                'getIri' !== $method &&
                'getAttributes' !== $method &&
                // end of deprecated methods

                method_exists($self, $method) &&
                preg_match('/^(?:get|is)(.*)/', $method, $matches) &&
                null === $self->{$method}() &&
                null !== $val = $resource->{$method}()
            ) {
                $self = $self->{"with{$matches[1]}"}($val);
            }
        }

        return $self;
    }
}
