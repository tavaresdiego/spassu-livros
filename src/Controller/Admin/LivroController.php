<?php

namespace App\Controller\Admin;

use App\Entity\Livro;
use App\Filter\LivroFilter;
use App\Form\LivroType;
use App\Repository\AssuntoRepository;
use App\Repository\LivroRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/livros', name: 'admin_livro_')]
final class LivroController extends AbstractCrudController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, LivroRepository $repository, AssuntoRepository $assuntos): Response
    {
        $filter = LivroFilter::fromRequest($request);

        return $this->render('admin/livro/index.html.twig', [
            'page' => $repository->paginateWithRelations($request->query->getInt('page', 1), filter: $filter),
            'filter' => $filter,
            'assuntos' => $assuntos->findAllOrdered(),
        ]);
    }

    #[Route('/novo', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->handleForm($request, new Livro(), LivroType::class, 'admin/livro/form.html.twig', 'admin_livro_index', 'Livro cadastrado com sucesso.');
    }

    #[Route('/{id}/editar', name: 'edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, #[MapEntity(id: 'id')] Livro $livro): Response
    {
        return $this->handleForm($request, $livro, LivroType::class, 'admin/livro/form.html.twig', 'admin_livro_index', 'Livro atualizado com sucesso.');
    }

    #[Route('/{id}/excluir', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, #[MapEntity(id: 'id')] Livro $livro): Response
    {
        return $this->handleDelete(
            $request,
            $livro,
            'delete-livro-'.$livro->getCodl(),
            'admin_livro_index',
            'Livro excluído com sucesso.',
            sprintf('O livro "%s" não pode ser excluído.', $livro->getTitulo()),
        );
    }
}
