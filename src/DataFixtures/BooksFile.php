<?php

namespace App\DataFixtures;

use App\DataFixtures\Exception\InvalidBooksFileException;

/** Leitura de data/books.json, fonte dos dados iniciais. */
final class BooksFile
{
    public function __construct(private readonly string $path)
    {
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /** @return array{subjects: list<string>, books: list<array<string, mixed>>} */
    public function read(): array
    {
        if (!is_file($this->path)) {
            throw new InvalidBooksFileException(sprintf('Arquivo "%s" não encontrado.', $this->path));
        }

        try {
            $data = json_decode((string) file_get_contents($this->path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new InvalidBooksFileException(sprintf('JSON inválido em "%s": %s', $this->path, $e->getMessage()), 0, $e);
        }

        if (!is_array($data['subjects'] ?? null) || !is_array($data['books'] ?? null)) {
            throw new InvalidBooksFileException(sprintf('"%s" deve conter as chaves "subjects" e "books".', $this->path));
        }

        return $data;
    }
}
