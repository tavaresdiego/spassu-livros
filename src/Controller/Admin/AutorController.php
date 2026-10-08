<?php

namespace App\Controller\Admin;

use App\Entity\Autor;
use App\Form\AutorType;
use App\Repository\AutorRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/autores', name: 'admin_autor_')]
final class AutorController extends AbstractCrudController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, AutorRepository $repository): Response
    {
        $filter = trim($request->query->getString('q'));

        return $this->render('admin/autor/index.html.twig', [
            'page' => $repository->paginate($request->query->getInt('page', 1), filter: $filter),
            'filter' => $filter,
        ]);
    }

    #[Route('/novo', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->handleForm($request, new Autor(), AutorType::class, 'admin/autor/form.html.twig', 'admin_autor_index', 'Autor cadastrado com sucesso.');
    }

    #[Route('/{id}/editar', name: 'edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, #[MapEntity(id: 'id')] Autor $autor): Response
    {
        return $this->handleForm($request, $autor, AutorType::class, 'admin/autor/form.html.twig', 'admin_autor_index', 'Autor atualizado com sucesso.');
    }

    #[Route('/{id}/excluir', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, #[MapEntity(id: 'id')] Autor $autor): Response
    {
        return $this->handleDelete(
            $request,
            $autor,
            'delete-autor-'.$autor->getCodAu(),
            'admin_autor_index',
            'Autor excluído com sucesso.',
            sprintf('O autor "%s" não pode ser excluído porque está vinculado a livros. Remova-o dos livros antes.', $autor->getNome()),
            guardedByForeignKeys: true,
        );
    }
}
