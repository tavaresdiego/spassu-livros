<?php

namespace App\Filter;

use Symfony\Component\HttpFoundation\Request;

/** Critérios do filtro da listagem de livros no admin (?titulo=&autor=&assunto=&editora=). */
final readonly class LivroFilter
{
    public function __construct(
        public string $titulo = '',
        public string $autor = '',
        public ?int $assunto = null,
        public string $editora = '',
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $assunto = $request->query->getString('assunto');

        return new self(
            trim($request->query->getString('titulo')),
            trim($request->query->getString('autor')),
            ctype_digit($assunto) ? (int) $assunto : null,
            trim($request->query->getString('editora')),
        );
    }

    public function isEmpty(): bool
    {
        return [] === $this->toQuery();
    }

    /** @return array<string, string|int> só os critérios preenchidos, para links de paginação */
    public function toQuery(): array
    {
        return array_filter(
            ['titulo' => $this->titulo, 'autor' => $this->autor, 'assunto' => $this->assunto, 'editora' => $this->editora],
            fn ($v) => null !== $v && '' !== $v,
        );
    }
}
