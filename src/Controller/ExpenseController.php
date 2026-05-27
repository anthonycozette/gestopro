<?php

namespace App\Controller;

use App\Entity\Expense;
use App\Entity\User;
use App\Repository\ExpenseCategoryRepository;
use App\Repository\ExpenseRepository;
use App\Service\ReceiptScannerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[Route('/expenses', name: 'app_expense')]
class ExpenseController extends AbstractController
{
    #[Route('/scan-receipt', name: '_scan_receipt', methods: ['POST'])]
    public function scanReceipt(Request $request, ReceiptScannerService $scanner): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getPlan() === 'free') {
            return $this->json(['error' => 'Le scan OCR est réservé aux plans Pro et Expert.'], 403);
        }

        $file = $request->files->get('receipt');
        if (!$file) {
            return $this->json(['error' => 'Aucun fichier fourni.'], 400);
        }

        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'];
        if (!in_array($file->getMimeType(), $allowed, true)) {
            return $this->json(['error' => 'Format non supporté (JPEG, PNG, WebP, PDF).'], 422);
        }

        if ($file->getSize() > 10 * 1024 * 1024) {
            return $this->json(['error' => 'Fichier trop volumineux (max 10 Mo).'], 422);
        }

        try {
            $result = $scanner->scan(
                base64_encode(file_get_contents($file->getPathname())),
                $file->getMimeType(),
            );
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Erreur lors de l\'analyse : ' . $e->getMessage()], 500);
        }

        return $this->json($result);
    }

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

    #[Route('/{id}/receipt', name: '_receipt')]
    public function viewReceipt(Expense $expense): Response
    {
        if ($expense->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $path = $this->getReceiptDir() . '/' . $expense->getReceiptPath();

        if (!$expense->getReceiptPath() || !file_exists($path)) {
            throw $this->createNotFoundException('Justificatif introuvable.');
        }

        return new BinaryFileResponse(
            $path,
            200,
            [],
            true,
            ResponseHeaderBag::DISPOSITION_INLINE
        );
    }

    #[Route('/{id}/receipt/delete', name: '_receipt_delete', methods: ['POST'])]
    public function deleteReceipt(Expense $expense, Request $request, EntityManagerInterface $em): Response
    {
        if ($expense->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete_receipt_' . $expense->getId(), $request->request->get('_token'))) {
            $this->removeReceiptFile($expense);
            $expense->setReceiptPath(null);
            $em->flush();
            $this->addFlash('success', 'Justificatif supprimé.');
        }

        return $this->redirectToRoute('app_expense_show', ['id' => $expense->getId()]);
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
                ->setInvoiceNumber($request->request->get('invoice_number') ?: null)
                ->setDeductible((bool) $request->request->get('deductible', true))
                ->setNotes($request->request->get('notes') ?: null)
                ->setCategory($category)
                ->setUser($this->getUser());

        // Données OCR (champs cachés remplis par le scan)
        $ocrConfidenceRaw = $request->request->get('ocr_confidence', '');
        if ($ocrConfidenceRaw !== '') {
            $ocrDataJson = $request->request->get('ocr_data', '');
            $expense->setOcrConfidence(number_format((float) $ocrConfidenceRaw / 100, 2, '.', ''))
                    ->setOcrData($ocrDataJson ? json_decode($ocrDataJson, true) : null)
                    ->setOcrVerified(true);
        }

        // Justificatif (upload)
        $receiptFile = $request->files->get('receipt_file');
        if ($receiptFile) {
            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
            if (!in_array($receiptFile->getMimeType(), $allowed, true)) {
                return 'Format de justificatif non supporté (JPEG, PNG, WebP, PDF).';
            }
            if ($receiptFile->getSize() > 10 * 1024 * 1024) {
                return 'Le justificatif ne doit pas dépasser 10 Mo.';
            }

            $this->removeReceiptFile($expense);

            $ext      = $receiptFile->guessExtension() ?? 'bin';
            $filename = sprintf('%s-%s.%s', $expense->getUser()->getId(), bin2hex(random_bytes(8)), $ext);
            $dir      = $this->getReceiptDir();

            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $receiptFile->move($dir, $filename);
            $expense->setReceiptPath($filename);
        }

        $em->persist($expense);
        $em->flush();

        return $expense;
    }

    private function getReceiptDir(): string
    {
        return $this->getParameter('kernel.project_dir') . '/var/uploads/receipts';
    }

    private function removeReceiptFile(Expense $expense): void
    {
        if ($expense->getReceiptPath()) {
            $old = $this->getReceiptDir() . '/' . $expense->getReceiptPath();
            if (file_exists($old)) {
                unlink($old);
            }
        }
    }
}
