<?php

namespace App\Controller\Api;

use App\Entity\Invoice;
use App\Entity\User;
use App\Repository\ClientRepository;
use App\Repository\ExpenseRepository;
use App\Repository\InvoiceRepository;
use App\Repository\UrssafDeclarationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/dashboard', name: 'api_dashboard', methods: ['GET'])]
class DashboardController extends AbstractController
{
    public function __invoke(
        InvoiceRepository $invoiceRepo,
        ExpenseRepository $expenseRepo,
        ClientRepository $clientRepo,
        UrssafDeclarationRepository $urssafRepo,
    ): JsonResponse {
        /** @var User $user */
        $user  = $this->getUser();
        $now   = new \DateTimeImmutable();
        $year  = (int) $now->format('Y');
        $month = (int) $now->format('n');

        // ── CA ──────────────────────────────────────────────────────────────
        $caYear  = $invoiceRepo->getYearRevenue($user, $year);
        $caMonth = $invoiceRepo->getMonthRevenue($user, $year, $month);

        $monthlyRevenue  = $invoiceRepo->getMonthlyRevenue($user, $year);
        $monthlyExpenses = $expenseRepo->getMonthlyTotals($user, $year);

        // ── Factures ─────────────────────────────────────────────────────────
        $pending = $invoiceRepo->getPendingStats($user);
        $overdue = $invoiceRepo->countOverdue($user);
        $rate    = $invoiceRepo->getRecoveryRate($user, $year);

        $recentInvoices = array_map(
            static fn(Invoice $inv) => [
                'id'       => $inv->getId(),
                'number'   => $inv->getNumber(),
                'client'   => $inv->getClient()->getName(),
                'amount'   => (float) $inv->getTotalTtc(),
                'status'   => $inv->getStatus(),
                'issuedAt' => $inv->getIssuedAt()->format('Y-m-d'),
            ],
            $invoiceRepo->findRecentByUser($user, 5),
        );

        $topClients = $invoiceRepo->getTopClients($user, 5);

        // ── Dépenses ─────────────────────────────────────────────────────────
        $expenseMonth = $expenseRepo->getMonthTotal($user, $year, $month);
        $expenseYear  = $expenseRepo->getYearTotal($user, $year);
        $expensesByCategory = $expenseRepo->getTotalsByCategory($user, $year);

        // ── Clients ──────────────────────────────────────────────────────────
        $clientCount = count($clientRepo->findBy(['user' => $user]));

        // ── URSSAF ───────────────────────────────────────────────────────────
        $nextUrssaf = $urssafRepo->findNextUndeclared($user);

        // ── Plan / limites ───────────────────────────────────────────────────
        $invoiceCountThisMonth = $invoiceRepo->countThisMonth($user);

        return $this->json([
            'period' => [
                'year'  => $year,
                'month' => $month,
            ],
            'revenue' => [
                'year'    => round($caYear, 2),
                'month'   => round($caMonth, 2),
                'monthly' => array_values($monthlyRevenue),
            ],
            'expenses' => [
                'month'      => round($expenseMonth, 2),
                'year'       => round($expenseYear, 2),
                'monthly'    => array_values($monthlyExpenses),
                'categories' => $expensesByCategory,
            ],
            'invoices' => [
                'pending_count'  => $pending['count'],
                'pending_amount' => round($pending['amount'], 2),
                'overdue_count'  => $overdue,
                'recovery_rate'  => $rate,
                'this_month'     => $invoiceCountThisMonth,
                'recent'         => $recentInvoices,
            ],
            'clients' => [
                'total' => $clientCount,
                'top'   => $topClients,
            ],
            'urssaf' => $nextUrssaf ? [
                'id'         => $nextUrssaf->getId(),
                'period'     => $nextUrssaf->getPeriodLabel(),
                'cotisation' => (float) $nextUrssaf->getCotisationAmount(),
                'period_end' => $nextUrssaf->getPeriodEnd()?->format('Y-m-d'),
            ] : null,
            'user' => [
                'plan'       => $user->getPlan(),
                'firstName'  => $user->getFirstName(),
            ],
        ]);
    }
}
