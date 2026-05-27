<?php

namespace App\Controller;

use App\Entity\BalanceSheet;
use App\Entity\User;
use App\Repository\BalanceSheetRepository;
use App\Repository\ExpenseRepository;
use App\Repository\InvoiceRepository;
use App\Repository\UrssafDeclarationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bilans', name: 'app_balance_sheet')]
class BalanceSheetController extends AbstractController
{
    private function requireExpertPlan(): ?Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user->isExpert()) {
            $this->addFlash('error', 'Les bilans comptables sont réservés au plan Expert.');
            return $this->redirectToRoute('app_subscription_manage');
        }
        return null;
    }

    #[Route('', name: 's')]
    public function index(BalanceSheetRepository $repo): Response
    {
        if ($redirect = $this->requireExpertPlan()) return $redirect;

        /** @var User $user */
        $user           = $this->getUser();
        $sheets         = $repo->findByUser($user);
        $curYear        = (int) date('Y');
        $prevYear       = $curYear - 1;
        $countGenerated = count($sheets);
        $countValidated = count(array_filter($sheets, fn($s) => $s->getStatus() === BalanceSheet::STATUS_VALIDATED));

        $latestCa = $latestResult = $prevCa = 0.0;
        $latestYear = $curYear;

        foreach ($sheets as $sheet) {
            $data  = $sheet->getFinancialData() ?? [];
            $start = $sheet->getPeriodStart();
            $year  = $start ? (int) $start->format('Y') : $curYear;

            if ($year === $curYear && $latestCa === 0.0) {
                $latestCa     = (float) ($data['revenue_ht'] ?? 0);
                $latestResult = (float) ($data['net_result'] ?? 0);
                $latestYear   = $year;
            }
            if ($year === $prevYear && $prevCa === 0.0) {
                $prevCa = (float) ($data['revenue_ht'] ?? 0);
            }
        }

        return $this->render('balance_sheet/index.html.twig', [
            'sheets'         => $sheets,
            'curYear'        => $curYear,
            'countGenerated' => $countGenerated,
            'countValidated' => $countValidated,
            'latestCa'       => $latestCa,
            'latestResult'   => $latestResult,
            'latestYear'     => $latestYear,
            'prevCa'         => $prevCa,
        ]);
    }

    #[Route('/new', name: '_new')]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        InvoiceRepository $invoiceRepo,
        ExpenseRepository $expenseRepo,
        UrssafDeclarationRepository $urssafRepo,
    ): Response {
        if ($redirect = $this->requireExpertPlan()) return $redirect;

        $preview = null;
        $error   = null;

        if ($request->isMethod('POST')) {
            $action     = $request->request->get('action');
            $periodType = $request->request->get('period_type', 'annual');
            $year       = (int) $request->request->get('year', date('Y'));
            $quarter    = $request->request->get('quarter', 'T1');
            $month      = $request->request->get('month', '01');

            [$start, $end, $label] = $this->computePeriodDates($periodType, $year, $quarter, $month);

            if ($action === 'preview') {
                $preview = $this->computeFinancialData($this->getUser(), $start, $end, $invoiceRepo, $expenseRepo, $urssafRepo);
                $preview['label'] = $label;
                $preview['start'] = $start;
                $preview['end']   = $end;
            } elseif ($action === 'save') {
                $financial = $this->computeFinancialData($this->getUser(), $start, $end, $invoiceRepo, $expenseRepo, $urssafRepo);

                $sheet = new BalanceSheet();
                $sheet->setUser($this->getUser())
                      ->setPeriod($label)
                      ->setPeriodStart($start)
                      ->setPeriodEnd($end)
                      ->setFinancialData($financial)
                      ->setStatus(BalanceSheet::STATUS_DRAFT);

                $em->persist($sheet);
                $em->flush();

                $this->addFlash('success', 'Bilan ' . $label . ' créé.');
                return $this->redirectToRoute('app_balance_sheets');
            }
        }

        return $this->render('balance_sheet/new.html.twig', [
            'preview' => $preview,
            'error'   => $error,
        ]);
    }

    #[Route('/{id}', name: '_show')]
    public function show(BalanceSheet $sheet): Response
    {
        if ($sheet->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $data     = $sheet->getFinancialData() ?? [];
        $sections = [
            ['id' => 'info',        'label' => 'Informations générales', 'done' => true,                                       'active' => false, 'hl' => false],
            ['id' => 'revenue',     'label' => "Chiffre d'affaires",     'done' => isset($data['revenue_ht']),                 'active' => false, 'hl' => false],
            ['id' => 'expenses',    'label' => 'Dépenses',               'done' => isset($data['expenses_ttc']),               'active' => false, 'hl' => false],
            ['id' => 'cotisations', 'label' => 'Cotisations URSSAF',     'done' => isset($data['cotisations']),                'active' => false, 'hl' => false],
            ['id' => 'result',      'label' => 'Résultat net',           'done' => isset($data['net_result']),                 'active' => true,  'hl' => true],
            ['id' => 'ai',          'label' => 'Analyse IA',             'done' => (bool) $sheet->getAiAnalysis(),             'active' => false, 'hl' => false],
            ['id' => 'reco',        'label' => 'Recommandations',        'done' => (bool) $sheet->getAiAnalysis(),             'active' => false, 'hl' => false],
            ['id' => 'annot',       'label' => 'Annotations expert',     'done' => !empty($sheet->getAccountantAnnotations()), 'active' => false, 'hl' => false],
            ['id' => 'sign',        'label' => 'Signature',              'done' => $sheet->getStatus() === BalanceSheet::STATUS_VALIDATED, 'active' => false, 'hl' => false],
        ];

        return $this->render('balance_sheet/show.html.twig', [
            'sheet'    => $sheet,
            'sections' => $sections,
        ]);
    }

    #[Route('/{id}/submit', name: '_submit', methods: ['POST'])]
    public function submit(BalanceSheet $sheet, Request $request, EntityManagerInterface $em): Response
    {
        if ($sheet->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('submit_sheet_' . $sheet->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        if ($sheet->getStatus() === BalanceSheet::STATUS_DRAFT) {
            $sheet->setStatus(BalanceSheet::STATUS_PENDING_REVIEW);
            $em->flush();
            $this->addFlash('success', 'Bilan soumis à votre expert-comptable.');
        }

        return $this->redirectToRoute('app_balance_sheet_show', ['id' => $sheet->getId()]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: ['POST'])]
    public function delete(BalanceSheet $sheet, Request $request, EntityManagerInterface $em): Response
    {
        if ($sheet->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete_sheet_' . $sheet->getId(), $request->request->get('_token'))) {
            $em->remove($sheet);
            $em->flush();
            $this->addFlash('success', 'Bilan supprimé.');
        }

        return $this->redirectToRoute('app_balance_sheets');
    }

    private function computePeriodDates(string $type, int $year, string $quarter, string $month): array
    {
        return match ($type) {
            'quarterly' => $this->quarterDates($year, $quarter),
            'monthly'   => $this->monthDates($year, (int) $month),
            default     => [
                new \DateTimeImmutable($year . '-01-01'),
                new \DateTimeImmutable($year . '-12-31'),
                (string) $year,
            ],
        };
    }

    private function quarterDates(int $year, string $q): array
    {
        $ranges = [
            'T1' => ['01-01', '03-31'],
            'T2' => ['04-01', '06-30'],
            'T3' => ['07-01', '09-30'],
            'T4' => ['10-01', '12-31'],
        ];
        $r = $ranges[$q] ?? $ranges['T1'];

        return [
            new \DateTimeImmutable($year . '-' . $r[0]),
            new \DateTimeImmutable($year . '-' . $r[1]),
            $year . '-' . $q,
        ];
    }

    private function monthDates(int $year, int $month): array
    {
        $last = (int) (new \DateTimeImmutable($year . '-' . sprintf('%02d', $month) . '-01'))->format('t');

        return [
            new \DateTimeImmutable(sprintf('%d-%02d-01', $year, $month)),
            new \DateTimeImmutable(sprintf('%d-%02d-%02d', $year, $month, $last)),
            sprintf('%d-%02d', $year, $month),
        ];
    }

    private function computeFinancialData(
        User $user,
        \DateTimeImmutable $start,
        \DateTimeImmutable $end,
        InvoiceRepository $invoiceRepo,
        ExpenseRepository $expenseRepo,
        UrssafDeclarationRepository $urssafRepo,
    ): array {
        $revenue     = $invoiceRepo->getRevenueForPeriod($user, $start, $end);
        $expenses    = $expenseRepo->getTotalForPeriod($user, $start, $end);
        $cotisations = $urssafRepo->getCotisationsForPeriod($user, $start, $end);
        $net         = $revenue - $expenses - $cotisations;

        return [
            'revenue_ht'   => round($revenue, 2),
            'expenses_ttc' => round($expenses, 2),
            'cotisations'  => round($cotisations, 2),
            'net_result'   => round($net, 2),
        ];
    }
}
