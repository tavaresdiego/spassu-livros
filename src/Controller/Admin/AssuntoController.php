<?php

namespace App\Controller\Admin;

use App\Entity\Assunto;
use App\Form\AssuntoType;
use App\Repository\AssuntoRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/assuntos', name: 'admin_assunto_')]
final class AssuntoController extends AbstractCrudController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, AssuntoRepository $repository): Response
    {
        $filter = trim($request->query->getString('q'));

        return $this->render('admin/assunto/index.html.twig', [
            'page' => $repository->paginate($request->query->getInt('page', 1), filter: $filter),
            'filter' => $filter,
        ]);
    }

    #[Route('/novo', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->handleForm($request, new Assunto(), AssuntoType::class, 'admin/assunto/form.html.twig', 'admin_assunto_index', 'Assunto cadastrado com sucesso.');
    }

    #[Route('/{id}/editar', name: 'edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, #[MapEntity(id: 'id')] Assunto $assunto): Response
    {
        return $this->handleForm($request, $assunto, AssuntoType::class, 'admin/assunto/form.html.twig', 'admin_assunto_index', 'Assunto atualizado com sucesso.');
    }

    #[Route('/{id}/excluir', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, #[MapEntity(id: 'id')] Assunto $assunto): Response
    {
        return $this->handleDelete(
            $request,
            $assunto,
            'delete-assunto-'.$assunto->getCodAs(),
            'admin_assunto_index',
            'Assunto excluído com sucesso.',
            sprintf('O assunto "%s" não pode ser excluído porque está vinculado a livros. Remova-o dos livros antes.', $assunto->getDescricao()),
            guardedByForeignKeys: true,
        );
    }
}
