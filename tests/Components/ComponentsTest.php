<?php

namespace App\Tests\Components;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\UX\TwigComponent\Test\InteractsWithTwigComponents;

class ComponentsTest extends KernelTestCase
{
    use InteractsWithTwigComponents;

    public function testButtonWithHrefRendersLink(): void
    {
        $crawler = $this->renderTwigComponent('Ui:Button', ['href' => '/admin', 'variant' => 'outline-primary', 'size' => 'sm'], 'Abrir')->crawler();

        $link = $crawler->filter('a');
        self::assertCount(1, $link);
        self::assertCount(0, $crawler->filter('button'));
        self::assertSame('/admin', $link->attr('href'));
        self::assertStringContainsString('btn btn-outline-primary btn-sm', $link->attr('class'));
        self::assertSame('Abrir', trim($link->text()));
    }

    public function testButtonWithoutHrefRendersButtonWithIcon(): void
    {
        $crawler = $this->renderTwigComponent('Ui:Button', ['type' => 'submit', 'icon' => 'plus', 'disabled' => true, 'class' => 'extra'], 'Salvar')->crawler();

        $button = $crawler->filter('button');
        self::assertCount(1, $button);
        self::assertSame('submit', $button->attr('type'));
        self::assertNotNull($button->attr('disabled'));
        self::assertStringContainsString('btn-primary', $button->attr('class'));
        self::assertStringContainsString('extra', $button->attr('class'));
        self::assertCount(1, $button->filter('svg'));
    }

    public function testPictureRendersDesktopSourceAndMobileImg(): void
    {
        $crawler = $this->renderTwigComponent('Ui:Picture', [
            'mobile' => 'https://example.com/m.jpg',
            'desktop' => 'https://example.com/d.jpg',
            'alt' => 'Capa',
            'ratio' => '2/3',
        ])->crawler();

        self::assertSame('(min-width: 768px)', $crawler->filter('picture source')->attr('media'));
        self::assertSame('https://example.com/d.jpg', $crawler->filter('picture source')->attr('srcset'));
        self::assertSame('https://example.com/m.jpg', $crawler->filter('picture img')->attr('src'));
        self::assertSame('Capa', $crawler->filter('picture img')->attr('alt'));
        self::assertSame('lazy', $crawler->filter('picture img')->attr('loading'));
        self::assertStringContainsString('app-picture--2x3', $crawler->filter('picture')->attr('class'));
    }

    public function testPictureShowsPlaceholderWhenUrlsAreEmpty(): void
    {
        $crawler = $this->renderTwigComponent('Ui:Picture', ['mobile' => '', 'desktop' => null, 'alt' => 'Sem capa'])->crawler();

        self::assertCount(0, $crawler->filter('img'));
        $placeholder = $crawler->filter('.app-picture--placeholder');
        self::assertCount(1, $placeholder);
        self::assertSame('Sem capa', $placeholder->attr('aria-label'));
    }

    public function testPriceFormatsBrl(): void
    {
        $html = (string) $this->renderTwigComponent('Ui:Price', ['value' => '1234.56']);

        self::assertStringContainsString('R$ 1.234,56', str_replace("\u{A0}", ' ', $html));
    }

    public function testConfirmDeleteRendersFormWithCsrfToken(): void
    {
        self::bootKernel();
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        static::getContainer()->get(RequestStack::class)->push($request);

        $crawler = $this->renderTwigComponent('Ui:ConfirmDelete', [
            'action' => '/admin/autores/7/excluir',
            'tokenId' => 'delete-autor-7',
            'itemLabel' => 'Machado de Assis',
        ])->crawler();

        $form = $crawler->filter('form[method="post"][action="/admin/autores/7/excluir"]');
        self::assertCount(1, $form);
        $token = $form->filter('input[type="hidden"][name="_token"]')->attr('value');
        self::assertNotEmpty($token);
        self::assertTrue(static::getContainer()->get('security.csrf.token_manager')->isTokenValid(
            new \Symfony\Component\Security\Csrf\CsrfToken('delete-autor-7', $token)
        ));
        self::assertStringContainsString('Machado de Assis', $crawler->filter('.modal')->text());
        self::assertCount(1, $crawler->filter('[data-bs-toggle="modal"]'));
    }
}
