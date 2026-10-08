<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Documentação viva dos componentes. A rota só existe fora de produção (dev e test). */
final class StyleguideController extends AbstractController
{
    #[Route('/styleguide', name: 'app_styleguide', methods: ['GET'], env: ['dev', 'test'])]
    public function index(Request $request): Response
    {
        // Dados de exemplo em arrays (não tocam o banco): o Twig lê book.titulo igual a um Livro.
        $assuntos = [['codAs' => 1, 'descricao' => 'Romance'], ['codAs' => 2, 'descricao' => 'Clássicos']];
        $book = [
            'codl' => 1,
            'titulo' => 'Dom Casmurro',
            'autores' => [['nome' => 'Machado de Assis']],
            'assuntos' => $assuntos,
            'valor' => '39.90',
            'imagemMobileUrl' => null,
            'imagemDesktopUrl' => null,
        ];

        return $this->render('styleguide/index.html.twig', [
            'book' => $book,
            'brokenBook' => ['codl' => 2, 'titulo' => 'Capa com URL quebrada', 'imagemMobileUrl' => '/imagem-inexistente.jpg', 'imagemDesktopUrl' => null] + $book,
            'assuntos' => $assuntos,
            'page' => max(1, $request->query->getInt('page', 3)),
        ]);
    }
}
