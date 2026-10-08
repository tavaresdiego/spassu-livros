<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Autor;
use App\Tests\Support\BookFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AutorControllerTest extends WebTestCase
{
    use BookFactory;

    public function testListsAutoresPaginated(): void
    {
        $client = static::createClient();
        foreach (range(1, 25) as $i) {
            $this->createAutor(sprintf('Autor %02d', $i));
        }

        $crawler = $client->request('GET', '/admin/autores');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Autores');
        self::assertSelectorTextContains('table', 'Autor 25');
        self::assertSelectorTextNotContains('table', 'Autor 01');
        self::assertCount(1, $crawler->filter('nav[aria-label="Paginação"]'));

        $client->request('GET', '/admin/autores?page=2');
        self::assertSelectorTextContains('table', 'Autor 01');
    }

    public function testCreatesAutor(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/autores/novo');
        $client->submitForm('Salvar', ['autor[nome]' => 'Machado de Assis']);

        self::assertResponseRedirects('/admin/autores');
        $client->followRedirect();
        self::assertSelectorTextContains('.alert-success', 'Autor cadastrado');
        self::assertNotNull($this->em()->getRepository(Autor::class)->findOneBy(['nome' => 'Machado de Assis']));
    }

    public function testInvalidAutorShowsErrors(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/autores/novo');
        $client->submitForm('Salvar', ['autor[nome]' => '']);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.invalid-feedback', 'Informe o nome do autor.');
    }

    public function testEditsAutor(): void
    {
        $client = static::createClient();
        $id = $this->createAutor('Nome Antigo')->getCodAu();

        $client->request('GET', "/admin/autores/$id/editar");
        $client->submitForm('Salvar', ['autor[nome]' => 'Nome Novo']);

        self::assertResponseRedirects('/admin/autores');
        $this->em()->clear();
        self::assertSame('Nome Novo', $this->em()->find(Autor::class, $id)?->getNome());
    }

    public function testDeletesUnusedAutor(): void
    {
        $client = static::createClient();
        $id = $this->createAutor('Sem Livros')->getCodAu();

        $crawler = $client->request('GET', '/admin/autores');
        $client->submit($crawler->filter("form[action=\"/admin/autores/$id/excluir\"]")->form());

        self::assertResponseRedirects('/admin/autores');
        $client->followRedirect();
        self::assertSelectorTextContains('.alert-success', 'Autor excluído');
        $this->em()->clear();
        self::assertNull($this->em()->find(Autor::class, $id));
    }

    public function testDeletingAutorWithLivrosFailsWithMessage(): void
    {
        $client = static::createClient();
        $autor = $this->createAutor('Autor Vinculado');
        $this->createLivro('Livro Vinculado', [$autor], [$this->createAssunto('Drama')]);
        $id = $autor->getCodAu();

        $crawler = $client->request('GET', '/admin/autores');
        $client->submit($crawler->filter("form[action=\"/admin/autores/$id/excluir\"]")->form());

        self::assertResponseRedirects('/admin/autores');
        $client->followRedirect();
        self::assertSelectorTextContains('.alert-danger', 'não pode ser excluído');
        self::assertSame(1, (int) $this->em()->getConnection()->fetchOne('SELECT COUNT(*) FROM Autor WHERE CodAu = ?', [$id]));
    }

    public function testDeleteRequiresValidCsrfToken(): void
    {
        $client = static::createClient();
        $id = $this->createAutor('Protegido')->getCodAu();

        $client->request('POST', "/admin/autores/$id/excluir", ['_token' => 'invalido']);

        self::assertResponseRedirects('/admin/autores');
        $client->followRedirect();
        self::assertSelectorTextContains('.alert-danger', 'Token de segurança inválido');
        self::assertSame(1, (int) $this->em()->getConnection()->fetchOne('SELECT COUNT(*) FROM Autor WHERE CodAu = ?', [$id]));
    }

    public function testUnknownAutorIs404(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/autores/999999/editar');

        self::assertResponseStatusCodeSame(404);
    }

    public function testFiltersAutoresByNomeOrCodigo(): void
    {
        $client = static::createClient();
        $machado = $this->createAutor('Machado de Assis');
        $this->createAutor('Clarice Lispector');

        $client->request('GET', '/admin/autores?q=machado');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('table', 'Machado de Assis');
        self::assertSelectorTextNotContains('table', 'Clarice Lispector');
        self::assertInputValueSame('q', 'machado');

        $client->request('GET', '/admin/autores?q='.$machado->getCodAu());
        self::assertSelectorTextContains('table', 'Machado de Assis');
        self::assertSelectorTextNotContains('table', 'Clarice Lispector');

        $client->request('GET', '/admin/autores?q=inexistente');
        self::assertSelectorNotExists('table');
        self::assertSelectorTextContains('.app-empty-state', 'Nenhum autor encontrado');
    }

    public function testFilterIsKeptInPagination(): void
    {
        $client = static::createClient();
        foreach (range(1, 25) as $i) {
            $this->createAutor(sprintf('Filtrado %02d', $i));
        }

        $crawler = $client->request('GET', '/admin/autores?q=Filtrado');
        $href = $crawler->filter('nav[aria-label="Paginação"]')->selectLink('2')->attr('href');
        self::assertStringContainsString('q=Filtrado', $href);

        $client->request('GET', $href);
        self::assertSelectorTextContains('table', 'Filtrado 01');
    }

    public function testListsMostRecentAutoresFirst(): void
    {
        $client = static::createClient();
        $this->createAutor('Aaa Antigo');
        $this->createAutor('Zzz Recente');

        $crawler = $client->request('GET', '/admin/autores');
        self::assertStringContainsString('Zzz Recente', $crawler->filter('tbody tr')->first()->text());
    }
}
