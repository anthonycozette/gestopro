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

        $monthNames  = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
                        'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
        $prevMonthName = $monthNames[$prevMonth];

        return $this->render('dashboard/index.html.twig', [
            'caMonth'        => $invoiceRepo->getMonthRevenue($user, $year, $month),
            'caYear'         => $invoiceRepo->getYearRevenue($user, $year),
            'caMonthHt'      => $monthDetails['ht'],
            'caMonthTva'     => $monthDetails['tva'],
            'caMonthTtc'     => $caMonthTtc,
            'deltaMonth'     => $deltaMonth,
            'diffMonth'      => $diffMonth,
            'prevMonthName'  => $prevMonthName,
            'expensesMonth'  => $expenseRepo->getMonthTotal($user, $year, $month),
            'expensesYear'   => $expenseRepo->getYearTotal($user, $year),
            'pending'        => $invoiceRepo->getPendingStats($user),
            'overdueCount'   => $invoiceRepo->countOverdue($user),
            'recoveryRate'   => $invoiceRepo->getRecoveryRate($user, $year),
            'topClients'     => $invoiceRepo->getTopClients($user, 5),
            'recentInvoices' => $invoiceRepo->findRecentByUser($user, 5),
            'nextUrssaf'     => $urssafRepo->findNextUndeclared($user),
            'urssafYear'     => $urssafRepo->getYearCotisation($user, $year),
            'tvaThreshold'   => UrssafDeclaration::THRESHOLD_TVA_SERVICES,
            'monthLabels'    => json_encode(['Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc']),
            'chartRevenues'  => json_encode(array_values($monthlyRevenues)),
            'chartRevenuesTtc' => json_encode(array_values($monthlyRevenuesTtc)),
            'chartExpenses'  => json_encode(array_values($monthlyExpenses)),
            'chartCatLabels' => json_encode(array_column($expensesByCategory, 'label')),
            'chartCatData'   => json_encode(array_column($expensesByCategory, 'total')),
            'year'           => $year,
            'currentMonth'   => $month,
        ]);
    }
}
