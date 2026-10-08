<?php

namespace App\Controller;

use App\Report\BooksByAuthorReport;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Attribute\Route;

final class ReportController extends AbstractController
{
    public function __construct(
        private readonly BooksByAuthorReport $report,
        #[Autowire('%kernel.project_dir%/public')]
        private readonly string $publicDir,
    ) {
    }

    #[Route('/relatorio', name: 'app_report', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('report/index.html.twig', ['autores' => $this->report->generate()]);
    }

    #[Route('/relatorio/pdf', name: 'app_report_pdf', methods: ['GET'])]
    public function pdf(): Response
    {
        $geradoEm = new \DateTimeImmutable();
        $html = $this->renderView('report/pdf.html.twig', [
            'autores' => $this->report->generate(),
            'geradoEm' => $geradoEm,
            'logo' => $this->publicDir.'/images/logo-spassu-livros.svg',
        ]);

        $options = new Options();
        $options->setIsRemoteEnabled(false);
        $options->setIsPhpEnabled(false);
        // Arquivos locais (a logo) só podem vir de public/.
        $options->setChroot($this->publicDir);
        $options->setDefaultFont('DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Numeração "Página X de Y" no rodapé de todas as páginas.
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans');
        $canvas->page_text($canvas->get_width() - 110, $canvas->get_height() - 28, 'Página {PAGE_NUM} de {PAGE_COUNT}', $font, 8, [0.4, 0.4, 0.4]);

        $filename = sprintf('relatorio-livros-por-autor-%s.pdf', $geradoEm->format('Y-m-d'));

        return new Response($dompdf->output(), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_INLINE, $filename),
        ]);
    }
}
