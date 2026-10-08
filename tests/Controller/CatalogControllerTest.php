<?php

namespace App\Tests\Controller;

use App\Tests\Support\BookFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CatalogControllerTest extends WebTestCase
{
    use BookFactory;

    public function testHomeGroupsSixBooksPerAssuntoWithSearchAndSeeAll(): void
    {
        $client = static::createClient();
        $autor = $this->createAutor('Autor Home');
        $ficcao = $this->createAssunto('Ficção');
        $poesia = $this->createAssunto('Poesia');
        foreach (range(1, 8) as $i) {
            $this->createLivro(sprintf('Ficção %02d', $i), [$autor], [$ficcao]);
        }
        $this->createLivro('Versos', [$autor], [$poesia, $ficcao]);

        $crawler = $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('main form[role="search"] input[name="q"]'));
        $section = $crawler->filter(sprintf('section#assunto-%d', $ficcao->getCodAs()));
        self::assertCount(1, $section);
        self::assertSelectorTextContains(sprintf('section#assunto-%d h2', $ficcao->getCodAs()), 'Ficção');
        self::assertCount(6, $section->filter('.app-book-card'));
        $seeAll = $section->filter(sprintf('a[href="/assunto/ficcao-%d"]', $ficcao->getCodAs()))->reduce(fn ($a) => str_contains($a->text(), 'Ver mais'));
        self::assertCount(1, $seeAll);
        self::assertStringContainsString('(9)', $seeAll->text());
        self::assertCount(1, $crawler->filter(sprintf('section#assunto-%d .app-book-card', $poesia->getCodAs())));
    }

    public function testNavigationListsAssuntos(): void
    {
        $client = static::createClient();
        $assunto = $this->createAssunto('Negócios');

        $crawler = $client->request('GET', '/');
        self::assertCount(1, $crawler->filter(sprintf('header nav a[href="/assunto/negocios-%d"]', $assunto->getCodAs())));
    }

    public function testAssuntoPageListsAllBooksPaginated(): void
    {
        $client = static::createClient();
        $autor = $this->createAutor('Autor');
        $assunto = $this->createAssunto('História');
        $outro = $this->createAssunto('Outro');
        foreach (range(1, 14) as $i) {
            $this->createLivro(sprintf('História %02d', $i), [$autor], [$assunto]);
        }
        $this->createLivro('Fora do Assunto', [$autor], [$outro]);
        $id = $assunto->getCodAs();

        $crawler = $client->request('GET', "/assunto/historia-$id");
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'História');
        self::assertCount(12, $crawler->filter('.app-book-card'));
        self::assertSelectorTextNotContains('main', 'Fora do Assunto');
        self::assertCount(1, $crawler->filter('nav[aria-label="Paginação"]'));

        $crawler = $client->request('GET', "/assunto/historia-$id?page=2");
        self::assertCount(2, $crawler->filter('.app-book-card'));
    }

    public function testUnknownAssuntoIs404(): void
    {
        $client = static::createClient();
        $client->request('GET', '/assunto/qualquer-999999');
        self::assertResponseStatusCodeSame(404);
    }

    public function testSearchByTitulo(): void
    {
        $client = static::createClient();
        $assunto = $this->createAssunto('Tecnologia');
        $this->createLivro('Arquitetura Limpa', [$this->createAutor('Robert Martin')], [$assunto]);
        $this->createLivro('Outro Livro', [$this->createAutor('Fulano')], [$assunto]);

        $crawler = $client->request('GET', '/busca?q=limpa');
        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('.app-book-card'));
        self::assertSelectorTextContains('.app-book-card', 'Arquitetura Limpa');
        self::assertSame('limpa', $crawler->filter('main input[name="q"]')->attr('value'));
    }

    public function testSearchByAutorDoesNotDuplicateBooksAndKeepsAllAutores(): void
    {
        $client = static::createClient();
        $assunto = $this->createAssunto('Ficção');
        $this->createLivro('Livro a Quatro Mãos', [$this->createAutor('Ana Souza'), $this->createAutor('Ana Lima')], [$assunto]);

        $crawler = $client->request('GET', '/busca?q=ana');
        self::assertCount(1, $crawler->filter('.app-book-card'));
        self::assertSelectorTextContains('.app-book-card', 'Ana Souza');
        self::assertSelectorTextContains('.app-book-card', 'Ana Lima');
    }

    public function testSearchWithoutResults(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/busca?q=inexistente-xyz');

        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('.app-book-card'));
        self::assertSelectorTextContains('main', 'Nenhum livro encontrado para "inexistente-xyz"');
    }

    public function testSearchWithEmptyTerm(): void
    {
        $client = static::createClient();
        $this->createLivro('Qualquer', [$this->createAutor('X')], [$this->createAssunto('Y')]);

        $crawler = $client->request('GET', '/busca?q=%20%20');
        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('.app-book-card'));
        self::assertSelectorTextContains('main', 'Digite um título ou nome de autor');
    }

    public function testSearchTreatsWildcardsLiterally(): void
    {
        $client = static::createClient();
        $this->createLivro('Qualquer', [$this->createAutor('X')], [$this->createAssunto('Y')]);

        $crawler = $client->request('GET', '/busca?q=%25');
        self::assertCount(0, $crawler->filter('.app-book-card'));
    }

    public function testBookDetail(): void
    {
        $client = static::createClient();
        $livro = $this->createLivro('Dom Casmurro', [$this->createAutor('Machado de Assis')], [$this->createAssunto('Clássicos')], '1234.56');

        $crawler = $client->request('GET', '/livro/dom-casmurro-'.$livro->getCodl());
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Dom Casmurro');
        self::assertSelectorTextContains('main', 'Machado de Assis');
        self::assertStringContainsString('R$ 1.234,56', str_replace("\u{A0}", ' ', $crawler->filter('main')->text()));
        self::assertCount(1, $crawler->filter('main picture source[media="(min-width: 768px)"]'));
    }

    public function testUnknownBookIs404WithCustomPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/livro/qualquer-999999');
        self::assertResponseStatusCodeSame(404);
    }

    public function testBookUrlWithoutOrWrongSlugRedirectsToCanonical(): void
    {
        $client = static::createClient();
        $livro = $this->createLivro('Memórias Póstumas', [$this->createAutor('Machado de Assis')], [$this->createAssunto('Clássicos')]);
        $canonical = '/livro/memorias-postumas-'.$livro->getCodl();

        foreach (['/livro/'.$livro->getCodl(), '/livro/outro-titulo-'.$livro->getCodl()] as $url) {
            $client->request('GET', $url);
            self::assertResponseRedirects($canonical, 301, $url);
        }
    }

    public function testAssuntoUrlWithoutOrWrongSlugRedirectsToCanonical(): void
    {
        $client = static::createClient();
        $id = $this->createAssunto('Biografia')->getCodAs();

        $client->request('GET', "/assunto/$id?page=2");
        self::assertResponseRedirects("/assunto/biografia-$id?page=2", 301);
        $client->request('GET', "/assunto/errado-$id");
        self::assertResponseRedirects("/assunto/biografia-$id", 301);
    }
}
