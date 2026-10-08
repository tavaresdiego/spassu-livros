<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Livro;
use App\Tests\Support\BookFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LivroControllerTest extends WebTestCase
{
    use BookFactory;

    public function testListsLivrosWithFormattedValor(): void
    {
        $client = static::createClient();
        $this->createLivro('Dom Casmurro', [$this->createAutor('Machado de Assis')], [$this->createAssunto('Ficção')], '1234.56');

        $client->request('GET', '/admin/livros');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Livros');
        $table = str_replace("\u{A0}", ' ', $client->getCrawler()->filter('table')->text());
        self::assertStringContainsString('Dom Casmurro', $table);
        self::assertStringContainsString('Machado de Assis', $table);
        self::assertStringContainsString('R$ 1.234,56', $table);
    }

    public function testCreatesLivroWithAutoresAssuntosAndMaskedValor(): void
    {
        $client = static::createClient();
        $a1 = $this->createAutor('Autor A');
        $a2 = $this->createAutor('Autor B');
        $s1 = $this->createAssunto('Tecnologia');

        $crawler = $client->request('GET', '/admin/livros/novo');
        self::assertCount(1, $crawler->filter('select[name="livro[autores][]"][multiple][data-pillbox]'));
        self::assertCount(1, $crawler->filter('select[name="livro[assuntos][]"][multiple][data-pillbox]'));
        self::assertCount(0, $crawler->filter('[data-select2]'));
        self::assertCount(1, $crawler->filter('script[src$="select2.min.js"]'));
        self::assertCount(1, $crawler->filter('link[href$="select2.min.css"]'));
        self::assertCount(1, $crawler->filter('input[name="livro[valor]"][data-money-mask]'));

        $form = $crawler->selectButton('Salvar')->form();
        $form['livro[titulo]'] = 'Código Limpo';
        $form['livro[editora]'] = 'Alta Books';
        $form['livro[edicao]'] = '2';
        $form['livro[anoPublicacao]'] = '2009';
        $form['livro[valor]'] = 'R$ 1.234,56';
        $form['livro[imagemMobileUrl]'] = 'https://example.com/m.jpg';
        $form['livro[imagemDesktopUrl]'] = 'https://example.com/d.jpg';
        $form['livro[autores]']->select([(string) $a1->getCodAu(), (string) $a2->getCodAu()]);
        $form['livro[assuntos]']->select([(string) $s1->getCodAs()]);
        $client->submit($form);

        self::assertResponseRedirects('/admin/livros');
        $this->em()->clear();
        $livro = $this->em()->getRepository(Livro::class)->findOneBy(['titulo' => 'Código Limpo']);
        self::assertNotNull($livro);
        self::assertSame('1234.56', $livro->getValor());
        self::assertCount(2, $livro->getAutores());
        self::assertCount(1, $livro->getAssuntos());
        self::assertSame('https://example.com/d.jpg', $livro->getImagemDesktopUrl());
    }

    public function testValorIsRequiredAndValidated(): void
    {
        $client = static::createClient();
        $autor = $this->createAutor('Autor');
        $assunto = $this->createAssunto('Assunto');

        $crawler = $client->request('GET', '/admin/livros/novo');
        $form = $crawler->selectButton('Salvar')->form();
        $form['livro[titulo]'] = 'Sem Valor';
        $form['livro[edicao]'] = '1';
        $form['livro[anoPublicacao]'] = '2020';
        $form['livro[valor]'] = '';
        $form['livro[autores]']->select([(string) $autor->getCodAu()]);
        $form['livro[assuntos]']->select([(string) $assunto->getCodAs()]);
        $client->submit($form);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.invalid-feedback', 'Informe o valor.');

        $form['livro[valor]'] = 'abc';
        $client->submit($form);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.invalid-feedback', 'Informe um valor válido');
    }

    public function testEditShowsMaskedValorAndUpdates(): void
    {
        $client = static::createClient();
        $livro = $this->createLivro('Antigo', [$this->createAutor('X')], [$this->createAssunto('Y')], '59.90');
        $id = $livro->getCodl();

        $crawler = $client->request('GET', "/admin/livros/$id/editar");
        self::assertSame('59,90', $crawler->filter('input[name="livro[valor]"]')->attr('value'));

        $client->submitForm('Salvar', ['livro[titulo]' => 'Novo Título', 'livro[valor]' => '79,00']);
        self::assertResponseRedirects('/admin/livros');
        $this->em()->clear();
        $reloaded = $this->em()->find(Livro::class, $id);
        self::assertSame('Novo Título', $reloaded?->getTitulo());
        self::assertSame('79.00', $reloaded?->getValor());
    }

    public function testDeletesLivroAndItsLinks(): void
    {
        $client = static::createClient();
        $livro = $this->createLivro('Para Excluir', [$this->createAutor('Z')], [$this->createAssunto('W')]);
        $id = $livro->getCodl();

        $crawler = $client->request('GET', '/admin/livros');
        $client->submit($crawler->filter("form[action=\"/admin/livros/$id/excluir\"]")->form());

        self::assertResponseRedirects('/admin/livros');
        $conn = $this->em()->getConnection();
        self::assertSame(0, (int) $conn->fetchOne('SELECT COUNT(*) FROM Livro WHERE Codl = ?', [$id]));
        self::assertSame(0, (int) $conn->fetchOne('SELECT COUNT(*) FROM Livro_Autor WHERE Livro_Codl = ?', [$id]));
    }

    public function testFiltersLivrosByTituloAutorAssuntoAndEditora(): void
    {
        $client = static::createClient();
        $ficcao = $this->createAssunto('Ficção');
        $historia = $this->createAssunto('História');
        $dom = $this->createLivro('Dom Casmurro', [$this->createAutor('Machado de Assis'), $this->createAutor('Coautor')], [$ficcao, $historia]);
        $dom->setEditora('Garnier');
        $this->em()->flush();
        $this->createLivro('A Hora da Estrela', [$this->createAutor('Clarice Lispector')], [$ficcao]);

        $cases = [
            'titulo=casmurro' => 'Dom Casmurro',
            'autor=machado' => 'Dom Casmurro',
            'assunto='.$historia->getCodAs() => 'Dom Casmurro',
            'editora=garn' => 'Dom Casmurro',
            'autor=clarice&assunto='.$ficcao->getCodAs() => 'A Hora da Estrela',
        ];
        foreach ($cases as $query => $expected) {
            $client->request('GET', '/admin/livros?'.$query);
            self::assertResponseIsSuccessful();
            $rows = $client->getCrawler()->filter('tbody tr');
            self::assertCount(1, $rows, $query);
            self::assertStringContainsString($expected, $rows->text(), $query);
        }

        // o filtro não corta as coleções exibidas
        $client->request('GET', '/admin/livros?autor=machado');
        self::assertSelectorTextContains('tbody', 'Coautor');
        self::assertSelectorTextContains('tbody', 'História');
        self::assertInputValueSame('autor', 'machado');

        // título não casa com código
        $client->request('GET', '/admin/livros?titulo='.$dom->getCodl());
        self::assertSelectorTextContains('.app-empty-state', 'Nenhum livro encontrado');
    }

    public function testFilterSelectListsAssuntos(): void
    {
        $client = static::createClient();
        $this->createAssunto('Poesia');

        $crawler = $client->request('GET', '/admin/livros');
        self::assertCount(1, $crawler->filter('select[name="assunto"] option:contains("Poesia")'));
    }

    public function testListsMostRecentLivrosFirst(): void
    {
        $client = static::createClient();
        $autor = $this->createAutor('Autor');
        $assunto = $this->createAssunto('Assunto');
        $this->createLivro('Aaa Antigo', [$autor], [$assunto]);
        $this->createLivro('Zzz Recente', [$autor], [$assunto]);

        $crawler = $client->request('GET', '/admin/livros');
        self::assertStringContainsString('Zzz Recente', $crawler->filter('tbody tr')->first()->text());
    }
}
