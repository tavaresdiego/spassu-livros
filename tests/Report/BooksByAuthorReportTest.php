<?php

namespace App\Tests\Report;

use App\Report\BooksByAuthorReport;
use App\Tests\Support\BookFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BooksByAuthorReportTest extends KernelTestCase
{
    use BookFactory;

    public function testGroupsRowsByAutorFromView(): void
    {
        self::bootKernel();
        $a = $this->createAutor('ZZ Autor B');
        $b = $this->createAutor('ZZ Autor A');
        $this->createLivro('Compartilhado', [$a, $b], [$this->createAssunto('Ensaio')], '10.00');
        $this->createLivro('Solo', [$a], [], '5.00');

        $groups = array_values(array_filter(
            static::getContainer()->get(BooksByAuthorReport::class)->generate(),
            fn ($g) => str_starts_with($g['nome'], 'ZZ '),
        ));

        self::assertSame(['ZZ Autor A', 'ZZ Autor B'], array_column($groups, 'nome'));
        self::assertSame(['Compartilhado'], array_column($groups[0]['livros'], 'Titulo'));
        self::assertSame(['Compartilhado', 'Solo'], array_column($groups[1]['livros'], 'Titulo'));
        self::assertSame(2, $groups[1]['total']);
        self::assertSame('15.00', $groups[1]['valorTotal']);
        self::assertSame('Ensaio', $groups[0]['livros'][0]['Assuntos']);
    }
}
