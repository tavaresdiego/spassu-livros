<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;

class ErrorPagesTest extends KernelTestCase
{
    public function testError404Template(): void
    {
        self::bootKernel();
        $html = static::getContainer()->get(Environment::class)
            ->render('bundles/TwigBundle/Exception/error404.html.twig', ['status_code' => 404, 'status_text' => 'Not Found']);

        self::assertStringContainsString('Página não encontrada', $html);
        self::assertStringContainsString('href="/"', $html);
    }

    public function testError500Template(): void
    {
        self::bootKernel();
        $html = static::getContainer()->get(Environment::class)
            ->render('bundles/TwigBundle/Exception/error500.html.twig', ['status_code' => 500, 'status_text' => 'Internal Server Error']);

        self::assertStringContainsString('Algo deu errado', $html);
    }

    public function testErrorPagesUseLayoutWithSingleH1EmptyStateAndBackButton(): void
    {
        self::bootKernel();
        $twig = static::getContainer()->get(Environment::class);

        foreach (['error404' => 404, 'error500' => 500] as $template => $status) {
            $crawler = new \Symfony\Component\DomCrawler\Crawler($twig->render(
                sprintf('bundles/TwigBundle/Exception/%s.html.twig', $template),
                ['status_code' => $status, 'status_text' => 'Erro']
            ));

            self::assertCount(1, $crawler->filter('h1'), $template);
            self::assertCount(1, $crawler->filter('main .app-empty-state h1'), $template);
            self::assertCount(1, $crawler->filter('main a.btn[href="/"]'), $template);
            self::assertCount(1, $crawler->filter('a.app-skip-link[href="#conteudo"]'), $template);
            self::assertStringContainsString('Erro '.$status, $crawler->filter('.app-error__code')->text());
        }
    }
}
