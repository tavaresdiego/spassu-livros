<?php

namespace App\Tests\Controller;

use App\Tests\Support\BookFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/** Estrutura comum das páginas: h1 único, skip link, busca, imagens dos cards e navegação acessível. */
class PageStructureTest extends WebTestCase
{
    use BookFactory;

    private KernelBrowser $client;
    private int $livroId;
    private int $assuntoId;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $autor = $this->createAutor('Clarice Lispector');
        $assunto = $this->createAssunto('Romance');
        $this->livroId = $this->createLivro('A hora da estrela', [$autor], [$assunto])->getCodl();
        $this->assuntoId = $assunto->getCodAs();
    }

    /** @return iterable<string, array{string}> */
    public static function pages(): iterable
    {
        yield 'home' => ['/'];
        yield 'busca' => ['/busca?q=estrela'];
        yield 'assunto' => ['/assunto/romance-{assunto}'];
        yield 'livro' => ['/livro/a-hora-da-estrela-{livro}'];
        yield 'relatório' => ['/relatorio'];
        yield 'admin livros' => ['/admin/livros'];
        yield 'admin autores' => ['/admin/autores'];
        yield 'admin assuntos' => ['/admin/assuntos'];
        yield 'novo livro' => ['/admin/livros/novo'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('pages')]
    public function testPageHasSingleH1SkipLinkAndSearchForm(string $url): void
    {
        $crawler = $this->get($url);

        self::assertCount(1, $crawler->filter('h1'), 'um único h1');
        $skip = $crawler->filter('body > a.app-skip-link');
        self::assertCount(1, $skip);
        self::assertSame('#conteudo', $skip->attr('href'));
        self::assertSame('Ir para o conteúdo', trim($skip->text()));
        self::assertCount(1, $crawler->filter('main#conteudo'));
        self::assertGreaterThanOrEqual(1, $crawler->filter('form[role="search"][action="/busca"] input[name="q"]')->count());
        self::assertSame('pt-BR', $crawler->filter('html')->attr('lang'));
        self::assertNotEmpty($crawler->filter('meta[name="description"]')->attr('content'));
        self::assertStringStartsWith('data:image/svg+xml', $crawler->filter('link[rel="icon"]')->attr('href'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('pages')]
    public function testFieldsHaveLabelsAndIconButtonsHaveNames(string $url): void
    {
        $crawler = $this->get($url);

        $crawler->filter('input:not([type="hidden"]), select, textarea')->each(function (Crawler $field) use ($crawler): void {
            $id = $field->attr('id');
            self::assertNotNull($id, 'campo sem id: '.$field->attr('name'));
            self::assertCount(1, $crawler->filter(sprintf('label[for="%s"]', $id)), 'campo sem label: '.$id);
        });
        $crawler->filter('button, a')->each(function (Crawler $control): void {
            if ('' === trim($control->text(''))) {
                self::assertNotEmpty($control->attr('aria-label'), 'controle sem nome acessível: '.$control->outerHtml());
            }
        });
    }

    public function testBookCardsUsePictureWithDesktopSourceAndAlt(): void
    {
        foreach (['/', '/assunto/romance-'.$this->assuntoId, '/busca?q=estrela'] as $url) {
            $cards = $this->get($url)->filter('.app-book-card');
            self::assertGreaterThan(0, $cards->count(), $url);
            $cards->each(function (Crawler $card) use ($url): void {
                self::assertCount(1, $card->filter('picture source[media="(min-width: 768px)"]'), $url);
                self::assertStringStartsWith('Capa do livro ', $card->filter('picture img')->attr('alt'), $url);
            });
        }
    }

    public function testHeaderBrandUsesProjectLogo(): void
    {
        $brand = $this->get('/')->filter('header a.app-brand');
        self::assertSame('/', $brand->attr('href'));
        self::assertSame('/images/logo-spassu-livros.svg', $brand->filter('img.app-logo')->attr('src'));
        self::assertSame('Spassu Livros', $brand->filter('img.app-logo')->attr('alt'));
        self::assertCount(0, $brand->filter('svg'), 'o ícone genérico foi substituído pela logo');
    }

    public function testNavigationMarksCurrentPage(): void
    {
        $crawler = $this->get('/assunto/romance-'.$this->assuntoId);
        $current = $crawler->filter('header [aria-current="page"]');
        self::assertCount(1, $current);
        self::assertSame('/assunto/romance-'.$this->assuntoId, $current->attr('href'));
        self::assertCount(1, $crawler->filter('.breadcrumb [aria-current="page"]'));

        $crawler = $this->get('/admin/livros');
        self::assertSame('/admin/livros', $crawler->filter('header [aria-current="page"]')->attr('href'));
    }

    public function testSearchTermIsEscaped(): void
    {
        $this->client->request('GET', '/busca', ['q' => '<script>alert(1)</script>']);

        $html = (string) $this->client->getResponse()->getContent();
        self::assertStringNotContainsString('<script>alert(1)</script>', $html);
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    public function testReportOpensPdfInNewTabWithoutHtmlPrintButton(): void
    {
        $crawler = $this->get('/relatorio');

        self::assertCount(1, $crawler->filter('main a[href="/relatorio/pdf"][target="_blank"][rel~="noopener"]'));
        self::assertCount(0, $crawler->filter('[data-print]'));
    }

    public function testValidationErrorsAreLinkedToFields(): void
    {
        $crawler = $this->get('/admin/autores/novo');
        $form = $crawler->selectButton('Salvar')->form();
        $crawler = $this->client->submit($form, ['autor[nome]' => '']);

        $input = $crawler->filter('input[name="autor[nome]"]');
        self::assertSame('true', $input->attr('aria-invalid'));
        $describedBy = explode(' ', (string) $input->attr('aria-describedby'));
        self::assertNotEmpty(array_filter($describedBy));
        foreach ($describedBy as $id) {
            self::assertCount(1, $crawler->filter('#'.$id));
        }
    }

    private function get(string $url): Crawler
    {
        $url = strtr($url, ['{livro}' => $this->livroId, '{assunto}' => $this->assuntoId]);
        $crawler = $this->client->request('GET', $url);
        self::assertResponseIsSuccessful($url);

        return $crawler;
    }
}
