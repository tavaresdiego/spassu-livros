<?php

namespace App\Entity;

use App\Repository\LivroRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LivroRepository::class)]
#[ORM\Table(name: 'Livro')]
#[ORM\Index(name: 'idx_livro_titulo', columns: ['Titulo'])]
class Livro
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'Codl', type: Types::INTEGER)]
    private ?int $codl = null;

    #[ORM\Column(name: 'Titulo', length: 40)]
    #[Assert\NotBlank(message: 'Informe o título.')]
    #[Assert\Length(max: 40, maxMessage: 'O título deve ter no máximo {{ limit }} caracteres.')]
    private string $titulo = '';

    #[ORM\Column(name: 'Editora', length: 40)]
    #[Assert\Length(max: 40, maxMessage: 'A editora deve ter no máximo {{ limit }} caracteres.')]
    private string $editora = '';

    #[ORM\Column(name: 'Edicao', type: Types::INTEGER)]
    #[Assert\GreaterThanOrEqual(1, message: 'A edição deve ser maior ou igual a 1.')]
    private int $edicao = 1;

    #[ORM\Column(name: 'AnoPublicacao', length: 4)]
    #[Assert\Regex(pattern: '/^\d{4}$/', message: 'O ano de publicação deve ter 4 dígitos.')]
    private string $anoPublicacao = '';

    #[ORM\Column(name: 'Valor', type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotNull(message: 'Informe o valor.')]
    #[Assert\PositiveOrZero(message: 'O valor não pode ser negativo.')]
    private ?string $valor = null;

    #[ORM\Column(name: 'ImagemMobileUrl', length: 500, nullable: true)]
    #[Assert\Length(max: 500)]
    #[Assert\Url(message: 'Informe uma URL válida.', requireTld: true)]
    private ?string $imagemMobileUrl = null;

    #[ORM\Column(name: 'ImagemDesktopUrl', length: 500, nullable: true)]
    #[Assert\Length(max: 500)]
    #[Assert\Url(message: 'Informe uma URL válida.', requireTld: true)]
    private ?string $imagemDesktopUrl = null;

    #[ORM\Column(name: 'Descricao', type: Types::TEXT, nullable: true)]
    private ?string $descricao = null;

    /** @var Collection<int, Autor> */
    #[ORM\ManyToMany(targetEntity: Autor::class, inversedBy: 'livros')]
    #[ORM\JoinTable(name: 'Livro_Autor')]
    #[ORM\JoinColumn(name: 'Livro_Codl', referencedColumnName: 'Codl', nullable: false)]
    #[ORM\InverseJoinColumn(name: 'Autor_CodAu', referencedColumnName: 'CodAu', nullable: false)]
    #[Assert\Count(min: 1, minMessage: 'Selecione pelo menos um autor.')]
    private Collection $autores;

    /** @var Collection<int, Assunto> */
    #[ORM\ManyToMany(targetEntity: Assunto::class, inversedBy: 'livros')]
    #[ORM\JoinTable(name: 'Livro_Assunto')]
    #[ORM\JoinColumn(name: 'Livro_Codl', referencedColumnName: 'Codl', nullable: false)]
    #[ORM\InverseJoinColumn(name: 'Assunto_codAs', referencedColumnName: 'codAs', nullable: false)]
    #[Assert\Count(min: 1, minMessage: 'Selecione pelo menos um assunto.')]
    private Collection $assuntos;

    public function __construct()
    {
        $this->autores = new ArrayCollection();
        $this->assuntos = new ArrayCollection();
    }

    public function getCodl(): ?int
    {
        return $this->codl;
    }

    public function getTitulo(): string
    {
        return $this->titulo;
    }

    public function setTitulo(string $titulo): static
    {
        $this->titulo = $titulo;

        return $this;
    }

    public function getEditora(): string
    {
        return $this->editora;
    }

    public function setEditora(string $editora): static
    {
        $this->editora = $editora;

        return $this;
    }

    public function getEdicao(): int
    {
        return $this->edicao;
    }

    public function setEdicao(int $edicao): static
    {
        $this->edicao = $edicao;

        return $this;
    }

    public function getAnoPublicacao(): string
    {
        return $this->anoPublicacao;
    }

    public function setAnoPublicacao(string $anoPublicacao): static
    {
        $this->anoPublicacao = $anoPublicacao;

        return $this;
    }

    public function getValor(): ?string
    {
        return $this->valor;
    }

    public function setValor(?string $valor): static
    {
        $this->valor = $valor;

        return $this;
    }

    public function getImagemMobileUrl(): ?string
    {
        return $this->imagemMobileUrl;
    }

    public function setImagemMobileUrl(?string $imagemMobileUrl): static
    {
        $this->imagemMobileUrl = $imagemMobileUrl;

        return $this;
    }

    public function getImagemDesktopUrl(): ?string
    {
        return $this->imagemDesktopUrl;
    }

    public function setImagemDesktopUrl(?string $imagemDesktopUrl): static
    {
        $this->imagemDesktopUrl = $imagemDesktopUrl;

        return $this;
    }

    public function getDescricao(): ?string
    {
        return $this->descricao;
    }

    public function setDescricao(?string $descricao): static
    {
        $this->descricao = $descricao;

        return $this;
    }

    /** @return Collection<int, Autor> */
    public function getAutores(): Collection
    {
        return $this->autores;
    }

    public function addAutor(Autor $autor): static
    {
        if (!$this->autores->contains($autor)) {
            $this->autores->add($autor);
            $autor->getLivros()->add($this);
        }

        return $this;
    }

    public function removeAutor(Autor $autor): static
    {
        if ($this->autores->removeElement($autor)) {
            $autor->getLivros()->removeElement($this);
        }

        return $this;
    }

    /** @return Collection<int, Assunto> */
    public function getAssuntos(): Collection
    {
        return $this->assuntos;
    }

    public function addAssunto(Assunto $assunto): static
    {
        if (!$this->assuntos->contains($assunto)) {
            $this->assuntos->add($assunto);
            $assunto->getLivros()->add($this);
        }

        return $this;
    }

    public function removeAssunto(Assunto $assunto): static
    {
        if ($this->assuntos->removeElement($assunto)) {
            $assunto->getLivros()->removeElement($this);
        }

        return $this;
    }
}
