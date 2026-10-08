<?php

namespace App\Tests\Database;

use App\Entity\Assunto;
use App\Entity\Autor;
use App\Entity\Livro;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class LivrosPorAutorViewTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private int $codl;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $livro = (new Livro())
            ->setTitulo('Obra Conjunta')
            ->setEditora('Editora Y')
            ->setEdicao(1)
            ->setAnoPublicacao('2015')
            ->setValor('120.50');
        foreach (['Ana Souza', 'Bruno Lima'] as $nome) {
            $autor = (new Autor())->setNome($nome);
            $this->em->persist($autor);
            $livro->addAutor($autor);
        }
        foreach (['Ciência', 'História'] as $descricao) {
            $assunto = (new Assunto())->setDescricao($descricao);
            $this->em->persist($assunto);
            $livro->addAssunto($assunto);
        }
        $this->em->persist($livro);
        $this->em->flush();
        $this->codl = $livro->getCodl();
    }

    /** @return list<array<string, mixed>> */
    private function rows(): array
    {
        return $this->em->getConnection()->fetchAllAssociative(
            'SELECT * FROM vw_livros_por_autor WHERE Codl = ? ORDER BY NomeAutor',
            [$this->codl],
        );
    }

    public function testLivroWithTwoAutoresAppearsOncePerAutor(): void
    {
        $rows = $this->rows();
        self::assertCount(2, $rows);
        self::assertSame(['Ana Souza', 'Bruno Lima'], array_column($rows, 'NomeAutor'));
        foreach ($rows as $row) {
            self::assertSame('Obra Conjunta', $row['Titulo']);
            self::assertSame('Editora Y', $row['Editora']);
            self::assertSame(1, (int) $row['Edicao']);
            self::assertSame('2015', $row['AnoPublicacao']);
            self::assertSame('120.50', $row['Valor']);
            self::assertNotEmpty($row['CodAu']);
        }
    }

    public function testAssuntosAreConcatenated(): void
    {
        foreach ($this->rows() as $row) {
            self::assertSame('Ciência, História', $row['Assuntos']);
        }
    }
}
