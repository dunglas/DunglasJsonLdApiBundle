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

namespace ApiPlatform\Doctrine\Common\State;

use ApiPlatform\Metadata\Operation;
use Psr\Container\ContainerInterface;

/**
 * Maybe merge this and LinksHandlerLocatorTrait into a OptionsHooksLocatorTrait or something similar?
 */
trait ResourceTransformerLocatorTrait
{
    private ?ContainerInterface $resourceTransformerLocator;

    protected function getToResourceTransformer(Operation $operation): ?callable
    {
        if (!($options = $operation->getStateOptions()) || !$options instanceof Options) {
            return null;
        }

        $transformer = $options->getToResourceTransformer();
        if (\is_callable($transformer)) {
            return $transformer;
        }

        if ($this->resourceTransformerLocator && \is_string($transformer) && $this->resourceTransformerLocator->has($transformer)) {
            return [$this->resourceTransformerLocator->get($transformer), 'toResource'];
        }

        return null;
    }

    protected function getFromResourceTransformer(Operation $operation): ?callable
    {
        if (!($options = $operation->getStateOptions()) || !$options instanceof Options) {
            return null;
        }

        $transformer = $options->getFromResourceTransformer();
        if (\is_callable($transformer)) {
            return $transformer;
        }

        if ($this->resourceTransformerLocator && \is_string($transformer) && $this->resourceTransformerLocator->has($transformer)) {
            return [$this->resourceTransformerLocator->get($transformer), 'fromResource'];
        }

        return null;
    }
}
