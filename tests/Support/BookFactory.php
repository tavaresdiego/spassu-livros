<?php

namespace App\Tests\Support;

use App\Entity\Assunto;
use App\Entity\Autor;
use App\Entity\Livro;
use Doctrine\ORM\EntityManagerInterface;

/** Cria registros no banco _test (cada teste roda dentro de uma transação do DAMA). */
trait BookFactory
{
    protected function em(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    protected function createAutor(string $nome): Autor
    {
        $autor = (new Autor())->setNome($nome);
        $this->em()->persist($autor);
        $this->em()->flush();

        return $autor;
    }

    protected function createAssunto(string $descricao): Assunto
    {
        $assunto = (new Assunto())->setDescricao($descricao);
        $this->em()->persist($assunto);
        $this->em()->flush();

        return $assunto;
    }

    /**
     * @param list<Autor>   $autores
     * @param list<Assunto> $assuntos
     */
    protected function createLivro(string $titulo, array $autores, array $assuntos, string $valor = '49.90'): Livro
    {
        $livro = (new Livro())
            ->setTitulo($titulo)
            ->setEditora('Editora Teste')
            ->setEdicao(1)
            ->setAnoPublicacao('2020')
            ->setValor($valor)
            ->setImagemMobileUrl('https://example.com/'.rawurlencode($titulo).'-m.jpg')
            ->setImagemDesktopUrl('https://example.com/'.rawurlencode($titulo).'-d.jpg');
        foreach ($autores as $autor) {
            $livro->addAutor($autor);
        }
        foreach ($assuntos as $assunto) {
            $livro->addAssunto($assunto);
        }
        $this->em()->persist($livro);
        $this->em()->flush();

        return $livro;
    }
}
