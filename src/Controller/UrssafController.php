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
        $user = $this->getUser();
        $declarations = $repo->findBy(['user' => $user], ['periodStart' => 'DESC']);

        $now     = new \DateTimeImmutable();
        $curYear = (int) $now->format('Y');

        $yearRevenue    = 0.0;
        $yearCotisation = 0.0;
        $yearLib        = 0.0;

        $enriched = [];
        foreach ($declarations as $d) {
            $rev   = (float) $d->getRevenue();
            $cotis = (float) $d->getCotisationAmount();
            $lib   = (int) round($rev * 0.022);
            $periodEnd = $d->getPeriodEnd();
            $dueDate   = $periodEnd?->modify('last day of next month');
            $daysLeft  = null;
            $overdue   = false;
            if ($dueDate) {
                $diff    = $now->diff($dueDate);
                $overdue  = (bool) $diff->invert;
                $daysLeft = $overdue ? 0 : (int) $diff->days;
            }

            if ((int) ($d->getPeriodStart()?->format('Y') ?? 0) === $curYear) {
                $yearRevenue    += $rev;
                $yearCotisation += $cotis;
                $yearLib        += $lib;
            }

            $enriched[] = [
                'd'        => $d,
                'lib'      => $lib,
                'dueDate'  => $dueDate,
                'daysLeft' => $daysLeft,
                'overdue'  => $overdue,
            ];
        }

        $nextPending  = null;
        $nextLib      = 0;
        $nextDaysLeft = null;
        $nextDueDate  = null;
        foreach ($enriched as $e) {
            if (!$e['d']->isDeclared()) {
                $nextPending  = $e['d'];
                $nextLib      = $e['lib'];
                $nextDaysLeft = $e['daysLeft'];
                $nextDueDate  = $e['dueDate'];
                break;
            }
        }

        $qDefs = [
            'T1' => [1, 3, 'Jan·Fév·Mar'], 'T2' => [4, 6, 'Avr·Mai·Jun'],
            'T3' => [7, 9, 'Jul·Aoû·Sep'], 'T4' => [10, 12, 'Oct·Nov·Déc'],
        ];
        $calendar = [];
        foreach ($qDefs as $qLabel => [$mStart, $mEnd, $mLabel]) {
            $qEndDate = new \DateTimeImmutable(
                $curYear . '-' . str_pad($mEnd, 2, '0', STR_PAD_LEFT) . '-01'
            );
            $qEndDate = new \DateTimeImmutable($qEndDate->format('Y-m-t'));
            $qDueDate = $qEndDate->modify('last day of next month');
            $diff     = $now->diff($qDueDate);
            $qOverdue = (bool) $diff->invert;
            $qDays    = $qOverdue ? 0 : (int) $diff->days;

            $matchDecl = null;
            foreach ($declarations as $d) {
                $ds = $d->getPeriodStart();
                if ($ds && (int) $ds->format('Y') === $curYear
                    && (int) $ds->format('n') === $mStart
                    && $d->getPeriodicity() === 'quarterly') {
                    $matchDecl = $d;
                    break;
                }
            }

            $calendar[] = [
                'label'       => $qLabel,
                'monthsLabel' => $mLabel,
                'dueDate'     => $qDueDate,
                'daysLeft'    => $qDays,
                'overdue'     => $qOverdue,
                'declaration' => $matchDecl,
            ];
        }

        return $this->render('urssaf/index.html.twig', [
            'declarations'    => $enriched,
            'nextPending'     => $nextPending,
            'nextLib'         => $nextLib,
            'nextDaysLeft'    => $nextDaysLeft,
            'nextDueDate'     => $nextDueDate,
            'curYear'         => $curYear,
            'yearRevenue'     => $yearRevenue,
            'yearCotisation'  => $yearCotisation,
            'yearLib'         => $yearLib,
            'calendar'        => $calendar,
            'thresholdServices' => UrssafDeclaration::THRESHOLD_TVA_SERVICES,
        ]);
    }

    #[Route('/new', name: '_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $params = $this->formParams(new UrssafDeclaration(), 'Nouvelle déclaration', false);

        if ($request->isMethod('POST')) {
            $result = $this->handleForm($request, new UrssafDeclaration(), $em);
            if ($result instanceof UrssafDeclaration) {
                $this->addFlash('success', 'Déclaration ' . $result->getPeriodLabel() . ' enregistrée.');
                return $this->redirectToRoute('app_urssaf');
            }
            return $this->render('urssaf/form.html.twig', array_merge($params, ['error' => $result]));
        }

        return $this->render('urssaf/form.html.twig', $params);
    }

    #[Route('/{id}/edit', name: '_edit')]
    public function edit(UrssafDeclaration $declaration, Request $request, EntityManagerInterface $em): Response
    {
        if ($declaration->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $params = $this->formParams($declaration, 'Modifier la déclaration', true);

        if ($request->isMethod('POST')) {
            $result = $this->handleForm($request, $declaration, $em);
            if ($result instanceof UrssafDeclaration) {
                $this->addFlash('success', 'Déclaration modifiée.');
                return $this->redirectToRoute('app_urssaf_show', ['id' => $declaration->getId()]);
            }
            return $this->render('urssaf/form.html.twig', array_merge($params, ['error' => $result]));
        }

        return $this->render('urssaf/form.html.twig', $params);
    }

    private function formParams(UrssafDeclaration $declaration, string $title, bool $isEdit): array
    {
        return [
            'declaration'  => $declaration,
            'error'        => null,
            'title'        => $title,
            'isEdit'       => $isEdit,
            'caEndpoint'   => $this->generateUrl('app_urssaf_ca_suggestion'),
            'rates'        => [
                ['value' => '0.2120', 'label' => 'Prestations de services BNC',     'short' => 'BNC',   'pct' => '21,2 %'],
                ['value' => '0.2180', 'label' => 'Professions libérales CIPAV',      'short' => 'CIPAV', 'pct' => '21,8 %'],
                ['value' => '0.1280', 'label' => 'Vente de marchandises (BIC)',      'short' => 'Vente', 'pct' => '12,8 %'],
                ['value' => '0.2120', 'label' => 'Prestations de services BIC',      'short' => 'BIC',   'pct' => '21,2 %'],
            ],
        ];
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
    public function show(
        UrssafDeclaration $declaration,
        UrssafDeclarationRepository $repo,
        InvoiceRepository $invoiceRepo,
    ): Response {
        if ($declaration->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->getUser();

        // 5 previous declarations for history
        $allDecls = $repo->findBy(['user' => $user], ['periodStart' => 'DESC'], 7);
        $history  = array_slice(
            array_values(array_filter($allDecls, fn($d) => $d->getId() !== $declaration->getId())),
            0, 5
        );

        // Monthly revenue breakdown for the period
        $monthlyBreakdown = $this->buildMonthlyBreakdown($declaration, $invoiceRepo);

        // Due date ≈ last day of the month following period end
        $periodEnd = $declaration->getPeriodEnd();
        $dueDate   = $periodEnd?->modify('last day of next month');
        $daysLeft  = null;
        if ($dueDate) {
            $diff     = (new \DateTimeImmutable())->diff($dueDate);
            $daysLeft = $diff->invert ? 0 : (int) $diff->days;
        }

        // Proportional cotisation breakdown
        $rev    = (float) $declaration->getRevenue();
        $total  = (float) $declaration->getCotisationAmount();
        $rates  = [
            ['label' => 'Maladie · maternité',      'rate' => 6.2],
            ['label' => 'Retraite de base',          'rate' => 8.2],
            ['label' => 'Retraite complémentaire',   'rate' => 1.4],
            ['label' => 'CSG · CRDS',                'rate' => 6.7],
            ['label' => 'Indemnités journalières',   'rate' => 0.3],
            ['label' => 'Formation professionnelle', 'rate' => 0.2],
        ];
        $sumRates  = array_sum(array_column($rates, 'rate'));
        $breakdown = array_map(
            fn($r) => [...$r, 'base' => $rev, 'amt' => (int) round($total * $r['rate'] / $sumRates)],
            $rates
        );

        // Versement libératoire (2.2%) — indicative, not persisted
        $libAmount = (int) round($rev * 0.022);

        // YTD cumulated revenue
        $year       = (int) (($declaration->getPeriodStart() ?? new \DateTimeImmutable())->format('Y'));
        $ytdRevenue = $invoiceRepo->getYearRevenue($user, $year);

        return $this->render('urssaf/show.html.twig', [
            'declaration'       => $declaration,
            'history'           => $history,
            'monthlyBreakdown'  => $monthlyBreakdown,
            'dueDate'           => $dueDate,
            'daysLeft'          => $daysLeft,
            'breakdown'         => $breakdown,
            'libAmount'         => $libAmount,
            'ytdRevenue'        => $ytdRevenue,
            'thresholdServices' => UrssafDeclaration::THRESHOLD_TVA_SERVICES,
        ]);
    }

    private function buildMonthlyBreakdown(UrssafDeclaration $d, InvoiceRepository $repo): array
    {
        $start = $d->getPeriodStart();
        $end   = $d->getPeriodEnd();
        if (!$start || !$end) {
            return [];
        }

        $user   = $d->getUser();
        $names  = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
        $result = [];
        $cursor = new \DateTimeImmutable($start->format('Y-m-01'));
        $guard  = 0;

        while ($cursor <= $end && $guard++ < 12) {
            $mEnd = new \DateTimeImmutable($cursor->format('Y-m-t') . ' 23:59:59');
            $result[] = [
                'label'  => $names[(int) $cursor->format('n') - 1],
                'amount' => round($repo->getRevenueForPeriod($user, $cursor, $mEnd), 2),
            ];
            $cursor = new \DateTimeImmutable($cursor->modify('first day of next month')->format('Y-m-01'));
        }

        return $result;
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
