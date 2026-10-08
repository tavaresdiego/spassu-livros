<?php

namespace App\Tests\Controller;

use App\Tests\Support\BookFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ReportControllerTest extends WebTestCase
{
    use BookFactory;

    public function testReportListsBookWithTwoAutoresUnderBoth(): void
    {
        $client = static::createClient();
        $machado = $this->createAutor('Machado Relatório');
        $clarice = $this->createAutor('Clarice Relatório');
        $assunto = $this->createAssunto('Romance');
        $this->createLivro('Livro a Quatro Mãos', [$machado, $clarice], [$assunto], '1234.50');

        $crawler = $client->request('GET', '/relatorio');

        self::assertResponseIsSuccessful();
        foreach ([$machado, $clarice] as $autor) {
            $section = $crawler->filter(sprintf('section#autor-%d', $autor->getCodAu()));
            self::assertCount(1, $section);
            self::assertStringContainsString($autor->getNome(), $section->filter('h2')->text());
            $row = $section->filter('tbody tr')->reduce(fn ($tr) => str_contains($tr->text(), 'Livro a Quatro Mãos'));
            self::assertCount(1, $row);
            self::assertStringContainsString('Romance', $row->text());
            self::assertStringContainsString('1.234,50', $row->text());
            self::assertStringContainsString('1 livro', $section->filter('tfoot')->text());
        }
        self::assertCount(1, $crawler->filter('main a[href="/relatorio/pdf"]'));
    }

    public function testPdfOpensInline(): void
    {
        $client = static::createClient();
        $this->createLivro('Livro PDF', [$this->createAutor('Autor PDF')], [$this->createAssunto('Teste')]);

        $client->request('GET', '/relatorio/pdf');

        self::assertResponseIsSuccessful();
        $response = $client->getResponse();
        self::assertSame('application/pdf', $response->headers->get('Content-Type'));
        self::assertStringContainsString('inline; filename=relatorio-livros-por-autor-', $response->headers->get('Content-Disposition'));
        self::assertStringStartsWith('%PDF', (string) $response->getContent());
    }

    public function testMenuHasReportButtons(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        self::assertCount(1, $crawler->filter('header a[href="/relatorio"]'));
        self::assertCount(1, $crawler->filter('header a[href="/relatorio/pdf"][target="_blank"]'));
    }
}
