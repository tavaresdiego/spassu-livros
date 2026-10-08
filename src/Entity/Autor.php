<?php

namespace App\Entity;

use App\Repository\AutorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AutorRepository::class)]
#[ORM\Table(name: 'Autor')]
#[ORM\Index(name: 'idx_autor_nome', columns: ['Nome'])]
class Autor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'CodAu', type: Types::INTEGER)]
    private ?int $codAu = null;

    #[ORM\Column(name: 'Nome', length: 40)]
    #[Assert\NotBlank(message: 'Informe o nome do autor.')]
    #[Assert\Length(max: 40, maxMessage: 'O nome deve ter no máximo {{ limit }} caracteres.')]
    private string $nome = '';

    /** @var Collection<int, Livro> */
    #[ORM\ManyToMany(targetEntity: Livro::class, mappedBy: 'autores')]
    private Collection $livros;

    public function __construct()
    {
        $this->livros = new ArrayCollection();
    }

    public function getCodAu(): ?int
    {
        return $this->codAu;
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function setNome(string $nome): static
    {
        $this->nome = $nome;

        return $this;
    }

    /** @return Collection<int, Livro> */
    public function getLivros(): Collection
    {
        return $this->livros;
    }

    public function __toString(): string
    {
        return $this->nome;
    }
}
