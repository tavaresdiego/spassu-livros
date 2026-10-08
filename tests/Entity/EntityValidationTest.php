<?php

namespace App\Tests\Entity;

use App\Entity\Assunto;
use App\Entity\Autor;
use App\Entity\Livro;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EntityValidationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    private function validLivro(): Livro
    {
        return (new Livro())
            ->setTitulo('Dom Casmurro')
            ->setEditora('Garnier')
            ->setEdicao(1)
            ->setAnoPublicacao('1899')
            ->setValor('49.90')
            ->addAutor((new Autor())->setNome('Machado de Assis'))
            ->addAssunto((new Assunto())->setDescricao('Romance'));
    }

    /** @return list<string> */
    private function violatedProperties(object $entity): array
    {
        $paths = [];
        foreach ($this->validator->validate($entity) as $violation) {
            $paths[] = $violation->getPropertyPath();
        }

        return $paths;
    }

    public function testValidLivroHasNoViolations(): void
    {
        self::assertSame([], $this->violatedProperties($this->validLivro()));
    }

    public function testTituloIsRequired(): void
    {
        self::assertContains('titulo', $this->violatedProperties($this->validLivro()->setTitulo('')));
    }

    public function testTituloMaxLength(): void
    {
        self::assertContains('titulo', $this->violatedProperties($this->validLivro()->setTitulo(str_repeat('a', 41))));
    }

    public function testEditoraMaxLength(): void
    {
        self::assertContains('editora', $this->violatedProperties($this->validLivro()->setEditora(str_repeat('a', 41))));
    }

    public function testEdicaoMustBeAtLeastOne(): void
    {
        self::assertContains('edicao', $this->violatedProperties($this->validLivro()->setEdicao(0)));
    }

    #[DataProvider('invalidYears')]
    public function testAnoPublicacaoMustHaveFourDigits(string $ano): void
    {
        self::assertContains('anoPublicacao', $this->violatedProperties($this->validLivro()->setAnoPublicacao($ano)));
    }

    /** @return iterable<array{string}> */
    public static function invalidYears(): iterable
    {
        yield ['99'];
        yield ['abcd'];
        yield ['19999'];
    }

    public function testValorIsRequired(): void
    {
        self::assertContains('valor', $this->violatedProperties($this->validLivro()->setValor(null)));
    }

    public function testValorCannotBeNegative(): void
    {
        self::assertContains('valor', $this->violatedProperties($this->validLivro()->setValor('-1.00')));
    }

    public function testImageUrlsMustBeValidWhenFilled(): void
    {
        $livro = $this->validLivro()->setImagemMobileUrl('not a url')->setImagemDesktopUrl('ftp//x');
        $paths = $this->violatedProperties($livro);
        self::assertContains('imagemMobileUrl', $paths);
        self::assertContains('imagemDesktopUrl', $paths);
    }

    public function testImageUrlsAreOptional(): void
    {
        $livro = $this->validLivro()->setImagemMobileUrl(null)->setImagemDesktopUrl('https://example.com/capa.jpg');
        self::assertSame([], $this->violatedProperties($livro));
    }

    public function testLivroRequiresAtLeastOneAutor(): void
    {
        $livro = $this->validLivro();
        foreach ($livro->getAutores() as $autor) {
            $livro->removeAutor($autor);
        }
        self::assertContains('autores', $this->violatedProperties($livro));
    }

    public function testLivroRequiresAtLeastOneAssunto(): void
    {
        $livro = $this->validLivro();
        foreach ($livro->getAssuntos() as $assunto) {
            $livro->removeAssunto($assunto);
        }
        self::assertContains('assuntos', $this->violatedProperties($livro));
    }

    public function testAutorNomeIsRequiredAndLimited(): void
    {
        self::assertContains('nome', $this->violatedProperties((new Autor())->setNome('')));
        self::assertContains('nome', $this->violatedProperties((new Autor())->setNome(str_repeat('a', 41))));
        self::assertSame([], $this->violatedProperties((new Autor())->setNome('Clarice Lispector')));
    }

    public function testAssuntoDescricaoIsRequiredAndLimited(): void
    {
        self::assertContains('descricao', $this->violatedProperties((new Assunto())->setDescricao('')));
        self::assertContains('descricao', $this->violatedProperties((new Assunto())->setDescricao(str_repeat('a', 21))));
        self::assertSame([], $this->violatedProperties((new Assunto())->setDescricao('Ficção')));
    }
}
