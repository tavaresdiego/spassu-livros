<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Converte o decimal do banco ("1234.56") para o formato brasileiro ("1.234,56") e vice-versa.
 * Aceita o texto vindo da máscara, com ou sem "R$".
 *
 * @implements DataTransformerInterface<string, string>
 */
final class BrlMoneyTransformer implements DataTransformerInterface
{
    /** decimal(10,2): até 8 dígitos inteiros */
    private const MAX_INTEGER_DIGITS = 8;

    public function transform(mixed $value): string
    {
        if (null === $value || '' === $value) {
            return '';
        }

        return number_format((float) $value, 2, ',', '.');
    }

    public function reverseTransform(mixed $value): ?string
    {
        $value = str_replace(['R$', "\u{A0}", ' '], '', trim((string) $value));
        if ('' === $value) {
            return null;
        }

        if (!preg_match('/^(\d{1,3}(?:\.\d{3})+|\d+)(?:,(\d{1,2}))?$/', $value, $m)) {
            throw new TransformationFailedException(sprintf('Valor inválido: "%s".', $value));
        }

        $integer = ltrim(str_replace('.', '', $m[1]), '0') ?: '0';
        if (strlen($integer) > self::MAX_INTEGER_DIGITS) {
            throw new TransformationFailedException('Valor acima do limite permitido.');
        }

        return $integer.'.'.str_pad($m[2] ?? '', 2, '0');
    }
}
