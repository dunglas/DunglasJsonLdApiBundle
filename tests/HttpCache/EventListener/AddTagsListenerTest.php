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

namespace ApiPlatform\Core\Tests\HttpCache\EventListener;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\HttpCache\EventListener\AddTagsListener;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AddTagsListenerTest extends TestCase
{
    public function testDoNotSetHeaderWhenMethodNotCacheable()
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $request = new Request([], [], ['_resources' => ['/foo', '/bar'], '_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get']);
        $request->setMethod('PUT');

        $response = new Response();
        $response->setPublic();
        $response->setEtag('foo');

        $event = $this->prophesize(ResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();
        $event->getResponse()->willReturn($response)->shouldBeCalled();

        $factory = $this->prophesize(ResourceMetadataFactoryInterface::class);

        $listener = new AddTagsListener($iriConverterProphecy->reveal(), $factory->reveal());
        $listener->onKernelResponse($event->reveal());

        $this->assertFalse($response->headers->has('Cache-Tags'));
    }

    public function testDoNotSetHeaderWhenResponseNotCacheable()
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $request = new Request([], [], ['_resources' => ['/foo', '/bar'], '_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get']);

        $response = new Response();

        $event = $this->prophesize(ResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();
        $event->getResponse()->willReturn($response)->shouldBeCalled();

        $factory = $this->prophesize(ResourceMetadataFactoryInterface::class);

        $listener = new AddTagsListener($iriConverterProphecy->reveal(), $factory->reveal());
        $listener->onKernelResponse($event->reveal());

        $this->assertFalse($response->headers->has('Cache-Tags'));
    }

    public function testDoNotSetHeaderWhenNotAnApiOperation()
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $request = new Request([], [], ['_resources' => ['/foo', '/bar']]);

        $response = new Response();
        $response->setPublic();
        $response->setEtag('foo');

        $event = $this->prophesize(ResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();
        $event->getResponse()->willReturn($response)->shouldBeCalled();

        $factory = $this->prophesize(ResourceMetadataFactoryInterface::class);

        $listener = new AddTagsListener($iriConverterProphecy->reveal(), $factory->reveal());
        $listener->onKernelResponse($event->reveal());

        $this->assertFalse($response->headers->has('Cache-Tags'));
    }

    public function testFilterTagsByConfig()
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $request = new Request([], [], ['_resources' => [
            '/resource/1' => '/resource/1',
            '/resource/2' => '/resource/2',
            '/resource/3' => '/resource/3',
            '/resource/4' => '/resource/4',
            '/resource/5' => '/resource/5',
            '/subresource/1' => '/subresource/1',
            '/subresource/2' => '/subresource/2',
            '/subresource/3' => '/subresource/3',
            '/subresource/4' => '/subresource/4',
            '/subresource/5' => '/subresource/5',
        ], '_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get']);

        $response = new Response();
        $response->setPublic();
        $response->setEtag('foo');

        $event = $this->prophesize(FilterResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();
        $event->getResponse()->willReturn($response)->shouldBeCalled();

        $factory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $factory->create(Dummy::class)->willReturn(new ResourceMetadata(
            null,
            null,
            null,
            null,
            null,
            ['cache_headers' => ['cache_tags' => false]]))->shouldBeCalled();

        $listener = new AddTagsListener($iriConverterProphecy->reveal(), $factory->reveal());
        $listener->onKernelResponse($event->reveal());

        $this->assertSame(
            implode(',',
                [
                    '/resource/1',
                    '/resource/2',
                    '/resource/3',
                    '/resource/4',
                    '/resource/5',
                ]
            ),
            $response->headers->get('Cache-Tags')
        );
    }

    public function testDoNotSetHeaderWhenEmptyTagList()
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $request = new Request([], [], ['_resources' => [], '_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get']);

        $response = new Response();
        $response->setPublic();
        $response->setEtag('foo');

        $event = $this->prophesize(ResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();
        $event->getResponse()->willReturn($response)->shouldBeCalled();

        $factory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $factory->create(Dummy::class)->willReturn(new ResourceMetadata())->shouldBeCalled();

        $listener = new AddTagsListener($iriConverterProphecy->reveal(), $factory->reveal());
        $listener->onKernelResponse($event->reveal());

        $this->assertFalse($response->headers->has('Cache-Tags'));
    }

    public function testAddTags()
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $request = new Request([], [], ['_resources' => ['/foo', '/bar'], '_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get']);

        $response = new Response();
        $response->setPublic();
        $response->setEtag('foo');

        $event = $this->prophesize(ResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();
        $event->getResponse()->willReturn($response)->shouldBeCalled();

        $factory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $factory->create(Dummy::class)->willReturn(new ResourceMetadata())->shouldBeCalled();

        $listener = new AddTagsListener($iriConverterProphecy->reveal(), $factory->reveal());
        $listener->onKernelResponse($event->reveal());

        $this->assertSame('/foo,/bar', $response->headers->get('Cache-Tags'));
    }

    public function testAddCollectionIri()
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResourceClass(Dummy::class)->willReturn('/dummies')->shouldBeCalled();

        $request = new Request([], [], ['_resources' => ['/foo', '/bar'], '_api_resource_class' => Dummy::class, '_api_collection_operation_name' => 'get']);

        $response = new Response();
        $response->setPublic();
        $response->setEtag('foo');

        $event = $this->prophesize(ResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();
        $event->getResponse()->willReturn($response)->shouldBeCalled();

        $factory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $factory->create(Dummy::class)->willReturn(new ResourceMetadata())->shouldBeCalled();

        $listener = new AddTagsListener($iriConverterProphecy->reveal(), $factory->reveal());
        $listener->onKernelResponse($event->reveal());

        $this->assertSame('/foo,/bar,/dummies', $response->headers->get('Cache-Tags'));
    }

    public function testAddCollectionIriWhenCollectionIsEmpty()
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResourceClass(Dummy::class)->willReturn('/dummies')->shouldBeCalled();

        $request = new Request([], [], ['_resources' => [], '_api_resource_class' => Dummy::class, '_api_collection_operation_name' => 'get']);

        $response = new Response();
        $response->setPublic();
        $response->setEtag('foo');

        $event = $this->prophesize(ResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();
        $event->getResponse()->willReturn($response)->shouldBeCalled();

        $factory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $factory->create(Dummy::class)->willReturn(new ResourceMetadata())->shouldBeCalled();

        $listener = new AddTagsListener($iriConverterProphecy->reveal(), $factory->reveal());
        $listener->onKernelResponse($event->reveal());

        $this->assertSame('/dummies', $response->headers->get('Cache-Tags'));
    }

    public function testAddSubResourceCollectionIri()
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResourceClass(Dummy::class)->willReturn('/dummies')->shouldBeCalled();

        $request = new Request([], [], ['_resources' => ['/foo', '/bar'], '_api_resource_class' => Dummy::class, '_api_subresource_operation_name' => 'api_dummies_relatedDummies_get_subresource', '_api_subresource_context' => ['collection' => true]]);

        $response = new Response();
        $response->setPublic();
        $response->setEtag('foo');

        $event = $this->prophesize(ResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();
        $event->getResponse()->willReturn($response)->shouldBeCalled();

        $factory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $factory->create(Dummy::class)->willReturn(new ResourceMetadata())->shouldBeCalled();

        $listener = new AddTagsListener($iriConverterProphecy->reveal(), $factory->reveal());
        $listener->onKernelResponse($event->reveal());

        $this->assertSame('/foo,/bar,/dummies', $response->headers->get('Cache-Tags'));
    }
}
