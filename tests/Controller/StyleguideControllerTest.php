<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class StyleguideControllerTest extends WebTestCase
{
    public function testStyleguideRespondsOutsideProduction(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/styleguide');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Styleguide');
        self::assertCount(1, $crawler->filter('h1'));
        foreach (['tokens', 'logo', 'botoes', 'badges', 'alertas', 'precos', 'imagens', 'livros', 'formularios', 'navegacao', 'estados', 'layout'] as $section) {
            self::assertCount(1, $crawler->filter('section#'.$section), $section);
        }
        self::assertGreaterThan(0, $crawler->filter('.app-book-card picture, .app-book-card .app-picture--placeholder')->count());
    }

    public function testStyleguideRouteIsNotRegisteredInProduction(): void
    {
        $routeAttribute = (new \ReflectionMethod(\App\Controller\StyleguideController::class, 'index'))
            ->getAttributes(\Symfony\Component\Routing\Attribute\Route::class)[0]->newInstance();

        self::assertSame(['dev', 'test'], $routeAttribute->envs);
    }
}
