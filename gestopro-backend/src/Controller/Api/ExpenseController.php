<?php

namespace App\Controller\Api;

use App\Entity\Expense;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/expenses')]
final class ExpenseController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('', name: 'api_expenses_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $expenses = $this->entityManager->getRepository(Expense::class)
            ->findBy(['user' => $user], ['expenseDate' => 'DESC']);

        $data = array_map(function (Expense $expense) {
            return [
                'id' => $expense->getId(),
                'description' => $expense->getDescription(),
                'amount' => (float) $expense->getAmount(),
                'category' => $expense->getCategory(),
                'expenseDate' => $expense->getExpenseDate()->format('Y-m-d'),
                'status' => $expense->getStatus(),
                'attachment' => $expense->getAttachment(),
                'createdAt' => $expense->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $expenses);

        return $this->json([
            'success' => true,
            'expenses' => $data
        ]);
    }

    #[Route('/{id}', name: 'api_expenses_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $expense = $this->entityManager->getRepository(Expense::class)->find($id);

        if (!$expense || $expense->getUser() !== $user) {
            return $this->json(['success' => false, 'message' => 'Dépense non trouvée'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'expense' => [
                'id' => $expense->getId(),
                'description' => $expense->getDescription(),
                'amount' => (float) $expense->getAmount(),
                'category' => $expense->getCategory(),
                'expenseDate' => $expense->getExpenseDate()->format('Y-m-d'),
                'status' => $expense->getStatus(),
                'attachment' => $expense->getAttachment(),
                'createdAt' => $expense->getCreatedAt()->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    #[Route('', name: 'api_expenses_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['description']) || !isset($data['amount'])) {
            return $this->json([
                'success' => false,
                'message' => 'Description et montant requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        $expense = new Expense();
        $expense->setDescription($data['description']);
        $expense->setAmount($data['amount']);
        $expense->setCategory($data['category'] ?? 'other');
        $expense->setExpenseDate(new \DateTimeImmutable($data['expenseDate'] ?? 'now'));
        $expense->setStatus($data['status'] ?? 'pending');
        $expense->setAttachment($data['attachment'] ?? null);
        $expense->setUser($user);
        $expense->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($expense);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Dépense créée avec succès',
            'expense' => [
                'id' => $expense->getId(),
                'description' => $expense->getDescription(),
                'amount' => (float) $expense->getAmount(),
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_expenses_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $expense = $this->entityManager->getRepository(Expense::class)->find($id);

        if (!$expense || $expense->getUser() !== $user) {
            return $this->json(['success' => false, 'message' => 'Dépense non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['description'])) $expense->setDescription($data['description']);
        if (isset($data['amount'])) $expense->setAmount($data['amount']);
        if (isset($data['category'])) $expense->setCategory($data['category']);
        if (isset($data['status'])) $expense->setStatus($data['status']);
        if (isset($data['attachment'])) $expense->setAttachment($data['attachment']);

        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Dépense mise à jour avec succès'
        ]);
    }

    #[Route('/{id}', name: 'api_expenses_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $expense = $this->entityManager->getRepository(Expense::class)->find($id);

        if (!$expense || $expense->getUser() !== $user) {
            return $this->json(['success' => false, 'message' => 'Dépense non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($expense);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Dépense supprimée avec succès'
        ]);
    }

    #[Route('/stats/by-category', name: 'api_expenses_stats_category', methods: ['GET'])]
    public function statsByCategory(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $expenses = $this->entityManager->getRepository(Expense::class)
            ->findBy(['user' => $user]);

        $stats = [];
        foreach ($expenses as $expense) {
            $category = $expense->getCategory();
            if (!isset($stats[$category])) {
                $stats[$category] = ['total' => 0, 'count' => 0];
            }
            $stats[$category]['total'] += (float) $expense->getAmount();
            $stats[$category]['count']++;
        }

        return $this->json([
            'success' => true,
            'stats' => $stats
        ]);
    }
}
