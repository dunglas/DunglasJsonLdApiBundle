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

namespace ApiPlatform\Core\Tests\Bridge\Elasticsearch\DataProvider;

use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Paginator;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Elasticsearch\Serializer\DocumentNormalizer;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Foo;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class PaginatorTest extends TestCase
{
    use ProphecyTrait;

    private const DOCUMENTS = [
        'hits' => [
            'total' => 8,
            'max_score' => 1,
            'hits' => [
                [
                    '_index' => 'foo',
                    '_type' => '_doc',
                    '_id' => '5',
                    '_score' => 1,
                    '_source' => [
                        'id' => 5,
                        'name' => 'Fribourg',
                        'bar' => 'gruobirf',
                    ],
                ],
                [
                    '_index' => 'foo',
                    '_type' => '_doc',
                    '_id' => '6',
                    '_score' => 1,
                    '_source' => [
                        'id' => 6,
                        'name' => 'Lausanne',
                        'bar' => 'ennasual',
                    ],
                ],
                [
                    '_index' => 'foo',
                    '_type' => '_doc',
                    '_id' => '7',
                    '_score' => 1,
                    '_source' => [
                        'id' => 7,
                        'name' => 'Vallorbe',
                        'bar' => 'ebrollav',
                    ],
                ],
                [
                    '_index' => 'foo',
                    '_type' => '_doc',
                    '_id' => '8',
                    '_score' => 1,
                    '_source' => [
                        'id' => 8,
                        'name' => 'Lugano',
                        'bar' => 'onagul',
                    ],
                ],
            ],
        ],
    ];

    private const OFFSET = 4;
    private const LIMIT = 4;

    /**
     * @var PaginatorInterface
     */
    private $paginator;

    public function testConstruct()
    {
        self::assertInstanceOf(PaginatorInterface::class, $this->paginator);
    }

    public function testCount()
    {
        self::assertCount(4, $this->paginator);
    }

    public function testGetLastPage()
    {
        self::assertSame(2., $this->paginator->getLastPage());
    }

    public function testGetLastPageWithZeroAsLimit()
    {
        self::assertSame(1., $this->getPaginator(0, 0)->getLastPage());
    }

    public function testGetLastPageWithNegativeLimit()
    {
        self::assertSame(1., $this->getPaginator(-1, 0)->getLastPage());
    }

    public function testGetTotalItems()
    {
        self::assertSame(8., $this->paginator->getTotalItems());
    }

    public function testGetTotalItemsForElasticSearch7()
    {
        // the total in elastichsearch >= 7 is object and not integer.
        $documents = self::DOCUMENTS;
        $documents['hits']['total'] = [
            'value' => 8,
            'relation' => 'eq',
        ];

        $paginator = $this->getPaginator(self::LIMIT, self::OFFSET, $documents);

        self::assertSame(8., $paginator->getTotalItems());
    }

    public function testGetCurrentPage()
    {
        self::assertSame(2., $this->paginator->getCurrentPage());
    }

    public function testGetCurrentPageWithZeroAsLimit()
    {
        self::assertSame(1., $this->getPaginator(0, 0)->getCurrentPage());
    }

    public function testGetCurrentPageWithNegativeLimit()
    {
        self::assertSame(1., $this->getPaginator(-1, 0)->getCurrentPage());
    }

    public function testGetItemsPerPage()
    {
        self::assertSame(4., $this->paginator->getItemsPerPage());
    }

    public function testGetIterator()
    {
        // set local cache
        iterator_to_array($this->paginator);

        self::assertEquals(
            array_map(
                function (array $document): Foo {
                    return $this->denormalizeFoo($document['_source']);
                },
                self::DOCUMENTS['hits']['hits']
            ),
            iterator_to_array($this->paginator)
        );
    }

    protected function setUp(): void
    {
        $this->paginator = $this->getPaginator();
    }

    private function getPaginator(int $limit = self::OFFSET, int $offset = self::OFFSET, array $documents = self::DOCUMENTS)
    {
        $denormalizerProphecy = $this->prophesize(DenormalizerInterface::class);

        foreach ($documents['hits']['hits'] as $document) {
            $denormalizerProphecy
                ->denormalize($document, Foo::class, DocumentNormalizer::FORMAT, [AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => true, 'groups' => ['custom']])
                ->willReturn($this->denormalizeFoo($document['_source']));
        }

        return new Paginator($denormalizerProphecy->reveal(), $documents, Foo::class, $limit, $offset, ['groups' => ['custom']]);
    }

    private function denormalizeFoo(array $fooDocument): Foo
    {
        $foo = new Foo();
        $foo->setName($fooDocument['name']);
        $foo->setBar($fooDocument['bar']);

        return $foo;
    }
}
