<?php

namespace App\Controller;

use App\Entity\UrssafDeclaration;
use App\Repository\InvoiceRepository;
use App\Repository\UrssafDeclarationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/urssaf', name: 'app_urssaf')]
class UrssafController extends AbstractController
{
    #[Route('/ca-suggestion', name: '_ca_suggestion', methods: ['GET'])]
    public function caSuggestion(Request $request, InvoiceRepository $invoiceRepo): JsonResponse
    {
        $start = $request->query->get('start', '');
        $end   = $request->query->get('end', '');

        if (!$start || !$end) {
            return $this->json(['revenue' => 0]);
        }

        try {
            $startDate = new \DateTimeImmutable($start);
            $endDate   = new \DateTimeImmutable($end . ' 23:59:59');
        } catch (\Throwable) {
            return $this->json(['revenue' => 0]);
        }

        $revenue = $invoiceRepo->getRevenueForPeriod($this->getUser(), $startDate, $endDate);

        return $this->json(['revenue' => round($revenue, 2)]);
    }

    #[Route('', name: '')]
    public function index(UrssafDeclarationRepository $repo): Response
    {
        $declarations = $repo->findBy(['user' => $this->getUser()], ['periodStart' => 'DESC']);

        $totalRevenue    = array_sum(array_map(fn($d) => (float) $d->getRevenue(), $declarations));
        $totalCotisation = array_sum(array_map(fn($d) => (float) $d->getCotisationAmount(), $declarations));

        return $this->render('urssaf/index.html.twig', [
            'declarations'    => $declarations,
            'totalRevenue'    => $totalRevenue,
            'totalCotisation' => $totalCotisation,
            'thresholdServices' => UrssafDeclaration::THRESHOLD_TVA_SERVICES,
        ]);
    }

    #[Route('/new', name: '_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $result = $this->handleForm($request, new UrssafDeclaration(), $em);
            if ($result instanceof UrssafDeclaration) {
                $this->addFlash('success', 'Déclaration ' . $result->getPeriodLabel() . ' enregistrée.');
                return $this->redirectToRoute('app_urssaf');
            }
            return $this->render('urssaf/form.html.twig', [
                'declaration' => new UrssafDeclaration(), 'error' => $result, 'title' => 'Nouvelle déclaration',
            ]);
        }

        return $this->render('urssaf/form.html.twig', [
            'declaration' => new UrssafDeclaration(), 'error' => null, 'title' => 'Nouvelle déclaration',
        ]);
    }

    #[Route('/{id}/edit', name: '_edit')]
    public function edit(UrssafDeclaration $declaration, Request $request, EntityManagerInterface $em): Response
    {
        if ($declaration->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $result = $this->handleForm($request, $declaration, $em);
            if ($result instanceof UrssafDeclaration) {
                $this->addFlash('success', 'Déclaration modifiée.');
                return $this->redirectToRoute('app_urssaf_show', ['id' => $declaration->getId()]);
            }
            return $this->render('urssaf/form.html.twig', [
                'declaration' => $declaration, 'error' => $result, 'title' => 'Modifier la déclaration',
            ]);
        }

        return $this->render('urssaf/form.html.twig', [
            'declaration' => $declaration, 'error' => null, 'title' => 'Modifier la déclaration',
        ]);
    }

    #[Route('/{id}/export-csv', name: '_export_csv')]
    public function exportCsv(UrssafDeclaration $declaration): StreamedResponse
    {
        if ($declaration->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $response = new StreamedResponse(function () use ($declaration) {
            $out = fopen('php://output', 'w');
            fprintf($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Champ', 'Valeur'], ';');
            fputcsv($out, ['Période', $declaration->getPeriodLabel()], ';');
            fputcsv($out, ['Début de période', $declaration->getPeriodStart()?->format('d/m/Y')], ';');
            fputcsv($out, ['Fin de période', $declaration->getPeriodEnd()?->format('d/m/Y')], ';');
            fputcsv($out, ['Périodicité', $declaration->getPeriodicity() === 'monthly' ? 'Mensuelle' : 'Trimestrielle'], ';');
            fputcsv($out, ['CA déclaré (€)', number_format((float) $declaration->getRevenue(), 2, ',', ' ')], ';');
            fputcsv($out, ['Taux de cotisation (%)', number_format((float) $declaration->getCotisationRate() * 100, 1, ',', '')], ';');
            fputcsv($out, ['Cotisation à payer (€)', number_format((float) $declaration->getCotisationAmount(), 2, ',', ' ')], ';');
            fputcsv($out, ['Statut', $declaration->isDeclared() ? 'Déclarée' : 'À déclarer'], ';');
            fputcsv($out, ['Date de déclaration', $declaration->getDeclaredAt()?->format('d/m/Y') ?? ''], ';');
            fclose($out);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="urssaf-' . $declaration->getPeriodLabel() . '.csv"');
        return $response;
    }

    #[Route('/{id}', name: '_show')]
    public function show(UrssafDeclaration $declaration): Response
    {
        if ($declaration->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('urssaf/show.html.twig', ['declaration' => $declaration]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: ['POST'])]
    public function delete(UrssafDeclaration $declaration, Request $request, EntityManagerInterface $em): Response
    {
        if ($declaration->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete_urssaf_' . $declaration->getId(), $request->request->get('_token'))) {
            $em->remove($declaration);
            $em->flush();
            $this->addFlash('success', 'Déclaration supprimée.');
        }

        return $this->redirectToRoute('app_urssaf');
    }

    private function handleForm(Request $request, UrssafDeclaration $d, EntityManagerInterface $em): UrssafDeclaration|string
    {
        $periodicity = $request->request->get('periodicity', UrssafDeclaration::PERIOD_QUARTERLY);
        $periodLabel = trim($request->request->get('period_label', ''));
        $periodStart = $request->request->get('period_start', '');
        $periodEnd   = $request->request->get('period_end', '');
        $revenue     = $request->request->get('revenue', '0');
        $rate        = $request->request->get('cotisation_rate', (string) UrssafDeclaration::RATE_BNC);

        if (!$periodLabel) {
            return 'Le libellé de période est obligatoire.';
        }
        if (!$periodStart || !$periodEnd) {
            return 'Les dates de début et fin de période sont obligatoires.';
        }
        if ((float) $revenue < 0) {
            return 'Le chiffre d\'affaires ne peut pas être négatif.';
        }

        $declared   = (bool) $request->request->get('declared');
        $declaredAt = null;
        if ($declared) {
            $declaredAtStr = $request->request->get('declared_at', '');
            $declaredAt    = $declaredAtStr ? new \DateTimeImmutable($declaredAtStr) : new \DateTimeImmutable();
        }

        $d->setPeriodicity($periodicity)
          ->setPeriodLabel($periodLabel)
          ->setPeriodStart(new \DateTimeImmutable($periodStart))
          ->setPeriodEnd(new \DateTimeImmutable($periodEnd))
          ->setCotisationRate(number_format((float) $rate, 4, '.', ''))
          ->setRevenue(number_format((float) $revenue, 2, '.', ''))
          ->setDeclared($declared)
          ->setDeclaredAt($declaredAt)
          ->setUser($this->getUser());

        $em->persist($d);
        $em->flush();

        return $d;
    }
}
