<?php

namespace App\Tests\Components;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\UX\TwigComponent\Test\InteractsWithTwigComponents;

/** Variantes e estados dos componentes de UI (a base está em ComponentsTest). */
class UiComponentsTest extends KernelTestCase
{
    use InteractsWithTwigComponents;

    public function testIconOnlyButtonHasAccessibleNameAndHiddenText(): void
    {
        $crawler = $this->renderTwigComponent('Ui:Button', ['icon' => 'trash', 'iconOnly' => true, 'label' => 'Excluir livro'], 'Excluir')->crawler();

        $button = $crawler->filter('button');
        self::assertSame('Excluir livro', $button->attr('aria-label'));
        self::assertStringContainsString('app-btn--icon-only', $button->attr('class'));
        self::assertSame('Excluir', $button->filter('.visually-hidden')->text());
        self::assertSame('true', $button->filter('svg')->attr('aria-hidden'));
    }

    public function testLogoRendersProjectSvgWithAccessibleName(): void
    {
        $crawler = $this->renderTwigComponent('Ui:Logo', ['class' => 'app-brand__logo'])->crawler();

        $img = $crawler->filter('img');
        self::assertSame('/images/logo-spassu-livros.svg', $img->attr('src'));
        self::assertSame('Spassu Livros', $img->attr('alt'));
        self::assertSame('200', $img->attr('width'));
        self::assertSame('74', $img->attr('height'));
        self::assertStringContainsString('app-logo', $img->attr('class'));
        self::assertStringContainsString('app-brand__logo', $img->attr('class'));
    }

    public function testDisabledLinkButtonIsNotFocusable(): void
    {
        $crawler = $this->renderTwigComponent('Ui:Button', ['href' => '/admin', 'disabled' => true], 'Abrir')->crawler();

        $link = $crawler->filter('a');
        self::assertSame('#', $link->attr('href'));
        self::assertSame('true', $link->attr('aria-disabled'));
        self::assertSame('-1', $link->attr('tabindex'));
        self::assertStringContainsString('disabled', $link->attr('class'));
    }

    public function testPictureHasDimensionsFallbackAndDefaultAlt(): void
    {
        $crawler = $this->renderTwigComponent('Ui:Picture', ['mobile' => 'https://example.com/m.jpg', 'alt' => '', 'ratio' => '2/3'])->crawler();

        $img = $crawler->filter('picture img');
        self::assertSame('Imagem', $img->attr('alt'), 'alt nunca fica vazio');
        self::assertSame('400', $img->attr('width'));
        self::assertSame('600', $img->attr('height'));
        self::assertSame('lazy', $img->attr('loading'));
        self::assertCount(0, $crawler->filter('picture source'), 'sem URL desktop não há <source>');
        self::assertCount(1, $crawler->filter('picture .app-picture__fallback[aria-hidden="true"]'));
    }

    public function testEagerPictureSkipsLazyLoading(): void
    {
        $crawler = $this->renderTwigComponent('Ui:Picture', ['mobile' => 'https://example.com/m.jpg', 'desktop' => 'https://example.com/d.jpg', 'alt' => 'Capa', 'eager' => true])->crawler();

        $img = $crawler->filter('picture img');
        self::assertSame('eager', $img->attr('loading'));
        self::assertSame('high', $img->attr('fetchpriority'));
    }

    public function testPictureFallsBackToDefaultRatio(): void
    {
        $crawler = $this->renderTwigComponent('Ui:Picture', ['mobile' => 'https://example.com/m.jpg', 'ratio' => '5/7'])->crawler();

        self::assertStringContainsString('app-picture--2x3', $crawler->filter('picture')->attr('class'));
    }

    public function testPriceVariantsAndEmptyValue(): void
    {
        $large = $this->renderTwigComponent('Ui:Price', ['value' => '99.9', 'size' => 'lg'])->crawler()->filter('.app-price');
        self::assertStringContainsString('app-price--lg', $large->attr('class'));
        self::assertSame('R$ 99,90', str_replace("\u{A0}", ' ', trim($large->text())));

        $empty = $this->renderTwigComponent('Ui:Price', ['value' => ''])->crawler()->filter('.app-price');
        self::assertSame('—', trim($empty->text()));
        self::assertStringContainsString('app-price--muted', $empty->attr('class'));
    }

    public function testConfirmDeleteButtonShowsShortTextAndNamesTheItem(): void
    {
        self::bootKernel();
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        static::getContainer()->get(RequestStack::class)->push($request);

        $crawler = $this->renderTwigComponent('Ui:ConfirmDelete', [
            'action' => '/admin/livros/3/excluir',
            'tokenId' => 'delete-livro-3',
            'itemLabel' => 'Dom Casmurro',
        ])->crawler();

        $trigger = $crawler->filter('[data-bs-toggle="modal"]');
        self::assertSame('Excluir', trim($trigger->text()));
        self::assertSame('Excluir Dom Casmurro', $trigger->attr('aria-label'));
        self::assertSame('#confirm-delete-livro-3', $trigger->attr('data-bs-target'));

        $modal = $crawler->filter('#confirm-delete-livro-3');
        self::assertSame('confirm-delete-livro-3-title', $modal->attr('aria-labelledby'));
        self::assertCount(1, $modal->filter('button.btn-close[data-bs-dismiss="modal"][aria-label="Fechar"]'));
        self::assertCount(1, $modal->filter('button[type="submit"]'));
    }

    public function testAlertRolesIconsAndDismiss(): void
    {
        $danger = $this->renderTwigComponent('Ui:Alert', ['type' => 'danger', 'dismissible' => true], 'Falhou')->crawler()->filter('.alert');
        self::assertSame('alert', $danger->attr('role'));
        self::assertStringContainsString('alert-danger', $danger->attr('class'));
        self::assertStringContainsString('alert-dismissible', $danger->attr('class'));
        self::assertCount(1, $danger->filter('svg.app-alert__icon'));
        self::assertCount(1, $danger->filter('button.btn-close[data-bs-dismiss="alert"]'));
        self::assertSame('Falhou', trim($danger->filter('.app-alert__content')->text()));

        $success = $this->renderTwigComponent('Ui:Alert', ['type' => 'success'], 'Salvo')->crawler()->filter('.alert');
        self::assertSame('status', $success->attr('role'));
        self::assertCount(0, $success->filter('.btn-close'));
    }

    public function testAlertWithUnknownTypeFallsBackToInfo(): void
    {
        $alert = $this->renderTwigComponent('Ui:Alert', ['type' => 'notice'], 'Aviso')->crawler()->filter('.alert');

        self::assertStringContainsString('alert-info', $alert->attr('class'));
    }

    public function testFormInputLinksHelpAndErrorToField(): void
    {
        $crawler = $this->renderTwigComponent('Form:Input', ['name' => 'nome', 'label' => 'Nome', 'help' => 'Até 40', 'error' => 'Obrigatório'])->crawler();

        $input = $crawler->filter('input#input-nome');
        self::assertSame('input-nome-help input-nome-error', $input->attr('aria-describedby'));
        self::assertSame('true', $input->attr('aria-invalid'));
        self::assertSame('Obrigatório', $crawler->filter('#input-nome-error')->text());
        self::assertSame('input-nome', $crawler->filter('label')->attr('for'));
    }
}
