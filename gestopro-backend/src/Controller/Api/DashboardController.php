<?php

namespace App\Controller\Api;

use App\Repository\ClientRepository;
use App\Repository\ExpenseRepository;
use App\Repository\InvoiceRepository;
use App\Repository\URSSAFDeclarationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/dashboard')]
#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    public function __construct(
        private InvoiceRepository $invoiceRepository,
        private ClientRepository $clientRepository,
        private ExpenseRepository $expenseRepository,
        private URSSAFDeclarationRepository $urssafRepository,
    ) {
    }

    #[Route('/stats', name: 'api_dashboard_stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        $user = $this->getUser();

        // Statistiques des factures
        $invoices = $this->invoiceRepository->findBy(['user' => $user]);
        $totalRevenue = 0;
        $paidInvoices = 0;
        $pendingInvoices = 0;
        $overdueInvoices = 0;

        foreach ($invoices as $invoice) {
            $totalRevenue += $invoice->getTotalTTC();

            switch ($invoice->getStatus()) {
                case 'paid':
                    $paidInvoices++;
                    break;
                case 'pending':
                    $pendingInvoices++;
                    break;
                case 'overdue':
                    $overdueInvoices++;
                    break;
            }
        }

        // Statistiques des clients
        $totalClients = $this->clientRepository->count(['user' => $user]);

        // Statistiques des dépenses
        $expenses = $this->expenseRepository->findBy(['user' => $user]);
        $totalExpenses = 0;
        foreach ($expenses as $expense) {
            $totalExpenses += $expense->getAmount();
        }

        // Prochaine déclaration URSSAF
        $nextDeclaration = $this->urssafRepository->findOneBy(
            ['user' => $user, 'status' => 'pending'],
            ['dueDate' => 'ASC']
        );

        return $this->json([
            'revenue' => [
                'total' => $totalRevenue,
                'trend' => '+12.5%', // À calculer dynamiquement plus tard
            ],
            'invoices' => [
                'total' => count($invoices),
                'paid' => $paidInvoices,
                'pending' => $pendingInvoices,
                'overdue' => $overdueInvoices,
            ],
            'clients' => [
                'total' => $totalClients,
            ],
            'expenses' => [
                'total' => $totalExpenses,
            ],
            'urssaf' => [
                'nextDueDate' => $nextDeclaration?->getDueDate()?->format('Y-m-d'),
                'amount' => $nextDeclaration?->getContributionAmount(),
            ],
        ]);
    }

    #[Route('/recent-invoices', name: 'api_dashboard_recent_invoices', methods: ['GET'])]
    public function recentInvoices(): JsonResponse
    {
        $user = $this->getUser();

        $invoices = $this->invoiceRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC'],
            5 // Limiter aux 5 dernières factures
        );

        $data = [];
        foreach ($invoices as $invoice) {
            $data[] = [
                'id' => $invoice->getId(),
                'invoiceNumber' => $invoice->getInvoiceNumber(),
                'client' => [
                    'id' => $invoice->getClient()->getId(),
                    'name' => $invoice->getClient()->getName(),
                ],
                'totalTTC' => $invoice->getTotalTTC(),
                'status' => $invoice->getStatus(),
                'invoiceDate' => $invoice->getInvoiceDate()->format('Y-m-d'),
                'dueDate' => $invoice->getDueDate()->format('Y-m-d'),
            ];
        }

        return $this->json($data);
    }

    #[Route('/chart-data', name: 'api_dashboard_chart_data', methods: ['GET'])]
    public function chartData(): JsonResponse
    {
        $user = $this->getUser();

        // Données pour graphique des revenus mensuels (6 derniers mois)
        $months = [];
        $revenues = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = new \DateTimeImmutable("-$i months");
            $monthStart = $date->modify('first day of this month');
            $monthEnd = $date->modify('last day of this month');

            $invoices = $this->invoiceRepository->createQueryBuilder('i')
                ->where('i.user = :user')
                ->andWhere('i.invoiceDate BETWEEN :start AND :end')
                ->andWhere('i.status = :status')
                ->setParameter('user', $user)
                ->setParameter('start', $monthStart)
                ->setParameter('end', $monthEnd)
                ->setParameter('status', 'paid')
                ->getQuery()
                ->getResult();

            $monthTotal = 0;
            foreach ($invoices as $invoice) {
                $monthTotal += $invoice->getTotalTTC();
            }

            $months[] = $date->format('M Y');
            $revenues[] = $monthTotal;
        }

        return $this->json([
            'months' => $months,
            'revenues' => $revenues,
        ]);
    }
}
