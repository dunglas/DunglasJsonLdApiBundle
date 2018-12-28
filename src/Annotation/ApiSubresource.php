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

namespace ApiPlatform\Core\Annotation;

/**
 * Property annotation.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 *
 * @Annotation
 * @Target({"METHOD", "PROPERTY"})
 * @Attributes(
 *     @Attribute("maxDepth", type="int"),
 *     @Attribute("postEnabled", type="bool"),
 *     @Attribute("deleteEnabled", type="bool")
 * )
 */
final class ApiSubresource
{
    /**
     * @var int
     */
    public $maxDepth;

    /**
     * @var bool
     */
    public $postEnabled = true;

    /**
     * @var bool
     */
    public $deleteEnabled = true;
}
