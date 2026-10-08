<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Assunto;
use App\Tests\Support\BookFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AssuntoControllerTest extends WebTestCase
{
    use BookFactory;

    public function testListsAssuntos(): void
    {
        $client = static::createClient();
        $this->createAssunto('Poesia');

        $client->request('GET', '/admin/assuntos');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Assuntos');
        self::assertSelectorTextContains('table', 'Poesia');
    }

    public function testCreatesAndEditsAssunto(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/assuntos/novo');
        $client->submitForm('Salvar', ['assunto[descricao]' => 'Romance']);
        self::assertResponseRedirects('/admin/assuntos');

        $assunto = $this->em()->getRepository(Assunto::class)->findOneBy(['descricao' => 'Romance']);
        self::assertNotNull($assunto);
        $id = $assunto->getCodAs();

        $client->request('GET', "/admin/assuntos/$id/editar");
        $client->submitForm('Salvar', ['assunto[descricao]' => 'Romances']);
        self::assertResponseRedirects('/admin/assuntos');
        $this->em()->clear();
        self::assertSame('Romances', $this->em()->find(Assunto::class, $id)?->getDescricao());
    }

    public function testDescricaoLongerThan20IsRejected(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/assuntos/novo');
        $client->submitForm('Salvar', ['assunto[descricao]' => str_repeat('a', 21)]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.invalid-feedback', 'no máximo 20 caracteres');
    }

    public function testDeletingAssuntoWithLivrosFailsWithMessage(): void
    {
        $client = static::createClient();
        $assunto = $this->createAssunto('Vinculado');
        $this->createLivro('Livro do Assunto', [$this->createAutor('Alguém')], [$assunto]);
        $id = $assunto->getCodAs();

        $crawler = $client->request('GET', '/admin/assuntos');
        $client->submit($crawler->filter("form[action=\"/admin/assuntos/$id/excluir\"]")->form());

        self::assertResponseRedirects('/admin/assuntos');
        $client->followRedirect();
        self::assertSelectorTextContains('.alert-danger', 'não pode ser excluído');
        self::assertSame(1, (int) $this->em()->getConnection()->fetchOne('SELECT COUNT(*) FROM Assunto WHERE codAs = ?', [$id]));
    }

    public function testDeletesUnusedAssunto(): void
    {
        $client = static::createClient();
        $id = $this->createAssunto('Avulso')->getCodAs();

        $crawler = $client->request('GET', '/admin/assuntos');
        $client->submit($crawler->filter("form[action=\"/admin/assuntos/$id/excluir\"]")->form());

        self::assertResponseRedirects('/admin/assuntos');
        $this->em()->clear();
        self::assertNull($this->em()->find(Assunto::class, $id));
    }

    public function testFiltersAssuntosByDescricaoOrCodigo(): void
    {
        $client = static::createClient();
        $ficcao = $this->createAssunto('Ficção');
        $this->createAssunto('História');

        $client->request('GET', '/admin/assuntos?q=fic');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('table', 'Ficção');
        self::assertSelectorTextNotContains('table', 'História');

        $client->request('GET', '/admin/assuntos?q='.$ficcao->getCodAs());
        self::assertSelectorTextContains('table', 'Ficção');
        self::assertSelectorTextNotContains('table', 'História');

        $client->request('GET', '/admin/assuntos?q=inexistente');
        self::assertSelectorTextContains('.app-empty-state', 'Nenhum assunto encontrado');
    }

    public function testListsMostRecentAssuntosFirst(): void
    {
        $client = static::createClient();
        $this->createAssunto('Aaa Antigo');
        $this->createAssunto('Zzz Recente');

        $crawler = $client->request('GET', '/admin/assuntos');
        self::assertStringContainsString('Zzz Recente', $crawler->filter('tbody tr')->first()->text());
    }
}
