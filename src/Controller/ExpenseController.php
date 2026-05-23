<?php

namespace App\Controller;

use App\Entity\Expense;
use App\Repository\ExpenseCategoryRepository;
use App\Repository\ExpenseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/expenses', name: 'app_expense')]
class ExpenseController extends AbstractController
{
    #[Route('', name: 's')]
    public function index(ExpenseRepository $repo): Response
    {
        $expenses = $repo->findBy(['user' => $this->getUser()], ['date' => 'DESC']);

        return $this->render('expense/index.html.twig', ['expenses' => $expenses]);
    }

    #[Route('/new', name: '_new')]
    public function new(Request $request, EntityManagerInterface $em, ExpenseCategoryRepository $categoryRepo): Response
    {
        $categories = $categoryRepo->findAll();

        if ($request->isMethod('POST')) {
            $result = $this->handleForm($request, new Expense(), $em, $categoryRepo);
            if ($result instanceof Expense) {
                $this->addFlash('success', 'Dépense ajoutée.');
                return $this->redirectToRoute('app_expenses');
            }
            return $this->render('expense/form.html.twig', [
                'expense' => new Expense(), 'categories' => $categories,
                'error' => $result, 'title' => 'Nouvelle dépense',
            ]);
        }

        return $this->render('expense/form.html.twig', [
            'expense' => new Expense(), 'categories' => $categories,
            'error' => null, 'title' => 'Nouvelle dépense',
        ]);
    }

    #[Route('/{id}/edit', name: '_edit')]
    public function edit(Expense $expense, Request $request, EntityManagerInterface $em, ExpenseCategoryRepository $categoryRepo): Response
    {
        if ($expense->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $categories = $categoryRepo->findAll();

        if ($request->isMethod('POST')) {
            $result = $this->handleForm($request, $expense, $em, $categoryRepo);
            if ($result instanceof Expense) {
                $this->addFlash('success', 'Dépense modifiée.');
                return $this->redirectToRoute('app_expense_show', ['id' => $expense->getId()]);
            }
            return $this->render('expense/form.html.twig', [
                'expense' => $expense, 'categories' => $categories,
                'error' => $result, 'title' => 'Modifier la dépense',
            ]);
        }

        return $this->render('expense/form.html.twig', [
            'expense' => $expense, 'categories' => $categories,
            'error' => null, 'title' => 'Modifier la dépense',
        ]);
    }

    #[Route('/{id}', name: '_show')]
    public function show(Expense $expense): Response
    {
        if ($expense->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('expense/show.html.twig', ['expense' => $expense]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: ['POST'])]
    public function delete(Expense $expense, Request $request, EntityManagerInterface $em): Response
    {
        if ($expense->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete_expense_' . $expense->getId(), $request->request->get('_token'))) {
            $em->remove($expense);
            $em->flush();
            $this->addFlash('success', 'Dépense supprimée.');
        }

        return $this->redirectToRoute('app_expenses');
    }

    private function handleForm(Request $request, Expense $expense, EntityManagerInterface $em, ExpenseCategoryRepository $categoryRepo): Expense|string
    {
        $vendor = trim($request->request->get('vendor', ''));
        if (!$vendor) {
            return 'Le fournisseur est obligatoire.';
        }

        $amountTtc = $request->request->get('amount_ttc', '0');
        if ((float) $amountTtc < 0) {
            return 'Le montant ne peut pas être négatif.';
        }

        $tvaRate  = $request->request->get('tva_rate', '0');
        $amountHt = (float) $amountTtc / (1 + (float) $tvaRate / 100);
        $tva      = (float) $amountTtc - $amountHt;

        $categoryId = $request->request->get('category_id');
        $category   = $categoryId ? $categoryRepo->find($categoryId) : null;

        $dateStr = $request->request->get('date') ?: 'today';

        $expense->setVendor($vendor)
                ->setDate(new \DateTimeImmutable($dateStr))
                ->setAmountTtc(number_format((float) $amountTtc, 2, '.', ''))
                ->setAmountHt(number_format($amountHt, 2, '.', ''))
                ->setTva(number_format($tva, 2, '.', ''))
                ->setTvaRate(number_format((float) $tvaRate, 2, '.', ''))
                ->setPaymentMethod($request->request->get('payment_method') ?: null)
                ->setDeductible((bool) $request->request->get('deductible', true))
                ->setNotes($request->request->get('notes') ?: null)
                ->setCategory($category)
                ->setUser($this->getUser());

        $em->persist($expense);
        $em->flush();

        return $expense;
    }
}
