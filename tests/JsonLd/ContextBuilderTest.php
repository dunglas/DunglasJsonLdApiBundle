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

namespace ApiPlatform\Tests\JsonLd;

use ApiPlatform\JsonLd\ContextBuilder;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\OutputDto;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\PropertyInfo\Type;

/**
 * @author Markus Mächler <markus.maechler@bithost.ch>
 */
class ContextBuilderTest extends TestCase
{
    use ProphecyTrait;

    private string $entityClass;
    private ObjectProphecy $resourceNameCollectionFactoryProphecy;
    private ObjectProphecy $resourceMetadataCollectionFactoryProphecy;
    private ObjectProphecy $propertyNameCollectionFactoryProphecy;
    private ObjectProphecy $propertyMetadataFactoryProphecy;
    private ObjectProphecy $iriConverterProphecy;
    private ObjectProphecy $urlGeneratorProphecy;

    protected function setUp(): void
    {
        $this->entityClass = '\Dummy\DummyEntity';
        $this->resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $this->resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $this->propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $this->propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $this->iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $this->urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
    }

    public function testResourceContext(): void
    {
        $this->resourceMetadataCollectionFactoryProphecy->create($this->entityClass)->willReturn(new ResourceMetadataCollection('DummyEntity', [
            (new ApiResource())
                ->withShortName('DummyEntity')
                ->withOperations(new Operations(['get' => (new Get())->withShortName('DummyEntity')])),
        ]));
        $this->propertyNameCollectionFactoryProphecy->create($this->entityClass)->willReturn(new PropertyNameCollection(['dummyPropertyA']));
        $this->propertyMetadataFactoryProphecy->create($this->entityClass, 'dummyPropertyA', Argument::type('array'))->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('Dummy property A')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true));
        $this->urlGeneratorProphecy->generate('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABS_URL)->willReturn('');

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataCollectionFactoryProphecy->reveal(), $this->propertyNameCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal());

        $expected = [
            '@vocab' => '#',
            'hydra' => 'http://www.w3.org/ns/hydra/core#',
            'dummyPropertyA' => 'DummyEntity/dummyPropertyA',
        ];

        $this->assertEquals($expected, $contextBuilder->getResourceContext($this->entityClass));
    }

    public function testIriOnlyResourceContext(): void
    {
        $this->resourceMetadataCollectionFactoryProphecy->create($this->entityClass)->willReturn(new ResourceMetadataCollection('DummyEntity', [
            (new ApiResource())
                ->withShortName('DummyEntity')
                ->withOperations(new Operations(['get' => (new Get())->withShortName('DummyEntity')->withNormalizationContext(['iri_only' => true])])),
        ]));
        $this->propertyNameCollectionFactoryProphecy->create($this->entityClass)->willReturn(new PropertyNameCollection(['dummyPropertyA']));
        $this->propertyMetadataFactoryProphecy->create($this->entityClass, 'dummyPropertyA', Argument::type('array'))->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('Dummy property A')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true));
        $this->urlGeneratorProphecy->generate('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABS_URL)->willReturn('');

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataCollectionFactoryProphecy->reveal(), $this->propertyNameCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal());

        $expected = [
            '@vocab' => '#',
            'hydra' => 'http://www.w3.org/ns/hydra/core#',
            'hydra:member' => [
                '@type' => '@id',
            ],
        ];

        $this->assertEquals($expected, $contextBuilder->getResourceContext($this->entityClass));
    }

    public function testResourceContextWithJsonldContext(): void
    {
        $this->resourceMetadataCollectionFactoryProphecy->create($this->entityClass)->willReturn(new ResourceMetadataCollection('DummyEntity', [
            (new ApiResource())
                ->withShortName('DummyEntity')
                ->withOperations(new Operations(['get' => (new Get())->withShortName('DummyEntity')])),
        ]));
        $this->propertyNameCollectionFactoryProphecy->create($this->entityClass)->willReturn(new PropertyNameCollection(['dummyPropertyA']));
        $this->propertyMetadataFactoryProphecy->create($this->entityClass, 'dummyPropertyA', Argument::type('array'))->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('Dummy property A')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true)->withJsonldContext(['@type' => '@id', '@id' => 'customId', 'foo' => 'bar']));
        $this->urlGeneratorProphecy->generate('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABS_URL)->willReturn('');

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataCollectionFactoryProphecy->reveal(), $this->propertyNameCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal());

        $expected = [
            '@vocab' => '#',
            'hydra' => 'http://www.w3.org/ns/hydra/core#',
            'dummyPropertyA' => [
                '@type' => '@id',
                '@id' => 'customId',
                'foo' => 'bar',
            ],
        ];

        $this->assertEquals($expected, $contextBuilder->getResourceContext($this->entityClass));
    }

    public function testGetEntryPointContext(): void
    {
        $this->resourceMetadataCollectionFactoryProphecy->create('dummyPropertyA')->willReturn(new ResourceMetadataCollection('DummyEntity', [
            (new ApiResource())
                ->withShortName('DummyEntity')
                ->withOperations(new Operations(['get' => (new Get())->withShortName('DummyEntity')])),
        ]));
        $this->propertyNameCollectionFactoryProphecy->create($this->entityClass)->willReturn(new PropertyNameCollection(['dummyPropertyA']));
        $this->resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection(['dummyPropertyA']));
        $this->propertyMetadataFactoryProphecy->create($this->entityClass, 'dummyPropertyA', Argument::type('array'))->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('Dummy property A')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true)->withJsonldContext(['@type' => '@id', '@id' => 'customId', 'foo' => 'bar']));
        $this->urlGeneratorProphecy->generate('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABS_URL)->willReturn('');

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataCollectionFactoryProphecy->reveal(), $this->propertyNameCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal());

        $expected = [
            '@vocab' => '#',
            'hydra' => 'http://www.w3.org/ns/hydra/core#',
            'dummyEntity' => [
                '@type' => '@id',
                '@id' => 'Entrypoint/dummyEntity',
            ],
        ];

        $this->assertEquals($expected, $contextBuilder->getEntrypointContext());
    }

    public function testResourceContextWithReverse(): void
    {
        $this->resourceMetadataCollectionFactoryProphecy->create($this->entityClass)->willReturn(new ResourceMetadataCollection('DummyEntity', [
            (new ApiResource())
                ->withShortName('DummyEntity')
                ->withOperations(new Operations(['get' => (new Get())->withShortName('DummyEntity')])),
        ]));
        $this->propertyNameCollectionFactoryProphecy->create($this->entityClass)->willReturn(new PropertyNameCollection(['dummyPropertyA']));
        $this->propertyMetadataFactoryProphecy->create($this->entityClass, 'dummyPropertyA', Argument::type('array'))->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('Dummy property A')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true)->withJsonldContext(['@reverse' => 'parent']));
        $this->urlGeneratorProphecy->generate('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABS_URL)->willReturn('');

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataCollectionFactoryProphecy->reveal(), $this->propertyNameCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal());

        $expected = [
            '@vocab' => '#',
            'hydra' => 'http://www.w3.org/ns/hydra/core#',
            'dummyPropertyA' => [
                '@id' => 'DummyEntity/dummyPropertyA',
                '@reverse' => 'parent',
            ],
        ];

        $this->assertEquals($expected, $contextBuilder->getResourceContext($this->entityClass));
    }

    public function testAnonymousResourceContext(): void
    {
        $dummy = new Dummy();
        $this->propertyNameCollectionFactoryProphecy->create(Dummy::class)->willReturn(new PropertyNameCollection(['dummyPropertyA']));
        $this->propertyMetadataFactoryProphecy->create(Dummy::class, 'dummyPropertyA', Argument::type('array'))->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('Dummy property A')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true)->withGenId(true));
        $this->urlGeneratorProphecy->generate('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABS_URL)->willReturn('');
        $this->iriConverterProphecy->getIriFromResource($dummy)->willreturn('/.well-known/genid/1');

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataCollectionFactoryProphecy->reveal(), $this->propertyNameCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal(), $this->iriConverterProphecy->reveal());

        $context = $contextBuilder->getAnonymousResourceContext($dummy);
        $this->assertSame('Dummy', $context['@type']);
        $this->assertStringStartsWith('/.well-known/genid', $context['@id']);
        $this->assertEquals([
            '@vocab' => '#',
            'hydra' => 'http://www.w3.org/ns/hydra/core#',
            'dummyPropertyA' => 'Dummy/dummyPropertyA',
        ], $context['@context']);
    }

    public function testAnonymousResourceContextWithIri(): void
    {
        $output = new OutputDto();
        $this->propertyNameCollectionFactoryProphecy->create(OutputDto::class)->willReturn(new PropertyNameCollection(['dummyPropertyA']));
        $this->propertyMetadataFactoryProphecy->create(OutputDto::class, 'dummyPropertyA', Argument::type('array'))->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('Dummy property A')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true));
        $this->urlGeneratorProphecy->generate('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABS_URL)->willReturn('');

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataCollectionFactoryProphecy->reveal(), $this->propertyNameCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal());

        $expected = [
            '@context' => [
                '@vocab' => '#',
                'hydra' => 'http://www.w3.org/ns/hydra/core#',
                'dummyPropertyA' => 'OutputDto/dummyPropertyA',
            ],
            '@id' => '/dummies',
            '@type' => 'OutputDto',
        ];

        $this->assertEquals($expected, $contextBuilder->getAnonymousResourceContext($output, ['iri' => '/dummies', 'name' => 'Dummy']));
    }

    public function testAnonymousResourceContextWithApiResource(): void
    {
        $output = new OutputDto();
        $this->propertyNameCollectionFactoryProphecy->create(OutputDto::class)->willReturn(new PropertyNameCollection(['dummyPropertyA']));
        $this->propertyMetadataFactoryProphecy->create(OutputDto::class, 'dummyPropertyA', Argument::type('array'))->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('Dummy property A')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true));
        $this->urlGeneratorProphecy->generate('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABS_URL)->willReturn('');

        $this->resourceMetadataCollectionFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadataCollection('Dummy', [
            (new ApiResource())
                ->withShortName('Dummy')
                ->withOperations(new Operations(['get' => (new Get())->withShortName('Dummy')])),
        ]));

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataCollectionFactoryProphecy->reveal(), $this->propertyNameCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal());

        $expected = [
            '@context' => [
                '@vocab' => '#',
                'hydra' => 'http://www.w3.org/ns/hydra/core#',
                'dummyPropertyA' => 'OutputDto/dummyPropertyA',
            ],
            '@id' => '/dummies',
            '@type' => 'Dummy',
        ];

        $this->assertEquals($expected, $contextBuilder->getAnonymousResourceContext($output, ['iri' => '/dummies', 'name' => 'Dummy', 'api_resource' => new Dummy()]));
    }

    public function testAnonymousResourceContextWithApiResourceHavingContext(): void
    {
        $output = new OutputDto();
        $this->propertyNameCollectionFactoryProphecy->create(OutputDto::class)->willReturn(new PropertyNameCollection(['dummyPropertyA']));
        $this->propertyMetadataFactoryProphecy->create(OutputDto::class, 'dummyPropertyA', Argument::type('array'))->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('Dummy property A')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true));
        $this->urlGeneratorProphecy->generate('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABS_URL)->willReturn('');

        $this->resourceMetadataCollectionFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadataCollection('Dummy', [
            (new ApiResource())
                ->withShortName('Dummy')
                ->withOperations(new Operations(['get' => (new Get())->withShortName('Dummy')])),
        ]));

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataCollectionFactoryProphecy->reveal(), $this->propertyNameCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal());

        $expected = [
            '@id' => '/dummies',
            '@type' => 'Dummy',
        ];

        $this->assertEquals($expected, $contextBuilder->getAnonymousResourceContext($output, ['iri' => '/dummies', 'name' => 'Dummy', 'api_resource' => new Dummy(), 'has_context' => true]));
    }

    public function testResourceContextWithoutHydraPrefix(): void
    {
        $this->resourceMetadataCollectionFactoryProphecy->create($this->entityClass)->willReturn(new ResourceMetadataCollection('DummyEntity', [
            (new ApiResource())
                ->withShortName('DummyEntity')
                ->withOperations(new Operations(['get' => (new Get())->withShortName('DummyEntity')])),
        ]));
        $this->propertyNameCollectionFactoryProphecy->create($this->entityClass)->willReturn(new PropertyNameCollection(['dummyPropertyA']));
        $this->propertyMetadataFactoryProphecy->create($this->entityClass, 'dummyPropertyA', Argument::type('array'))->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('Dummy property A')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true));
        $this->urlGeneratorProphecy->generate('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABS_URL)->willReturn('');

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataCollectionFactoryProphecy->reveal(), $this->propertyNameCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal(), null, null, [ContextBuilder::HYDRA_CONTEXT_HAS_PREFIX => false]);

        $expected = [
            [
                '@context' => [
                    'hydra' => 'http://www.w3.org/ns/hydra/core#',
                    'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
                    'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
                    'xsd' => 'http://www.w3.org/2001/XMLSchema#',
                    'owl' => 'http://www.w3.org/2002/07/owl#',
                    'vs' => 'http://www.w3.org/2003/06/sw-vocab-status/ns#',
                    'dc' => 'http://purl.org/dc/terms/',
                    'cc' => 'http://creativecommons.org/ns#',
                    'schema' => 'http://schema.org/',
                    'apiDocumentation' => 'hydra:apiDocumentation',
                    'ApiDocumentation' => 'hydra:ApiDocumentation',
                    'title' => 'hydra:title',
                    'description' => 'hydra:description',
                    'entrypoint' => [
                        '@id' => 'hydra:entrypoint',
                        '@type' => '@id',
                    ],
                    'supportedClass' => [
                        '@id' => 'hydra:supportedClass',
                        '@type' => '@vocab',
                    ],
                    'Class' => 'hydra:Class',
                    'supportedProperty' => [
                        '@id' => 'hydra:supportedProperty',
                        '@type' => '@id',
                    ],
                    'SupportedProperty' => 'hydra:SupportedProperty',
                    'property' => [
                        '@id' => 'hydra:property',
                        '@type' => '@vocab',
                    ],
                    'required' => 'hydra:required',
                    'readable' => 'hydra:readable',
                    'writable' => 'hydra:writable',
                    'writeable' => 'hydra:writeable',
                    'supportedOperation' => [
                        '@id' => 'hydra:supportedOperation',
                        '@type' => '@id',
                    ],
                    'Operation' => 'hydra:Operation',
                    'method' => 'hydra:method',
                    'expects' => [
                        '@id' => 'hydra:expects',
                        '@type' => '@vocab',
                    ],
                    'returns' => [
                        '@id' => 'hydra:returns',
                        '@type' => '@vocab',
                    ],
                    'possibleStatus' => [
                        '@id' => 'hydra:possibleStatus',
                        '@type' => '@id',
                    ],
                    'Status' => 'hydra:Status',
                    'statusCode' => 'hydra:statusCode',
                    'Error' => 'hydra:Error',
                    'Resource' => 'hydra:Resource',
                    'operation' => 'hydra:operation',
                    'Collection' => 'hydra:Collection',
                    'collection' => 'hydra:collection',
                    'member' => [
                        '@id' => 'hydra:member',
                        '@type' => '@id',
                    ],
                    'memberAssertion' => 'hydra:memberAssertion',
                    'manages' => 'hydra:manages',
                    'subject' => [
                        '@id' => 'hydra:subject',
                        '@type' => '@vocab',
                    ],
                    'object' => [
                        '@id' => 'hydra:object',
                        '@type' => '@vocab',
                    ],
                    'search' => 'hydra:search',
                    'freetextQuery' => 'hydra:freetextQuery',
                    'view' => [
                        '@id' => 'hydra:view',
                        '@type' => '@id',
                    ],
                    'PartialCollectionView' => 'hydra:PartialCollectionView',
                    'totalItems' => 'hydra:totalItems',
                    'first' => [
                        '@id' => 'hydra:first',
                        '@type' => '@id',
                    ],
                    'last' => [
                        '@id' => 'hydra:last',
                        '@type' => '@id',
                    ],
                    'next' => [
                        '@id' => 'hydra:next',
                        '@type' => '@id',
                    ],
                    'previous' => [
                        '@id' => 'hydra:previous',
                        '@type' => '@id',
                    ],
                    'Link' => 'hydra:Link',
                    'TemplatedLink' => 'hydra:TemplatedLink',
                    'IriTemplate' => 'hydra:IriTemplate',
                    'template' => 'hydra:template',
                    'Rfc6570Template' => 'hydra:Rfc6570Template',
                    'variableRepresentation' => [
                        '@id' => 'hydra:variableRepresentation',
                        '@type' => '@vocab',
                    ],
                    'VariableRepresentation' => 'hydra:VariableRepresentation',
                    'BasicRepresentation' => 'hydra:BasicRepresentation',
                    'ExplicitRepresentation' => 'hydra:ExplicitRepresentation',
                    'mapping' => 'hydra:mapping',
                    'IriTemplateMapping' => 'hydra:IriTemplateMapping',
                    'variable' => 'hydra:variable',
                    'offset' => [
                        '@id' => 'hydra:offset',
                        '@type' => 'xsd:nonNegativeInteger',
                    ],
                    'limit' => [
                        '@id' => 'hydra:limit',
                        '@type' => 'xsd:nonNegativeInteger',
                    ],
                    'pageIndex' => [
                        '@id' => 'hydra:pageIndex',
                        '@type' => 'xsd:nonNegativeInteger',
                    ],
                    'pageReference' => [
                        '@id' => 'hydra:pageReference',
                    ],
                    'returnsHeader' => [
                        '@id' => 'hydra:returnsHeader',
                        '@type' => 'xsd:string',
                    ],
                    'expectsHeader' => [
                        '@id' => 'hydra:expectsHeader',
                        '@type' => 'xsd:string',
                    ],
                    'HeaderSpecification' => 'hydra:HeaderSpecification',
                    'headerName' => 'hydra:headerName',
                    'possibleValue' => 'hydra:possibleValue',
                    'closedSet' => [
                        '@id' => 'hydra:possibleValue',
                        '@type' => 'xsd:boolean',
                    ],
                    'name' => [
                        '@id' => 'hydra:name',
                        '@type' => 'xsd:string',
                    ],
                    'extension' => [
                        '@id' => 'hydra:extension',
                        '@type' => '@id',
                    ],
                    'isDefinedBy' => [
                        '@id' => 'rdfs:isDefinedBy',
                        '@type' => '@id',
                    ],
                    'defines' => [
                        '@reverse' => 'rdfs:isDefinedBy',
                    ],
                    'comment' => 'rdfs:comment',
                    'label' => 'rdfs:label',
                    'preferredPrefix' => 'http://purl.org/vocab/vann/preferredNamespacePrefix',
                    'cc:license' => [
                        '@type' => '@id',
                    ],
                    'cc:attributionURL' => [
                        '@type' => '@id',
                    ],
                    'domain' => [
                        '@id' => 'rdfs:domain',
                        '@type' => '@vocab',
                    ],
                    'range' => [
                        '@id' => 'rdfs:range',
                        '@type' => '@vocab',
                    ],
                    'subClassOf' => [
                        '@id' => 'rdfs:subClassOf',
                        '@type' => '@vocab',
                    ],
                    'subPropertyOf' => [
                        '@id' => 'rdfs:subPropertyOf',
                        '@type' => '@vocab',
                    ],
                    'seeAlso' => [
                        '@id' => 'rdfs:seeAlso',
                        '@type' => '@id',
                    ],
                    'domainIncludes' => [
                        '@id' => 'schema:domainIncludes',
                        '@type' => '@id',
                    ],
                    'rangeIncludes' => [
                        '@id' => 'schema:rangeIncludes',
                        '@type' => '@id',
                    ],
                ],
                '@id' => 'http://www.w3.org/ns/hydra/core',
                '@type' => 'owl:Ontology',
                'label' => 'The Hydra Core Vocabulary',
                'comment' => 'A lightweight vocabulary for hypermedia-driven Web APIs',
                'seeAlso' => 'https://www.hydra-cg.com/spec/latest/core/',
                'preferredPrefix' => 'hydra',
                'dc:description' => 'The Hydra Core Vocabulary is a lightweight vocabulary to create hypermedia-driven Web APIs. By specifying a number of concepts commonly used in Web APIs it enables the creation of generic API clients.',
                'dc:rights' => 'Copyright © 2012-2014 the Contributors to the Hydra Core Vocabulary Specification',
                'dc:publisher' => 'Hydra W3C Community Group',
                'cc:license' => 'http://creativecommons.org/licenses/by/4.0/',
                'cc:attributionName' => 'Hydra W3C Community Group',
                'cc:attributionURL' => 'http://www.hydra-cg.com/',
                'defines' => [
                    [
                        '@id' => 'hydra:Resource',
                        '@type' => 'hydra:Class',
                        'label' => 'Hydra Resource',
                        'comment' => 'The class of dereferenceable resources by means a client can attempt to dereference; however, the received responses should still be verified.',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:Class',
                        '@type' => [
                            'hydra:Resource',
                            'rdfs:Class',
                        ],
                        'subClassOf' => [
                            'rdfs:Class',
                        ],
                        'label' => 'Hydra Class',
                        'comment' => 'The class of Hydra classes.',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:Link',
                        '@type' => 'hydra:Class',
                        'subClassOf' => [
                            'hydra:Resource',
                            'rdf:Property',
                        ],
                        'label' => 'Link',
                        'comment' => 'The class of properties representing links.',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:apiDocumentation',
                        '@type' => 'hydra:Link',
                        'label' => 'apiDocumentation',
                        'comment' => 'A link to the API documentation',
                        'range' => 'hydra:ApiDocumentation',
                        'domain' => 'hydra:Resource',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:ApiDocumentation',
                        '@type' => 'hydra:Class',
                        'subClassOf' => 'hydra:Resource',
                        'label' => 'ApiDocumentation',
                        'comment' => 'The Hydra API documentation class',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:entrypoint',
                        '@type' => 'hydra:Link',
                        'label' => 'entrypoint',
                        'comment' => 'A link to main entry point of the Web API',
                        'domain' => 'hydra:ApiDocumentation',
                        'range' => 'hydra:Resource',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:supportedClass',
                        '@type' => 'hydra:Link',
                        'label' => 'supported classes',
                        'comment' => 'A class known to be supported by the Web API',
                        'domain' => 'hydra:ApiDocumentation',
                        'range' => 'rdfs:Class',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:possibleStatus',
                        '@type' => 'hydra:Link',
                        'label' => 'possible status',
                        'comment' => 'A status that might be returned by the Web API (other statuses should be expected and properly handled as well)',
                        'range' => 'hydra:Status',
                        'domainIncludes' => [
                            'hydra:ApiDocumentation',
                            'hydra:Operation',
                        ],
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:supportedProperty',
                        '@type' => 'hydra:Link',
                        'label' => 'supported properties',
                        'comment' => 'The properties known to be supported by a Hydra class',
                        'domain' => 'rdfs:Class',
                        'range' => 'hydra:SupportedProperty',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:SupportedProperty',
                        '@type' => 'hydra:Class',
                        'label' => 'Supported Property',
                        'comment' => 'A property known to be supported by a Hydra class.',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:property',
                        '@type' => 'rdf:Property',
                        'label' => 'property',
                        'comment' => 'A property',
                        'range' => 'rdf:Property',
                        'domainIncludes' => [
                            'hydra:SupportedProperty',
                            'hydra:IriTemplateMapping',
                        ],
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:required',
                        '@type' => 'rdf:Property',
                        'label' => 'required',
                        'comment' => 'True if the property is required, false otherwise.',
                        'range' => 'xsd:boolean',
                        'domainIncludes' => [
                            'hydra:SupportedProperty',
                            'hydra:IriTemplateMapping',
                        ],
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:readable',
                        '@type' => 'rdf:Property',
                        'label' => 'readable',
                        'comment' => "True if the client can retrieve the property's value, false otherwise.",
                        'domain' => 'hydra:SupportedProperty',
                        'range' => 'xsd:boolean',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:writable',
                        '@type' => 'rdf:Property',
                        'label' => 'writable',
                        'comment' => "True if the client can change the property's value, false otherwise.",
                        'domain' => 'hydra:SupportedProperty',
                        'range' => 'xsd:boolean',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:writeable',
                        'subPropertyOf' => 'hydra:writable',
                        'label' => 'writable',
                        'comment' => 'This property is left for compatibility purposes and hydra:writable should be used instead.',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'archaic',
                    ],
                    [
                        '@id' => 'hydra:supportedOperation',
                        '@type' => 'hydra:Link',
                        'label' => 'supported operation',
                        'comment' => 'An operation supported by instances of the specific Hydra class, or the target of the Hydra link, or IRI template.',
                        'range' => 'hydra:Operation',
                        'domainIncludes' => [
                            'rdfs:Class',
                            'hydra:Class',
                            'hydra:Link',
                            'hydra:TemplatedLink',
                            'hydra:SupportedProperty',
                        ],
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:operation',
                        '@type' => 'hydra:Link',
                        'label' => 'operation',
                        'comment' => 'An operation supported by the Hydra resource',
                        'domain' => 'hydra:Resource',
                        'range' => 'hydra:Operation',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:Operation',
                        '@type' => 'hydra:Class',
                        'label' => 'Operation',
                        'comment' => 'An operation.',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:method',
                        '@type' => 'rdf:Property',
                        'label' => 'method',
                        'comment' => 'The HTTP method.',
                        'domain' => 'hydra:Operation',
                        'range' => 'xsd:string',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:expects',
                        '@type' => 'hydra:Link',
                        'label' => 'expects',
                        'comment' => 'The information expected by the Web API.',
                        'domain' => 'hydra:Operation',
                        'rangeIncludes' => [
                            'rdfs:Resource',
                            'hydra:Resource',
                            'rdfs:Class',
                            'hydra:Class',
                        ],
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:returns',
                        '@type' => 'hydra:Link',
                        'label' => 'returns',
                        'comment' => 'The information returned by the Web API on success',
                        'domain' => 'hydra:Operation',
                        'rangeIncludes' => [
                            'rdfs:Resource',
                            'hydra:Resource',
                            'rdfs:Class',
                            'hydra:Class',
                        ],
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:Status',
                        '@type' => 'hydra:Class',
                        'label' => 'Status code description',
                        'comment' => 'Additional information about a status code that might be returned.',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:statusCode',
                        '@type' => 'rdf:Property',
                        'label' => 'status code',
                        'comment' => 'The HTTP status code. Please note it may happen this value will be different to actual status code received.',
                        'domain' => 'hydra:Status',
                        'range' => 'xsd:integer',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:title',
                        '@type' => 'rdf:Property',
                        'subPropertyOf' => 'rdfs:label',
                        'label' => 'title',
                        'comment' => 'A title, often used along with a description.',
                        'range' => 'xsd:string',
                        'domainIncludes' => [
                            'hydra:ApiDocumentation',
                            'hydra:Status',
                            'hydra:Class',
                            'hydra:SupportedProperty',
                            'hydra:Operation',
                            'hydra:Link',
                            'hydra:TemplatedLink',
                        ],
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:description',
                        '@type' => 'rdf:Property',
                        'subPropertyOf' => 'rdfs:comment',
                        'label' => 'description',
                        'comment' => 'A description.',
                        'range' => 'xsd:string',
                        'domainIncludes' => [
                            'hydra:ApiDocumentation',
                            'hydra:Status',
                            'hydra:Class',
                            'hydra:SupportedProperty',
                            'hydra:Operation',
                            'hydra:Link',
                            'hydra:TemplatedLink',
                        ],
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:Error',
                        '@type' => 'hydra:Class',
                        'subClassOf' => 'hydra:Status',
                        'label' => 'Error',
                        'comment' => 'A runtime error, used to report information beyond the returned status code.',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:Collection',
                        '@type' => 'hydra:Class',
                        'subClassOf' => 'hydra:Resource',
                        'label' => 'Collection',
                        'comment' => 'A collection holding references to a number of related resources.',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:collection',
                        '@type' => 'hydra:Link',
                        'label' => 'collection',
                        'comment' => 'Collections somehow related to this resource.',
                        'range' => 'hydra:Collection',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:memberAssertion',
                        'label' => 'member assertion',
                        'comment' => 'Semantics of each member provided by the collection.',
                        'domain' => 'hydra:Collection',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:manages',
                        'subPropertyOf' => 'hydra:memberAssertion',
                        'label' => 'manages',
                        'comment' => 'This predicate is left for compatibility purposes and hydra:memberAssertion should be used instead.',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'archaic',
                    ],
                    [
                        '@id' => 'hydra:subject',
                        'label' => 'subject',
                        'comment' => 'The subject.',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:object',
                        'label' => 'object',
                        'comment' => 'The object.',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:member',
                        '@type' => 'hydra:Link',
                        'label' => 'member',
                        'comment' => 'A member of the collection',
                        'domain' => 'hydra:Collection',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:view',
                        '@type' => 'hydra:Link',
                        'label' => 'view',
                        'comment' => 'A specific view of a resource.',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:PartialCollectionView',
                        '@type' => 'hydra:Class',
                        'subClassOf' => 'hydra:Resource',
                        'label' => 'PartialCollectionView',
                        'comment' => 'A PartialCollectionView describes a partial view of a Collection. Multiple PartialCollectionViews can be connected with the the next/previous properties to allow a client to retrieve all members of the collection.',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:totalItems',
                        '@type' => 'rdf:Property',
                        'label' => 'total items',
                        'comment' => 'The total number of items referenced by a collection.',
                        'domain' => 'hydra:Collection',
                        'range' => 'xsd:integer',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:first',
                        '@type' => 'hydra:Link',
                        'label' => 'first',
                        'comment' => 'The first resource of an interlinked set of resources.',
                        'domain' => 'hydra:Resource',
                        'range' => 'hydra:Resource',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:last',
                        '@type' => 'hydra:Link',
                        'label' => 'last',
                        'comment' => 'The last resource of an interlinked set of resources.',
                        'domain' => 'hydra:Resource',
                        'range' => 'hydra:Resource',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:next',
                        '@type' => 'hydra:Link',
                        'label' => 'next',
                        'comment' => 'The resource following the current instance in an interlinked set of resources.',
                        'domain' => 'hydra:Resource',
                        'range' => 'hydra:Resource',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:previous',
                        '@type' => 'hydra:Link',
                        'label' => 'previous',
                        'comment' => 'The resource preceding the current instance in an interlinked set of resources.',
                        'domain' => 'hydra:Resource',
                        'range' => 'hydra:Resource',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:search',
                        '@type' => 'hydra:TemplatedLink',
                        'label' => 'search',
                        'comment' => 'A IRI template that can be used to query a collection.',
                        'range' => 'hydra:IriTemplate',
                        'domain' => 'hydra:Resource',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:freetextQuery',
                        '@type' => 'rdf:Property',
                        'label' => 'freetext query',
                        'comment' => 'A property representing a freetext query.',
                        'range' => 'xsd:string',
                        'domain' => 'hydra:Resource',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:TemplatedLink',
                        '@type' => 'hydra:Class',
                        'subClassOf' => [
                            'hydra:Resource',
                            'rdf:Property',
                        ],
                        'label' => 'Templated Link',
                        'comment' => 'A templated link.',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:IriTemplate',
                        '@type' => 'hydra:Class',
                        'label' => 'IRI Template',
                        'comment' => 'The class of IRI templates.',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:template',
                        '@type' => 'rdf:Property',
                        'label' => 'template',
                        'comment' => "A templated string with placeholders. The literal's datatype indicates the template syntax; if not specified, hydra:Rfc6570Template is assumed.",
                        'seeAlso' => 'hydra:Rfc6570Template',
                        'domain' => 'hydra:IriTemplate',
                        'range' => 'hydra:Rfc6570Template',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:Rfc6570Template',
                        '@type' => 'rdfs:Datatype',
                        'label' => 'RFC6570 IRI template',
                        'comment' => 'An IRI template as defined by RFC6570.',
                        'seeAlso' => 'http://tools.ietf.org/html/rfc6570',
                        'range' => 'xsd:string',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:variableRepresentation',
                        '@type' => 'rdf:Property',
                        'label' => 'variable representation',
                        'comment' => 'The representation format to use when expanding the IRI template.',
                        'range' => 'hydra:VariableRepresentation',
                        'domain' => 'hydra:IriTemplateMapping',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:VariableRepresentation',
                        '@type' => 'hydra:Class',
                        'label' => 'VariableRepresentation',
                        'comment' => 'A representation specifies how to serialize variable values into strings.',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:BasicRepresentation',
                        '@type' => 'hydra:VariableRepresentation',
                        'label' => 'BasicRepresentation',
                        'comment' => 'A representation that serializes just the lexical form of a variable value, but omits language and type information.',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:ExplicitRepresentation',
                        '@type' => 'hydra:VariableRepresentation',
                        'label' => 'ExplicitRepresentation',
                        'comment' => 'A representation that serializes a variable value including its language and type information and thus differentiating between IRIs and literals.',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:mapping',
                        '@type' => 'rdf:Property',
                        'label' => 'mapping',
                        'comment' => 'A variable-to-property mapping of the IRI template.',
                        'domain' => 'hydra:IriTemplate',
                        'range' => 'hydra:IriTemplateMapping',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:IriTemplateMapping',
                        '@type' => 'hydra:Class',
                        'label' => 'IriTemplateMapping',
                        'comment' => 'A mapping from an IRI template variable to a property.',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:variable',
                        '@type' => 'rdf:Property',
                        'label' => 'variable',
                        'comment' => 'An IRI template variable',
                        'domain' => 'hydra:IriTemplateMapping',
                        'range' => 'xsd:string',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:resolveRelativeUsing',
                        '@type' => 'rdf:Property',
                        'label' => 'relative Uri resolution',
                        'domain' => 'hydra:IriTemplate',
                        'range' => 'hydra:BaseUriSource',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:BaseUriSource',
                        '@type' => 'hydra:Class',
                        'subClassOf' => 'hydra:Resource',
                        'label' => 'Base Uri source',
                        'comment' => 'Provides a base abstract for base Uri source for Iri template resolution.',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:Rfc3986',
                        '@type' => 'hydra:BaseUriSource',
                        'label' => 'RFC 3986 based',
                        'comment' => 'States that the base Uri should be established using RFC 3986 reference resolution algorithm specified in section 5.',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:LinkContext',
                        '@type' => 'hydra:BaseUriSource',
                        'label' => 'Link context',
                        'comment' => "States that the link's context IRI, as defined in RFC 5988, should be used as the base Uri",
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:offset',
                        '@type' => 'rdf:Property',
                        'label' => 'skip',
                        'comment' => 'Instructs to skip N elements of the set.',
                        'range' => 'xsd:nonNegativeInteger',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:limit',
                        '@type' => 'rdf:Property',
                        'label' => 'take',
                        'comment' => 'Instructs to limit set only to N elements.',
                        'range' => 'xsd:nonNegativeInteger',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:pageIndex',
                        '@type' => 'rdf:Property',
                        'subPropertyOf' => 'hydra:pageReference',
                        'label' => 'page index',
                        'comment' => 'Instructs to provide a specific page of the collection at a given index.',
                        'range' => 'xsd:nonNegativeInteger',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:pageReference',
                        '@type' => 'rdf:Property',
                        'label' => 'page reference',
                        'comment' => 'Instructs to provide a specific page reference of the collection.',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:returnsHeader',
                        '@type' => 'rdf:Property',
                        'label' => 'returns header',
                        'comment' => 'Name of the header returned by the operation.',
                        'domain' => 'hydra:Operation',
                        'rangeIncludes' => [
                            'xsd:string',
                            'hydra:HeaderSpecification',
                        ],
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:expectsHeader',
                        '@type' => 'rdf:Property',
                        'label' => 'expects header',
                        'comment' => 'Specification of the header expected by the operation.',
                        'domain' => 'hydra:Operation',
                        'rangeIncludes' => [
                            'xsd:string',
                            'hydra:HeaderSpecification',
                        ],
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:HeaderSpecification',
                        '@type' => 'rdfs:Class',
                        'subClassOf' => 'hydra:Resource',
                        'label' => 'Header specification',
                        'comment' => 'Specifies a possible either expected or returned header values',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:headerName',
                        '@type' => 'rdf:Property',
                        'label' => 'header name',
                        'comment' => 'Name of the header.',
                        'domain' => 'hydra:HeaderSpecification',
                        'range' => 'xsd:string',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:possibleValue',
                        '@type' => 'rdf:Property',
                        'label' => 'possible header value',
                        'comment' => 'Possible value of the header.',
                        'domain' => 'hydra:HeaderSpecification',
                        'range' => 'xsd:string',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:closedSet',
                        '@type' => 'rdf:Property',
                        'label' => 'closed set',
                        'comment' => 'Determines whether the provided set of header values is closed or not.',
                        'domain' => 'hydra:HeaderSpecification',
                        'range' => 'xsd:boolean',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                    [
                        '@id' => 'hydra:extension',
                        '@type' => 'rdf:Property',
                        'label' => 'extension',
                        'comment' => 'Hint on what kind of extensions are in use.',
                        'domain' => 'hydra:ApiDocumentation',
                        'isDefinedBy' => 'http://www.w3.org/ns/hydra/core',
                        'vs:term_status' => 'testing',
                    ],
                ],
            ],
            [
                '@vocab' => '#',
                'hydra' => 'http://www.w3.org/ns/hydra/core#',
                'dummyPropertyA' => 'DummyEntity/dummyPropertyA',
            ],
        ];

        $this->assertEquals($expected, $contextBuilder->getResourceContext($this->entityClass));
    }
}
