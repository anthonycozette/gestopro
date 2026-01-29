<?php

namespace App\Controller\Api;

use App\Entity\URSSAFDeclaration;
use App\Entity\Invoice;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/urssaf')]
final class URSSAFController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/calculate', name: 'api_urssaf_calculate', methods: ['POST'])]
    public function calculate(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['turnover'])) {
            return $this->json([
                'success' => false,
                'message' => 'Chiffre d\'affaires requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        $turnover = (float) $data['turnover'];
        $activityType = $data['activityType'] ?? 'services'; // services, ecommerce, vente
        $regime = $data['regime'] ?? 'micro'; // micro, real

        // Taux de cotisations URSSAF 2026
        $rates = [
            'micro' => [
                'services' => 0.225,    // 22.5%
                'ecommerce' => 0.225,   // 22.5%
                'vente' => 0.145,       // 14.5%
            ],
            'real' => [
                'services' => 0.45,     // 45%
                'ecommerce' => 0.45,    // 45%
                'vente' => 0.415,       // 41.5%
            ]
        ];

        $rate = $rates[$regime][$activityType] ?? 0.225;
        $contribution = $turnover * $rate;

        // Calcul additionnel (CFP - Contribution à la Formation Professionnelle)
        $cfpRate = 0.003; // 0.3%
        $cfpContribution = $turnover * $cfpRate;

        $totalContribution = $contribution + $cfpContribution;
        $netRevenue = $turnover - $totalContribution;

        return $this->json([
            'success' => true,
            'calculation' => [
                'turnover' => $turnover,
                'regime' => $regime,
                'activityType' => $activityType,
                'baseRate' => $rate * 100,
                'baseContribution' => round($contribution, 2),
                'cfpContribution' => round($cfpContribution, 2),
                'totalContribution' => round($totalContribution, 2),
                'netRevenue' => round($netRevenue, 2),
                'netMargin' => round(($netRevenue / $turnover) * 100, 2),
            ]
        ]);
    }

    #[Route('/declarations', name: 'api_urssaf_declarations_list', methods: ['GET'])]
    public function listDeclarations(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $declarations = $this->entityManager->getRepository(URSSAFDeclaration::class)
            ->findBy(['user' => $user], ['createdAt' => 'DESC']);

        $data = array_map(function (URSSAFDeclaration $declaration) {
            return [
                'id' => $declaration->getId(),
                'period' => $declaration->getPeriod(),
                'turnover' => (float) $declaration->getTurnover(),
                'contributionAmount' => (float) $declaration->getContributionAmount(),
                'contributionRate' => (float) $declaration->getContributionRate(),
                'status' => $declaration->getStatus(),
                'dueDate' => $declaration->getDueDate()->format('Y-m-d'),
                'paidDate' => $declaration->getPaidDate()?->format('Y-m-d'),
                'createdAt' => $declaration->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $declarations);

        return $this->json([
            'success' => true,
            'declarations' => $data
        ]);
    }

    #[Route('/declarations', name: 'api_urssaf_declaration_create', methods: ['POST'])]
    public function createDeclaration(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['period']) || !isset($data['turnover'])) {
            return $this->json([
                'success' => false,
                'message' => 'Période et chiffre d\'affaires requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        $declaration = new URSSAFDeclaration();
        $declaration->setPeriod($data['period']);
        $declaration->setTurnover($data['turnover']);
        $declaration->setContributionAmount($data['contributionAmount']);
        $declaration->setContributionRate($data['contributionRate']);
        $declaration->setStatus($data['status'] ?? 'pending');
        $declaration->setDueDate(new \DateTimeImmutable($data['dueDate']));
        $declaration->setPaidDate(isset($data['paidDate']) ? new \DateTimeImmutable($data['paidDate']) : null);
        $declaration->setUser($user);
        $declaration->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($declaration);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Déclaration créée avec succès',
            'declaration' => [
                'id' => $declaration->getId(),
                'period' => $declaration->getPeriod(),
                'turnover' => (float) $declaration->getTurnover(),
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/declarations/{id}', name: 'api_urssaf_declaration_update', methods: ['PUT'])]
    public function updateDeclaration(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $declaration = $this->entityManager->getRepository(URSSAFDeclaration::class)->find($id);

        if (!$declaration || $declaration->getUser() !== $user) {
            return $this->json(['success' => false, 'message' => 'Déclaration non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['status'])) $declaration->setStatus($data['status']);
        if (isset($data['paidDate'])) {
            $declaration->setPaidDate(new \DateTimeImmutable($data['paidDate']));
        }

        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Déclaration mise à jour avec succès'
        ]);
    }

    #[Route('/turnover/{year}/{quarter}', name: 'api_urssaf_turnover', methods: ['GET'])]
    public function getTurnover(int $year, string $quarter): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // Calculer les dates de début et fin du trimestre
        $quarters = [
            'Q1' => ['start' => "$year-01-01", 'end' => "$year-03-31"],
            'Q2' => ['start' => "$year-04-01", 'end' => "$year-06-30"],
            'Q3' => ['start' => "$year-07-01", 'end' => "$year-09-30"],
            'Q4' => ['start' => "$year-10-01", 'end' => "$year-12-31"],
        ];

        if (!isset($quarters[$quarter])) {
            return $this->json(['success' => false, 'message' => 'Trimestre invalide'], Response::HTTP_BAD_REQUEST);
        }

        $startDate = new \DateTimeImmutable($quarters[$quarter]['start']);
        $endDate = new \DateTimeImmutable($quarters[$quarter]['end']);

        // Récupérer les factures payées du trimestre
        $invoices = $this->entityManager->getRepository(Invoice::class)
            ->createQueryBuilder('i')
            ->where('i.user = :user')
            ->andWhere('i.status = :status')
            ->andWhere('i.invoiceDate >= :startDate')
            ->andWhere('i.invoiceDate <= :endDate')
            ->setParameter('user', $user)
            ->setParameter('status', 'paid')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();

        $totalTurnover = 0;
        foreach ($invoices as $invoice) {
            $totalTurnover += (float) $invoice->getTotalTTC();
        }

        return $this->json([
            'success' => true,
            'period' => "$year-$quarter",
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'turnover' => round($totalTurnover, 2),
            'invoiceCount' => count($invoices),
        ]);
    }
}
