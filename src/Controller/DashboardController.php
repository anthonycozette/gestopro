<?php

namespace App\Controller;

use App\Entity\UrssafDeclaration;
use App\Repository\ExpenseRepository;
use App\Repository\InvoiceRepository;
use App\Repository\UrssafDeclarationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        InvoiceRepository $invoiceRepo,
        ExpenseRepository $expenseRepo,
        UrssafDeclarationRepository $urssafRepo,
    ): Response {
        $user  = $this->getUser();
        $year  = (int) date('Y');
        $month = (int) date('n');

        $prevMonth = $month === 1 ? 12 : $month - 1;
        $prevYear  = $month === 1 ? $year - 1 : $year;

        $monthlyRevenues    = $invoiceRepo->getMonthlyRevenue($user, $year);
        $monthlyRevenuesTtc = $invoiceRepo->getMonthlyRevenueTtc($user, $year);
        $monthlyExpenses    = $expenseRepo->getMonthlyTotals($user, $year);
        $expensesByCategory = $expenseRepo->getTotalsByCategory($user, $year);

        $monthDetails     = $invoiceRepo->getMonthRevenueDetails($user, $year, $month);
        $prevMonthDetails = $invoiceRepo->getMonthRevenueDetails($user, $prevYear, $prevMonth);

        $caMonthTtc  = $monthDetails['ttc'];
        $prevTtc     = $prevMonthDetails['ttc'];
        $deltaMonth  = $prevTtc > 0 ? (int) round(($caMonthTtc - $prevTtc) / $prevTtc * 100) : null;
        $diffMonth   = $caMonthTtc - $prevTtc;

        $monthNames    = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
                          'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
        $prevMonthName = $monthNames[$prevMonth];

        // Expenses delta vs previous month
        $expensesMonth     = $expenseRepo->getMonthTotal($user, $year, $month);
        $expensesMonthPrev = $expenseRepo->getMonthTotal($user, $prevYear, $prevMonth);
        $deltaExpenses     = $expensesMonthPrev > 0
            ? (int) round(($expensesMonth - $expensesMonthPrev) / $expensesMonthPrev * 100)
            : null;

        // Expenses scanned this month
        $expensesScannedCount = $expenseRepo->countByUserAndMonth($user, $year, $month);

        // Invoice status breakdown
        $invoiceCounts = $invoiceRepo->getStatusCounts($user);

        // URSSAF — days left for progress bar
        $nextUrssaf = $urssafRepo->findNextUndeclared($user);
        $urssafDaysLeft = null;
        $urssafProgress = 0;
        if ($nextUrssaf) {
            $dueDate = $nextUrssaf->getPeriodEnd()->modify('first day of next month')->modify('last day of this month');
            $now     = new \DateTimeImmutable();
            $urssafDaysLeft = max(0, (int) $now->diff($dueDate)->days);
            $urssafProgress = max(0, min(100, (int) round((1 - $urssafDaysLeft / 30) * 100)));
        }

        // Recent expenses with OCR for "Reçus scannés" widget
        $recentOcrExpenses = $expenseRepo->findRecentByUser($user, 3, true);

        // Upcoming due-soon invoices for "Cette semaine"
        $dueSoonInvoices = $invoiceRepo->findDueSoon($user, 14);

        return $this->render('dashboard/index.html.twig', [
            'caMonth'             => $invoiceRepo->getMonthRevenue($user, $year, $month),
            'caYear'              => $invoiceRepo->getYearRevenue($user, $year),
            'caMonthHt'           => $monthDetails['ht'],
            'caMonthTva'          => $monthDetails['tva'],
            'caMonthTtc'          => $caMonthTtc,
            'deltaMonth'          => $deltaMonth,
            'diffMonth'           => $diffMonth,
            'prevMonthName'       => $prevMonthName,
            'expensesMonth'       => $expensesMonth,
            'expensesYear'        => $expenseRepo->getYearTotal($user, $year),
            'deltaExpenses'       => $deltaExpenses,
            'expensesScannedCount'=> $expensesScannedCount,
            'pending'             => $invoiceRepo->getPendingStats($user),
            'overdueCount'        => $invoiceRepo->countOverdue($user),
            'invoiceCounts'       => $invoiceCounts,
            'recoveryRate'        => $invoiceRepo->getRecoveryRate($user, $year),
            'topClients'          => $invoiceRepo->getTopClients($user, 5),
            'recentInvoices'      => $invoiceRepo->findRecentByUser($user, 6),
            'nextUrssaf'          => $nextUrssaf,
            'urssafYear'          => $urssafRepo->getYearCotisation($user, $year),
            'urssafDaysLeft'      => $urssafDaysLeft,
            'urssafProgress'      => $urssafProgress,
            'tvaThreshold'        => UrssafDeclaration::THRESHOLD_TVA_SERVICES,
            'recentOcrExpenses'   => $recentOcrExpenses,
            'dueSoonInvoices'     => $dueSoonInvoices,
            'monthLabels'         => json_encode(['Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc']),
            'chartRevenues'       => json_encode(array_values($monthlyRevenues)),
            'chartRevenuesTtc'    => json_encode(array_values($monthlyRevenuesTtc)),
            'chartExpenses'       => json_encode(array_values($monthlyExpenses)),
            'chartCatLabels'      => json_encode(array_column($expensesByCategory, 'label')),
            'chartCatData'        => json_encode(array_column($expensesByCategory, 'total')),
            'year'                => $year,
            'currentMonth'        => $month,
        ]);
    }
}
