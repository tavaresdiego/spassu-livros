<?php

namespace App\Twig;

use App\Entity\Assunto;
use App\Repository\AssuntoRepository;
use Twig\Attribute\AsTwigFunction;

/** Assuntos exibidos na navegação principal. */
final class NavigationExtension
{
    /** @var list<Assunto>|null */
    private ?array $assuntos = null;

    public function __construct(private readonly AssuntoRepository $assuntoRepository)
    {
    }

    /** @return list<Assunto> */
    #[AsTwigFunction('nav_assuntos')]
    public function navAssuntos(): array
    {
        return $this->assuntos ??= $this->assuntoRepository->findAllOrdered();
    }
}
