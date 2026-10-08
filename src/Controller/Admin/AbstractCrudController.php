<?php

namespace App\Controller\Admin;

use App\Persistence\EntityWriter;
use App\Persistence\Exception\DuplicateEntityException;
use App\Persistence\Exception\EntityInUseException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/** Fluxo comum de formulário e exclusão dos CRUDs do admin. */
abstract class AbstractCrudController extends AbstractController
{
    public function __construct(protected readonly EntityWriter $writer)
    {
    }

    /**
     * @param class-string             $formType
     * @param array<string, mixed>     $context variáveis extras para o template
     */
    protected function handleForm(Request $request, object $entity, string $formType, string $template, string $indexRoute, string $successMessage, array $context = []): Response
    {
        $form = $this->createForm($formType, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->writer->save($entity);
                $this->addFlash('success', $successMessage);

                return $this->redirectToRoute($indexRoute);
            } catch (DuplicateEntityException) {
                $form->addError(new FormError('Já existe um registro com esses dados.'));
            }
        }

        return $this->render($template, ['form' => $form, 'entity' => $entity] + $context, new Response(status: $form->isSubmitted() ? 422 : 200));
    }

    /**
     * @param bool $guardedByForeignKeys true para Autor/Assunto: o banco recusa a exclusão se houver livros vinculados
     */
    protected function handleDelete(Request $request, object $entity, string $tokenId, string $indexRoute, string $successMessage, string $inUseMessage, bool $guardedByForeignKeys = false): Response
    {
        if (!$this->isCsrfTokenValid($tokenId, $request->getPayload()->getString('_token'))) {
            $this->addFlash('danger', 'Token de segurança inválido. Recarregue a página e tente novamente.');

            return $this->redirectToRoute($indexRoute);
        }

        try {
            $guardedByForeignKeys ? $this->writer->removeIfUnreferenced($entity) : $this->writer->remove($entity);
            $this->addFlash('success', $successMessage);
        } catch (EntityInUseException) {
            $this->addFlash('danger', $inUseMessage);
        }

        return $this->redirectToRoute($indexRoute);
    }
}
