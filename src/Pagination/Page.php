<?php

namespace App\Pagination;

/**
 * Uma página de resultados.
 *
 * @template T
 */
final readonly class Page
{
    /** @param list<T> $items */
    public function __construct(
        public array $items,
        public int $page,
        public int $perPage,
        public int $total,
        public int $pages,
    ) {
    }

    public function hasPrevious(): bool
    {
        return $this->page > 1;
    }

    public function hasNext(): bool
    {
        return $this->page < $this->pages;
    }
}
