<?php

namespace App\DataFixtures;

use App\Entity\Assunto;
use App\Entity\Autor;
use App\Entity\Livro;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/** Carrega assuntos, autores e livros a partir de data/books.json (sem acessar a API). */
class AppFixtures extends Fixture
{
    public function __construct(private readonly BooksFile $booksFile)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $data = $this->booksFile->read();

        $assuntos = [];
        foreach ($data['subjects'] as $descricao) {
            $assuntos[$descricao] = (new Assunto())->setDescricao($descricao);
            $manager->persist($assuntos[$descricao]);
        }

        $autores = [];
        foreach ($data['books'] as $book) {
            $livro = (new Livro())
                ->setTitulo($book['titulo'])
                ->setEditora($book['editora'])
                ->setEdicao($book['edicao'])
                ->setAnoPublicacao($book['anoPublicacao'])
                ->setValor($book['valor'])
                ->setImagemMobileUrl($book['imagemMobileUrl'])
                ->setImagemDesktopUrl($book['imagemDesktopUrl'])
                ->setDescricao($book['descricao'] ?? null);

            foreach ($book['autores'] as $nome) {
                $key = mb_strtolower($nome);
                if (!isset($autores[$key])) {
                    $autores[$key] = (new Autor())->setNome($nome);
                    $manager->persist($autores[$key]);
                }
                $livro->addAutor($autores[$key]);
            }

            foreach ($book['assuntos'] as $descricao) {
                $livro->addAssunto($assuntos[$descricao]);
            }

            $manager->persist($livro);
        }

        $manager->flush();
    }
}
