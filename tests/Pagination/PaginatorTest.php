<?php

namespace App\Tests\Pagination;

use App\Entity\Autor;
use App\Pagination\Paginator;
use App\Tests\Support\BookFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PaginatorTest extends KernelTestCase
{
    use BookFactory;

    protected function setUp(): void
    {
        self::bootKernel();
        foreach (range(1, 7) as $i) {
            $this->createAutor(sprintf('Autor %02d', $i));
        }
    }

    private function query(): \Doctrine\ORM\QueryBuilder
    {
        return $this->em()->createQueryBuilder()->select('a')->from(Autor::class, 'a')->orderBy('a.nome');
    }

    public function testReturnsRequestedSlice(): void
    {
        $page = Paginator::paginate($this->query(), page: 2, perPage: 3);

        self::assertSame(2, $page->page);
        self::assertSame(3, $page->pages);
        self::assertSame(7, $page->total);
        self::assertSame(['Autor 04', 'Autor 05', 'Autor 06'], array_map(fn (Autor $a) => $a->getNome(), $page->items));
        self::assertTrue($page->hasPrevious());
        self::assertTrue($page->hasNext());
    }

    public function testClampsOutOfRangePages(): void
    {
        self::assertSame(3, Paginator::paginate($this->query(), page: 99, perPage: 3)->page);
        self::assertSame(1, Paginator::paginate($this->query(), page: -4, perPage: 3)->page);
    }

    public function testEmptyResultHasOnePage(): void
    {
        $page = Paginator::paginate($this->query()->where('a.nome = :x')->setParameter('x', 'ninguém'), 1, 3);

        self::assertSame([], $page->items);
        self::assertSame(0, $page->total);
        self::assertSame(1, $page->pages);
        self::assertFalse($page->hasNext());
    }
}
