<?php

namespace App\Tests\Entity;

use App\Entity\Assunto;
use App\Entity\Autor;
use App\Entity\Livro;
use App\Repository\LivroRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class LivroPersistenceTest extends KernelTestCase
{
    public function testPersistsLivroWithTwoAutoresAndTwoAssuntos(): void
    {
        self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $livro = (new Livro())
            ->setTitulo('Livro a quatro mãos')
            ->setEditora('Editora X')
            ->setEdicao(2)
            ->setAnoPublicacao('2020')
            ->setValor('59.90')
            ->setDescricao('Uma descrição longa.')
            ->addAutor((new Autor())->setNome('Autor Um'))
            ->addAutor((new Autor())->setNome('Autor Dois'))
            ->addAssunto((new Assunto())->setDescricao('Ficção'))
            ->addAssunto((new Assunto())->setDescricao('Drama'));

        foreach ($livro->getAutores() as $autor) {
            $em->persist($autor);
        }
        foreach ($livro->getAssuntos() as $assunto) {
            $em->persist($assunto);
        }
        $em->persist($livro);
        $em->flush();
        $id = $livro->getCodl();
        $em->clear();

        $reloaded = static::getContainer()->get(LivroRepository::class)->find($id);
        self::assertNotNull($reloaded);
        self::assertSame('59.90', $reloaded->getValor());
        self::assertEqualsCanonicalizing(['Autor Um', 'Autor Dois'], $reloaded->getAutores()->map(fn (Autor $a) => $a->getNome())->toArray());
        self::assertEqualsCanonicalizing(['Ficção', 'Drama'], $reloaded->getAssuntos()->map(fn (Assunto $a) => $a->getDescricao())->toArray());

        $conn = $em->getConnection();
        self::assertSame(2, (int) $conn->fetchOne('SELECT COUNT(*) FROM Livro_Autor WHERE Livro_Codl = ?', [$id]));
        self::assertSame(2, (int) $conn->fetchOne('SELECT COUNT(*) FROM Livro_Assunto WHERE Livro_Codl = ?', [$id]));
    }
}
