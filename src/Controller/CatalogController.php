<?php

namespace App\Controller;

use App\Entity\Assunto;
use App\Repository\AssuntoRepository;
use App\Repository\LivroRepository;
use App\Twig\SlugExtension;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

final class CatalogController extends AbstractController
{
    private const BOOKS_PER_ASSUNTO_ON_HOME = 6;

    public function __construct(
        private readonly LivroRepository $livroRepository,
        private readonly SlugExtension $slugger,
    ) {
    }

    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function home(AssuntoRepository $assuntoRepository): Response
    {
        return $this->render('catalog/home.html.twig', [
            'assuntos' => $assuntoRepository->findAllOrdered('DESC'),
            'livrosPorAssunto' => $this->livroRepository->findFirstByAssunto(self::BOOKS_PER_ASSUNTO_ON_HOME),
            'totais' => $assuntoRepository->countLivrosByAssunto(),
        ]);
    }

    #[Route('/busca', name: 'app_search', methods: ['GET'])]
    public function search(Request $request, #[MapQueryParameter] string $q = ''): Response
    {
        $term = trim($q);

        return $this->render('catalog/search.html.twig', [
            'term' => $term,
            'page' => '' === $term ? null : $this->livroRepository->search($term, $request->query->getInt('page', 1)),
        ]);
    }

    #[Route('/assunto/{slug}-{id}', name: 'app_subject', requirements: ['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*', 'id' => '\d+'], methods: ['GET'])]
    #[Route('/assunto/{id}', name: 'app_subject_legacy', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function subject(Request $request, #[MapEntity(id: 'id')] Assunto $assunto, ?string $slug = null): Response
    {
        $canonical = $this->slugger->slug($assunto->getDescricao());
        if ($slug !== $canonical) {
            return $this->redirectToCanonical('app_subject', $canonical, $assunto->getCodAs(), $request);
        }

        return $this->render('catalog/subject.html.twig', [
            'assunto' => $assunto,
            'page' => $this->livroRepository->paginateByAssunto($assunto, $request->query->getInt('page', 1)),
        ]);
    }

    #[Route('/livro/{slug}-{id}', name: 'app_book_show', requirements: ['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*', 'id' => '\d+'], methods: ['GET'])]
    #[Route('/livro/{id}', name: 'app_book_show_legacy', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Request $request, int $id, ?string $slug = null): Response
    {
        $livro = $this->livroRepository->findOneWithRelations($id)
            ?? throw $this->createNotFoundException('Livro não encontrado.');

        $canonical = $this->slugger->slug($livro->getTitulo());
        if ($slug !== $canonical) {
            return $this->redirectToCanonical('app_book_show', $canonical, $id, $request);
        }

        return $this->render('catalog/show.html.twig', ['livro' => $livro]);
    }

    /** URL sem slug ou com slug desatualizado: 301 para a canônica, preservando a query string. */
    private function redirectToCanonical(string $route, string $slug, int $id, Request $request): RedirectResponse
    {
        return $this->redirectToRoute($route, ['slug' => $slug, 'id' => $id] + $request->query->all(), Response::HTTP_MOVED_PERMANENTLY);
    }
}
