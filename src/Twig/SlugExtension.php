<?php

namespace App\Twig;

use Symfony\Component\String\Slugger\AsciiSlugger;
use Twig\Attribute\AsTwigFilter;

/** Gera o trecho legível das URLs públicas (ex.: "Ficção Científica" -> "ficcao-cientifica"). */
final class SlugExtension
{
    private const FALLBACK = 'item';

    #[AsTwigFilter('slug')]
    public function slug(string $text): string
    {
        $slug = (new AsciiSlugger('pt_BR'))->slug($text)->lower()->toString();

        return '' === $slug ? self::FALLBACK : $slug;
    }
}
