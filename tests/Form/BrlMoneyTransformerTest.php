<?php

namespace App\Tests\Form;

use App\Form\DataTransformer\BrlMoneyTransformer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

class BrlMoneyTransformerTest extends TestCase
{
    public function testTransformsDecimalToBrazilianFormat(): void
    {
        $t = new BrlMoneyTransformer();
        self::assertSame('1.234,56', $t->transform('1234.56'));
        self::assertSame('0,90', $t->transform('0.90'));
        self::assertSame('', $t->transform(null));
    }

    #[DataProvider('validInputs')]
    public function testReverseTransformsMaskedInputToDecimal(string $input, string $expected): void
    {
        self::assertSame($expected, (new BrlMoneyTransformer())->reverseTransform($input));
    }

    /** @return iterable<array{string, string}> */
    public static function validInputs(): iterable
    {
        yield ['R$ 1.234,56', '1234.56'];
        yield ['1234,5', '1234.50'];
        yield ['59,90', '59.90'];
        yield ['10', '10.00'];
        yield ["R\$\u{A0}0,99", '0.99'];
        yield ['1.000.000,00', '1000000.00'];
    }

    public function testEmptyInputBecomesNull(): void
    {
        self::assertNull((new BrlMoneyTransformer())->reverseTransform(''));
        self::assertNull((new BrlMoneyTransformer())->reverseTransform('R$ '));
    }

    #[DataProvider('invalidInputs')]
    public function testRejectsInvalidInput(string $input): void
    {
        $this->expectException(TransformationFailedException::class);
        (new BrlMoneyTransformer())->reverseTransform($input);
    }

    /** @return iterable<array{string}> */
    public static function invalidInputs(): iterable
    {
        yield ['abc'];
        yield ['12,345'];
        yield ['1,2,3'];
        yield ['100000000,00'];
    }
}
