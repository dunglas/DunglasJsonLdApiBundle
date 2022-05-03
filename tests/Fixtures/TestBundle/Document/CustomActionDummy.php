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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
#[ApiResource(operations: [new Get(), new Get(uriTemplate: 'custom_action_collection_dummies/{id}'), new Get(routeName: 'custom_normalization'), new Get(routeName: 'short_custom_normalization'), new GetCollection(), new GetCollection(uriTemplate: 'custom_action_collection_dummies'), new Post(routeName: 'custom_denormalization'), new GetCollection(routeName: 'short_custom_denormalization')])]
class CustomActionDummy
{
    /**
     * @var int
     *
     * @ODM\Id(strategy="INCREMENT", type="int")
     */
    private $id;
    /**
     * @var string
     *
     * @ODM\Field
     */
    private $foo = '';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFoo(): string
    {
        return $this->foo;
    }

    public function setFoo(string $foo)
    {
        $this->foo = $foo;
    }
}
