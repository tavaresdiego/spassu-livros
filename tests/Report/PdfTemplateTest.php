<?php

namespace App\Tests\Report;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DomCrawler\Crawler;

/** Cabeçalho do template do PDF (o Dompdf desenha o SVG como vetor, então a checagem é no HTML). */
class PdfTemplateTest extends KernelTestCase
{
    public function testHeaderShowsProjectLogo(): void
    {
        $html = static::getContainer()->get('twig')->render('report/pdf.html.twig', [
            'autores' => [],
            'geradoEm' => new \DateTimeImmutable('2026-10-07 10:00'),
            'logo' => '/caminho/logo-spassu-livros.svg',
        ]);

        $logo = (new Crawler($html))->filter('.header img.header__logo');
        self::assertCount(1, $logo);
        self::assertSame('/caminho/logo-spassu-livros.svg', $logo->attr('src'));
        self::assertSame('Spassu Livros', $logo->attr('alt'));
    }
}
