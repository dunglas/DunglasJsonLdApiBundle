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

namespace ApiPlatform\Symfony\Tests\EventListener;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Symfony\EventListener\SerializeListener;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SerializeListenerTest extends TestCase
{
    public function testFetchOperation(): void
    {
        $controllerResult = new \stdClass();
        $processor = $this->createMock(ProcessorInterface::class);
        $processor->expects($this->once())->method('process')->willReturn(new Response());
        $metadata = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $metadata->expects($this->once())->method('create')->with('class')->willReturn(new ResourceMetadataCollection('class', [
            new ApiResource(operations: [
                'operation' => new Get(),
            ]),
        ]));

        $request = new Request([], [], ['_api_operation_name' => 'operation', '_api_resource_class' => 'class']);
        $listener = new SerializeListener($processor, $metadata);
        $listener->onKernelView(
            new ViewEvent(
                $this->createStub(HttpKernelInterface::class),
                $request,
                HttpKernelInterface::MAIN_REQUEST,
                $controllerResult
            )
        );
    }

    public function testCallProcessor(): void
    {
        $controllerResult = new \stdClass();
        $processor = $this->createMock(ProcessorInterface::class);
        $processor->expects($this->once())->method('process')->willReturn(new Response());
        $metadata = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $request = new Request([], [], ['_api_operation' => new Get(), '_api_operation_name' => 'operation', '_api_resource_class' => 'class']);
        $listener = new SerializeListener($processor, $metadata);
        $listener->onKernelView(
            new ViewEvent(
                $this->createStub(HttpKernelInterface::class),
                $request,
                HttpKernelInterface::MAIN_REQUEST,
                $controllerResult
            )
        );
    }

    public function testCallProcessorContext(): void
    {
        $operation = new Get(class: 'class');
        $controllerResult = new \stdClass();
        $uriVariables = ['id' => 3];
        $request = new Request([], [], ['_api_operation' => $operation, '_api_operation_name' => 'operation', '_api_resource_class' => 'class', '_api_uri_variables' => $uriVariables]);
        $processor = $this->createMock(ProcessorInterface::class);
        $processor->expects($this->once())->method('process')
            ->with($controllerResult, $operation->withSerialize(true), $uriVariables, ['request' => $request, 'uri_variables' => $uriVariables, 'resource_class' => 'class'])->willReturn(new Response());
        $metadata = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $listener = new SerializeListener($processor, $metadata);
        $listener->onKernelView(
            new ViewEvent(
                $this->createStub(HttpKernelInterface::class),
                $request,
                HttpKernelInterface::MAIN_REQUEST,
                $controllerResult
            )
        );
    }

    #[DataProvider('provideNonApiAttributes')]
    public function testNoCallProcessor(...$attributes): void
    {
        $controllerResult = new \stdClass();
        $processor = $this->createMock(ProcessorInterface::class);
        $processor->expects($this->never())->method('process')->willReturn(new Response());
        $metadata = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $metadata->method('create')->willReturn(new ResourceMetadataCollection('class'));
        $request = new Request([], [], $attributes);
        $listener = new SerializeListener($processor, $metadata);
        $listener->onKernelView(
            new ViewEvent(
                $this->createStub(HttpKernelInterface::class),
                $request,
                HttpKernelInterface::MAIN_REQUEST,
                $controllerResult
            )
        );
    }

    public static function provideNonApiAttributes(): array
    {
        return [
            ['_api_respond' => false, '_api_operation_name' => 'dummy'],
            [],
        ];
    }
}
