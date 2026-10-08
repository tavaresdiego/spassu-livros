<?php

namespace App\Entity;

use App\Repository\AssuntoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AssuntoRepository::class)]
#[ORM\Table(name: 'Assunto')]
class Assunto
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'codAs', type: Types::INTEGER)]
    private ?int $codAs = null;

    #[ORM\Column(name: 'Descricao', length: 20)]
    #[Assert\NotBlank(message: 'Informe a descrição do assunto.')]
    #[Assert\Length(max: 20, maxMessage: 'A descrição deve ter no máximo {{ limit }} caracteres.')]
    private string $descricao = '';

    /** @var Collection<int, Livro> */
    #[ORM\ManyToMany(targetEntity: Livro::class, mappedBy: 'assuntos')]
    private Collection $livros;

    public function __construct()
    {
        $this->livros = new ArrayCollection();
    }

    public function getCodAs(): ?int
    {
        return $this->codAs;
    }

    public function getDescricao(): string
    {
        return $this->descricao;
    }

    public function setDescricao(string $descricao): static
    {
        $this->descricao = $descricao;

        return $this;
    }

    /** @return Collection<int, Livro> */
    public function getLivros(): Collection
    {
        return $this->livros;
    }

    public function __toString(): string
    {
        return $this->descricao;
    }
}
