<?php

namespace App\Tests\Twig;

use App\Twig\SlugExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SlugExtensionTest extends TestCase
{
    /** @return iterable<string, array{string, string}> */
    public static function cases(): iterable
    {
        yield 'acentos' => ['Ficção Científica', 'ficcao-cientifica'];
        yield 'pontuação' => ['O Senhor dos Anéis: A Sociedade do Anel', 'o-senhor-dos-aneis-a-sociedade-do-anel'];
        yield 'espaços nas pontas' => ['  Biografia ', 'biografia'];
        yield 'sem letras' => ['?!', 'item'];
    }

    #[DataProvider('cases')]
    public function testSlug(string $text, string $expected): void
    {
        self::assertSame($expected, (new SlugExtension())->slug($text));
    }
}
