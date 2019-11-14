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

namespace ApiPlatform\Core\Tests\GraphQl\Resolver;

use ApiPlatform\Core\Api\IriFromItemConverterInterface;
use ApiPlatform\Core\GraphQl\Resolver\ResourceFieldResolver;
use ApiPlatform\Core\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Schema;
use PHPUnit\Framework\TestCase;

class ResourceFieldResolverTest extends TestCase
{
    public function testId()
    {
        $iriFromItemConverterProphecy = $this->prophesize(IriFromItemConverterInterface::class);
        $iriFromItemConverterProphecy->getItemIriFromResourceClass(Dummy::class, ['id' => 1])->willReturn('/dummies/1')->shouldBeCalled();

        $resolveInfo = new ResolveInfo('id', [], new ObjectType(['name' => '']), new ObjectType(['name' => '']), [], new Schema([]), [], null, null, []);

        $resolver = new ResourceFieldResolver($iriFromItemConverterProphecy->reveal());
        $this->assertEquals('/dummies/1', $resolver([ItemNormalizer::ITEM_RESOURCE_CLASS_KEY => Dummy::class, ItemNormalizer::ITEM_IDENTIFIERS_KEY => ['id' => 1]], [], [], $resolveInfo));
    }

    public function testOriginalId()
    {
        $iriFromItemConverterProphecy = $this->prophesize(IriFromItemConverterInterface::class);

        $resolveInfo = new ResolveInfo('_id', [], new ObjectType(['name' => '']), new ObjectType(['name' => '']), [], new Schema([]), [], null, null, []);

        $resolver = new ResourceFieldResolver($iriFromItemConverterProphecy->reveal());
        $this->assertEquals(1, $resolver(['id' => 1], [], [], $resolveInfo));
    }

    public function testDirectAccess()
    {
        $iriFromItemConverterProphecy = $this->prophesize(IriFromItemConverterInterface::class);

        $resolveInfo = new ResolveInfo('foo', [], new ObjectType(['name' => '']), new ObjectType(['name' => '']), [], new Schema([]), [], null, null, []);

        $resolver = new ResourceFieldResolver($iriFromItemConverterProphecy->reveal());
        $this->assertEquals('bar', $resolver(['foo' => 'bar'], [], [], $resolveInfo));
    }

    public function testNonResource()
    {
        $dummy = new Dummy();
        $iriFromItemConverterProphecy = $this->prophesize(IriFromItemConverterInterface::class);
        $iriFromItemConverterProphecy->getIriFromItem($dummy)->willReturn('/dummies/1')->shouldNotBeCalled();

        $resolveInfo = new ResolveInfo('id', [], new ObjectType(['name' => '']), new ObjectType(['name' => '']), [], new Schema([]), [], null, null, []);

        $resolver = new ResourceFieldResolver($iriFromItemConverterProphecy->reveal());
        $this->assertNull($resolver([], [], [], $resolveInfo));
    }
}
