<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Doctrine\Orm\Filter;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Test\DoctrineTestHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @author Julien Deniau <julien.deniau@mapado.com>
 * @author Vincent CHALAMON <vincentchalamon@gmail.com>
 */
class SearchFilterTest extends KernelTestCase
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var EntityRepository
     */
    private $repository;

    /**
     * @var string
     */
    protected $resourceClass;

    /**
     * @var IriConverterInterface
     */
    protected $iriConverter;

    /**
     * @var PropertyAccessorInterface
     */
    protected $propertyAccessor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        self::bootKernel();
        $manager = DoctrineTestHelper::createTestEntityManager();
        $this->managerRegistry = self::$kernel->getContainer()->get('doctrine');
        $this->iriConverter = self::$kernel->getContainer()->get('api_platform.iri_converter');
        $this->propertyAccessor = self::$kernel->getContainer()->get('property_accessor');
        $this->repository = $manager->getRepository(Dummy::class);
        $this->resourceClass = Dummy::class;
    }

    /**
     * @dataProvider provideApplyTestData
     */
    public function testApply($properties, array $filterParameters, array $expected)
    {
        $request = Request::create('/api/dummies', 'GET', $filterParameters);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $queryBuilder = $this->repository->createQueryBuilder('o');

        $filter = new SearchFilter(
            $this->managerRegistry,
            $requestStack,
            $this->iriConverter,
            $this->propertyAccessor,
            null,
            $properties
        );

        $filter->apply($queryBuilder, new QueryNameGenerator(), $this->resourceClass, 'op');
        $actualDql = $queryBuilder->getQuery()->getDQL();
        $expectedDql = $expected['dql'];

        $this->assertEquals($expectedDql, $actualDql);

        if (!empty($expected['parameters'])) {
            foreach ($expected['parameters'] as $parameterName => $expectedParameterValue) {
                $queryParameter = $queryBuilder->getQuery()->getParameter($parameterName);

                $this->assertNotNull(
                    $queryParameter,
                    sprintf('Expected query parameter "%s" to be set', $parameterName)
                );

                $actualParameterValue = $queryParameter->getValue();

                $this->assertEquals(
                    $expectedParameterValue,
                    $actualParameterValue,
                    sprintf('Expected query parameter "%s" to be "%s"', $parameterName, var_export($expectedParameterValue, true))
                );
            }
        }
    }

    public function testGetDescription()
    {
        $filter = new SearchFilter(
            $this->managerRegistry,
            new RequestStack(),
            $this->iriConverter,
            $this->propertyAccessor
        );

        $this->assertEquals([
            'id' => [
                'property' => 'id',
                'type' => 'integer',
                'required' => false,
                'strategy' => 'exact',
            ],
            'id[]' => [
                'property' => 'id',
                'type' => 'integer',
                'required' => false,
                'strategy' => 'exact',
            ],
            'name' => [
                'property' => 'name',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'name[]' => [
                'property' => 'name',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'alias' => [
                'property' => 'alias',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'alias[]' => [
                'property' => 'alias',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'description' => [
                'property' => 'description',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'description[]' => [
                'property' => 'description',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'dummy' => [
                'property' => 'dummy',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'dummy[]' => [
                'property' => 'dummy',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'dummyDate' => [
                'property' => 'dummyDate',
                'type' => 'datetime',
                'required' => false,
                'strategy' => 'exact',
            ],
            'dummyDate[]' => [
                'property' => 'dummyDate',
                'type' => 'datetime',
                'required' => false,
                'strategy' => 'exact',
            ],
            'dummyPrice' => [
                'property' => 'dummyPrice',
                'type' => 'decimal',
                'required' => false,
                'strategy' => 'exact',
            ],
            'dummyPrice[]' => [
                'property' => 'dummyPrice',
                'type' => 'decimal',
                'required' => false,
                'strategy' => 'exact',
            ],
            'jsonData' => [
                'property' => 'jsonData',
                'type' => 'json_array',
                'required' => false,
                'strategy' => 'exact',
            ],
            'jsonData[]' => [
                'property' => 'jsonData',
                'type' => 'json_array',
                'required' => false,
                'strategy' => 'exact',
            ],
            'nameConverted' => [
                'property' => 'nameConverted',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'nameConverted[]' => [
                'property' => 'nameConverted',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'dummyBoolean' => [
                'property' => 'dummyBoolean',
                'type' => 'boolean',
                'required' => false,
                'strategy' => 'exact',
            ],
            'dummyBoolean[]' => [
                'property' => 'dummyBoolean',
                'type' => 'boolean',
                'required' => false,
                'strategy' => 'exact',
            ],
        ], $filter->getDescription($this->resourceClass));

        $filter = new SearchFilter(
            $this->managerRegistry,
            new RequestStack(),
            $this->iriConverter,
            $this->propertyAccessor,
            null,
            [
                'id' => null,
                'name' => null,
                'alias' => null,
                'dummy' => null,
                'dummyDate' => null,
                'jsonData' => null,
                'nameConverted' => null,
                'foo' => null,
                'relatedDummies.dummyDate' => null,
                'relatedDummy' => null,
            ]
        );

        $this->assertEquals([
            'id' => [
                'property' => 'id',
                'type' => 'integer',
                'required' => false,
                'strategy' => 'exact',
            ],
            'id[]' => [
                'property' => 'id',
                'type' => 'integer',
                'required' => false,
                'strategy' => 'exact',
            ],
            'name' => [
                'property' => 'name',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'name[]' => [
                'property' => 'name',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'alias' => [
                'property' => 'alias',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'alias[]' => [
                'property' => 'alias',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'dummy' => [
                'property' => 'dummy',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'dummy[]' => [
                'property' => 'dummy',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'dummyDate' => [
                'property' => 'dummyDate',
                'type' => 'datetime',
                'required' => false,
                'strategy' => 'exact',
            ],
            'dummyDate[]' => [
                'property' => 'dummyDate',
                'type' => 'datetime',
                'required' => false,
                'strategy' => 'exact',
            ],
            'jsonData' => [
                'property' => 'jsonData',
                'type' => 'json_array',
                'required' => false,
                'strategy' => 'exact',
            ],
            'jsonData[]' => [
                'property' => 'jsonData',
                'type' => 'json_array',
                'required' => false,
                'strategy' => 'exact',
            ],
            'nameConverted' => [
                'property' => 'nameConverted',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'nameConverted[]' => [
                'property' => 'nameConverted',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'relatedDummies.dummyDate' => [
                'property' => 'relatedDummies.dummyDate',
                'type' => 'datetime',
                'required' => false,
                'strategy' => 'exact',
            ],
            'relatedDummies.dummyDate[]' => [
                'property' => 'relatedDummies.dummyDate',
                'type' => 'datetime',
                'required' => false,
                'strategy' => 'exact',
            ],
            'relatedDummy' => [
                'property' => 'relatedDummy',
                'type' => 'iri',
                'required' => false,
                'strategy' => 'exact',
            ],
            'relatedDummy[]' => [
                'property' => 'relatedDummy',
                'type' => 'iri',
                'required' => false,
                'strategy' => 'exact',
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    /**
     * Provides test data.
     *
     * Provides 3 parameters:
     *  - configuration of filterable properties
     *  - filter parameters
     *  - expected DQL query and parameter values
     *
     * @return array
     */
    public function provideApplyTestData() : array
    {
        return [
            'exact' => [
                [
                    'id' => null,
                    'name' => null,
                ],
                [
                    'name' => 'exact',
                ],
                [
                    'dql' => sprintf('SELECT o FROM %s o WHERE o.name = :name_p1', Dummy::class),
                    'parameters' => [
                        'name_p1' => 'exact',
                    ],
                ],
            ],
            'exact (case insensitive)' => [
                [
                    'id' => null,
                    'name' => 'iexact',
                ],
                [
                    'name' => 'exact',
                ],
                [
                    'dql' => sprintf('SELECT o FROM %s o WHERE LOWER(o.name) = LOWER(:name_p1)', Dummy::class),
                    'parameters' => [
                        'name_p1' => 'exact',
                    ],
                ],
            ],
            'invalid property' => [
                [
                    'id' => null,
                    'name' => null,
                ],
                [
                    'foo' => 'exact',
                ],
                [
                    'dql' => sprintf('SELECT o FROM %s o', Dummy::class),
                    'parameters' => [],
                ],
            ],
            'invalid values for relations' => [
                [
                    'id' => null,
                    'name' => null,
                    'relatedDummy' => null,
                    'relatedDummies' => null,
                ],
                [
                    'name' => ['foo'],
                    'relatedDummy' => ['foo'],
                    'relatedDummies' => [['foo']],
                ],
                [
                    'dql' => sprintf('SELECT o FROM %s o INNER JOIN o.relatedDummy relatedDummy_a1 WHERE o.name = :name_p1 AND relatedDummy_a1.id = :relatedDummy_p2', Dummy::class),
                    'parameters' => [
                        'relatedDummy_p2' => 'foo',
                    ],
                ],
            ],
            'partial' => [
                [
                    'id' => null,
                    'name' => 'partial',
                ],
                [
                    'name' => 'partial',
                ],
                [
                    'dql' => sprintf('SELECT o FROM %s o WHERE o.name LIKE :name_p1', Dummy::class),
                    'parameters' => [
                        'name_p1' => '%partial%',
                    ],
                ],
            ],
            'partial (case insensitive)' => [
                [
                    'id' => null,
                    'name' => 'ipartial',
                ],
                [
                    'name' => 'partial',
                ],
                [
                    'dql' => sprintf('SELECT o FROM %s o WHERE LOWER(o.name) LIKE LOWER(:name_p1)', Dummy::class),
                    'parameters' => [
                        'name_p1' => '%partial%',
                    ],
                ],
            ],
            'start' => [
                [
                    'id' => null,
                    'name' => 'start',
                ],
                [
                    'name' => 'partial',
                ],
                [
                    'dql' => sprintf('SELECT o FROM %s o WHERE o.name LIKE CONCAT(:name_p1, \'%%\')', Dummy::class),
                    'parameters' => [
                        'name_p1' => 'partial',
                    ],
                ],
            ],
            'start (case insensitive)' => [
                [
                    'id' => null,
                    'name' => 'istart',
                ],
                [
                    'name' => 'partial',
                ],
                [
                    'dql' => sprintf('SELECT o FROM %s o WHERE LOWER(o.name) LIKE LOWER(CONCAT(:name_p1, \'%%\'))', Dummy::class),
                    'parameters' => [
                        'name_p1' => 'partial',
                    ],
                ],
            ],
            'end' => [
                [
                    'id' => null,
                    'name' => 'end',
                ],
                [
                    'name' => 'partial',
                ],
                [
                    'dql' => sprintf('SELECT o FROM %s o WHERE o.name LIKE CONCAT(\'%%\', :name_p1)', Dummy::class),
                    'parameters' => [
                        'name_p1' => 'partial',
                    ],
                ],
            ],
            'end (case insensitive)' => [
                [
                    'id' => null,
                    'name' => 'iend',
                ],
                [
                    'name' => 'partial',
                ],
                [
                    'dql' => sprintf('SELECT o FROM %s o WHERE LOWER(o.name) LIKE LOWER(CONCAT(\'%%\', :name_p1))', Dummy::class),
                    'parameters' => [
                        'name_p1' => 'partial',
                    ],
                ],
            ],
            'word_start' => [
                [
                    'id' => null,
                    'name' => 'word_start',
                ],
                [
                    'name' => 'partial',
                ],
                [
                    'dql' => sprintf('SELECT o FROM %s o WHERE o.name LIKE CONCAT(:name_p1, \'%%\') OR o.name LIKE :name_p1_2', Dummy::class),
                    'parameters' => [
                        'name_p1' => 'partial',
                        'name_p1_2' => '%partial%',
                    ],
                ],
            ],
            'word_start (case insensitive)' => [
                [
                    'id' => null,
                    'name' => 'iword_start',
                ],
                [
                    'name' => 'partial',
                ],
                [
                    'dql' => sprintf('SELECT o FROM %s o WHERE LOWER(o.name) LIKE LOWER(CONCAT(:name_p1, \'%%\')) OR LOWER(o.name) LIKE LOWER(:name_p1_2)', Dummy::class),
                    'parameters' => [
                        'name_p1' => 'partial',
                        'name_p1_2' => '%partial%',
                    ],
                ],
            ],
            'invalid value for relation' => [
                [
                    'id' => null,
                    'name' => null,
                    'relatedDummy' => null,
                ],
                [
                    'relatedDummy' => 'exact',
                ],
                [
                    'dql' => sprintf('SELECT o FROM %s o INNER JOIN o.relatedDummy relatedDummy_a1 WHERE relatedDummy_a1.id = :relatedDummy_p1', Dummy::class),
                    'parameters' => [
                        'relatedDummy_p1' => 'exact',
                    ],
                ],
            ],
            'IRI value for relation' => [
                [
                    'id' => null,
                    'name' => null,
                    'relatedDummy.id' => null,
                ],
                [
                    'relatedDummy.id' => '/related_dummies/1',
                ],
                [
                    'dql' => sprintf('SELECT o FROM %s o INNER JOIN o.relatedDummy relatedDummy_a1 WHERE relatedDummy_a1.id = :id_p1', Dummy::class),
                    'parameters' => [
                        'id_p1' => 1,
                    ],
                ],
            ],
            'mixed IRI and entity ID values for relations' => [
                [
                    'id' => null,
                    'name' => null,
                    'relatedDummy' => null,
                    'relatedDummies' => null,
                ],
                [
                    'relatedDummy' => ['/related_dummies/1', '2'],
                    'relatedDummies' => '1',
                ],
                [
                    'dql' => sprintf('SELECT o FROM %s o INNER JOIN o.relatedDummy relatedDummy_a1 INNER JOIN o.relatedDummies relatedDummies_a2 WHERE relatedDummy_a1.id IN (:relatedDummy_p1) AND relatedDummies_a2.id = :relatedDummies_p2', Dummy::class),
                    'parameters' => [
                        'relatedDummy_p1' => [1, 2],
                        'relatedDummies_p2' => 1,
                    ],
                ],
            ],
            'nested property' => [
                [
                    'id' => null,
                    'name' => null,
                    'relatedDummy.symfony' => null,
                ],
                [
                    'name' => 'exact',
                    'relatedDummy.symfony' => 'exact',
                ],
                [
                    'dql' => sprintf('SELECT o FROM %s o INNER JOIN o.relatedDummy relatedDummy_a1 WHERE o.name = :name_p1 AND relatedDummy_a1.symfony = :symfony_p2', Dummy::class),
                    'parameters' => [
                        'name_p1' => 'exact',
                        'symfony_p2' => 'exact',
                    ],
                ],
            ],
        ];
    }

    public function testDoubleJoin()
    {
        $request = Request::create('/api/dummies', 'GET', ['relatedDummy.symfony' => 'exact']);
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $queryBuilder = $this->repository->createQueryBuilder('o');
        $filter = new SearchFilter(
            $this->managerRegistry,
            $requestStack,
            $this->iriConverter,
            $this->propertyAccessor,
            null,
            ['relatedDummy.symfony' => null]
        );

        $queryBuilder->innerJoin('o.relatedDummy', 'relateddummy_a1');

        $filter->apply($queryBuilder, new QueryNameGenerator(), $this->resourceClass, 'op');
        $actual = strtolower($queryBuilder->getQuery()->getDQL());
        $expected = strtolower(sprintf('SELECT o FROM %s o inner join o.relatedDummy relateddummy_a1 WHERE relateddummy_a1.symfony = :symfony_p1', Dummy::class));
        $this->assertEquals($actual, $expected);
    }
}
