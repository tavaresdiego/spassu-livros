<?php

namespace App\Tests\DataFixtures;

use App\DataFixtures\BooksFile;
use App\DataFixtures\AppFixtures;
use App\Entity\Assunto;
use App\Entity\Autor;
use App\Entity\Livro;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BooksDataTest extends KernelTestCase
{
    /** A home exibe 6 livros por assunto; cada assunto precisa ter ao menos isso como assunto principal. */
    private const MIN_BOOKS_PER_SUBJECT = 6;

    private function booksFile(): BooksFile
    {
        return new BooksFile(dirname(__DIR__, 2).'/data/books.json');
    }

    public function testJsonFileIsValid(): void
    {
        $data = $this->booksFile()->read();

        self::assertCount(6, $data['subjects'], 'O arquivo deve ter exatamente 6 assuntos');
        $ids = array_column($data['books'], 'googleId');
        self::assertSame(count($ids), count(array_unique($ids)), 'Livros duplicados');

        $primary = array_count_values(array_map(fn (array $b) => $b['assuntos'][0], $data['books']));
        foreach ($data['subjects'] as $subject) {
            self::assertGreaterThanOrEqual(self::MIN_BOOKS_PER_SUBJECT, $primary[$subject] ?? 0, "Assunto $subject");
        }

        $shared = array_filter($data['books'], fn (array $b) => count($b['assuntos']) > 1);
        self::assertGreaterThanOrEqual(3, count($shared), 'Pelo menos 3 livros devem ter mais de um assunto');

        foreach ($data['books'] as $book) {
            self::assertLessThanOrEqual(40, mb_strlen($book['titulo']), $book['titulo']);
            self::assertNotEmpty($book['autores'], $book['titulo']);
            self::assertNotEmpty($book['assuntos'], $book['titulo']);
            self::assertStringStartsWith('https://', $book['imagemMobileUrl']);
            self::assertStringStartsWith('https://', $book['imagemDesktopUrl']);
            self::assertStringNotContainsString('edge=curl', $book['imagemDesktopUrl']);
            // Parâmetros de zoom só se aplicam a capas vindas do Google Books.
            if (str_contains($book['imagemMobileUrl'], 'books.google')) {
                self::assertStringContainsString('zoom=2', $book['imagemMobileUrl']);
            }
            if (str_contains($book['imagemDesktopUrl'], 'books.google')) {
                self::assertStringContainsString('zoom=4', $book['imagemDesktopUrl']);
            }
            self::assertMatchesRegularExpression('/^\d{4}$/', $book['anoPublicacao']);
        }
    }

    public function testFixturesLoadSubjectsAuthorsAndBooks(): void
    {
        self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $books = $this->booksFile()->read()['books'];
        (new AppFixtures($this->booksFile()))->load($em);
        $em->clear();

        self::assertSame(6, $em->getRepository(Assunto::class)->count([]));
        self::assertSame(count($books), $em->getRepository(Livro::class)->count([]));

        $nomes = array_map(fn (Autor $a) => $a->getNome(), $em->getRepository(Autor::class)->findAll());
        self::assertSame(count($nomes), count(array_unique($nomes)), 'Autores duplicados');

        foreach ($em->getRepository(Livro::class)->findAll() as $livro) {
            self::assertLessThanOrEqual(40, mb_strlen($livro->getTitulo()));
            self::assertGreaterThan(0, $livro->getAutores()->count(), $livro->getTitulo());
            self::assertGreaterThan(0, $livro->getAssuntos()->count(), $livro->getTitulo());
            self::assertNotEmpty($livro->getImagemMobileUrl());
            self::assertNotEmpty($livro->getImagemDesktopUrl());
        }

        $conn = $em->getConnection();
        $expectedLinks = array_sum(array_map(fn (array $b) => count($b['assuntos']), $books));
        self::assertSame($expectedLinks, (int) $conn->fetchOne('SELECT COUNT(*) FROM Livro_Assunto'));
    }
}
