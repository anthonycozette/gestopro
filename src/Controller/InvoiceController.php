<?php

namespace App\Controller;

use App\Entity\Invoice;
use App\Entity\InvoiceLine;
use App\Entity\User;
use App\Repository\ClientRepository;
use App\Repository\InvoiceRepository;
use App\Service\InvoiceMailer;
use App\Service\InvoiceNumberService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;
use Knp\Snappy\Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/invoices', name: 'app_invoice')]
class InvoiceController extends AbstractController
{
    #[Route('', name: 's')]
    public function index(InvoiceRepository $repo): Response
    {
        $invoices = $repo->findBy(['user' => $this->getUser()], ['createdAt' => 'DESC']);

        return $this->render('invoice/index.html.twig', ['invoices' => $invoices]);
    }

    #[Route('/new', name: '_new')]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        ClientRepository $clientRepo,
        InvoiceNumberService $numberService,
        InvoiceRepository $invoiceRepo,
        InvoiceMailer $mailer,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getPlan() === User::PLAN_FREE && $invoiceRepo->countThisMonth($user) >= 10) {
            $this->addFlash('error', 'Limite de 10 factures/mois atteinte sur le plan gratuit. Passez au plan Pro pour continuer.');
            return $this->redirectToRoute('app_invoices');
        }

        $clients = $clientRepo->findBy(['user' => $user], ['name' => 'ASC']);

        if ($request->isMethod('POST')) {
            $result = $this->handleForm($request, new Invoice(), $em, $numberService, true);
            if ($result instanceof Invoice) {
                if ($result->getStatus() === Invoice::STATUS_SENT) {
                    $this->sendInvoiceEmail($result, $mailer);
                }
                $this->addFlash('success', 'Facture ' . $result->getNumber() . ' créée.');
                return $this->redirectToRoute('app_invoices');
            }
            return $this->render('invoice/form.html.twig', ['invoice' => new Invoice(), 'clients' => $clients, 'error' => $result, 'title' => 'Nouvelle facture']);
        }

        return $this->render('invoice/form.html.twig', ['invoice' => new Invoice(), 'clients' => $clients, 'error' => null, 'title' => 'Nouvelle facture']);
    }

    #[Route('/{id}/edit', name: '_edit')]
    public function edit(Invoice $invoice, Request $request, EntityManagerInterface $em, ClientRepository $clientRepo, InvoiceNumberService $numberService, InvoiceMailer $mailer): Response
    {
        if ($invoice->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $clients = $clientRepo->findBy(['user' => $this->getUser()], ['name' => 'ASC']);

        if ($request->isMethod('POST')) {
            $result = $this->handleForm($request, $invoice, $em, $numberService, false);
            if ($result instanceof Invoice) {
                if ($result->getStatus() === Invoice::STATUS_SENT) {
                    $this->sendInvoiceEmail($result, $mailer);
                }
                $this->addFlash('success', 'Facture modifiée.');
                return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
            }
            return $this->render('invoice/form.html.twig', ['invoice' => $invoice, 'clients' => $clients, 'error' => $result, 'title' => 'Modifier la facture']);
        }

        return $this->render('invoice/form.html.twig', ['invoice' => $invoice, 'clients' => $clients, 'error' => null, 'title' => 'Modifier la facture']);
    }

    #[Route('/{id}', name: '_show')]
    public function show(Invoice $invoice): Response
    {
        if ($invoice->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('invoice/show.html.twig', ['invoice' => $invoice]);
    }

    #[Route('/{id}/pdf', name: '_pdf')]
    public function pdf(Invoice $invoice, Pdf $knpPdf): Response
    {
        if ($invoice->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $html = $this->renderView('invoice/pdf.html.twig', ['invoice' => $invoice]);

        return new PdfResponse(
            $knpPdf->getOutputFromHtml($html),
            'facture-' . $invoice->getNumber() . '.pdf'
        );
    }

    #[Route('/{id}/status/{status}', name: '_status', methods: ['POST'])]
    public function changeStatus(Invoice $invoice, string $status, Request $request, EntityManagerInterface $em, InvoiceMailer $mailer): Response
    {
        if ($invoice->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!in_array($status, Invoice::STATUSES)) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('status_invoice_' . $invoice->getId(), $request->request->get('_token'))) {
            $previousStatus = $invoice->getStatus();
            $invoice->setStatus($status);
            if ($status === Invoice::STATUS_PAID) {
                $invoice->setPaidAt(new \DateTimeImmutable());
            }
            if ($status === Invoice::STATUS_SENT && $invoice->isQuote() && !$invoice->getSignatureToken()) {
                $invoice->setSignatureToken(bin2hex(random_bytes(32)));
            }
            $em->flush();

            if ($status === Invoice::STATUS_SENT && $previousStatus !== Invoice::STATUS_SENT) {
                $this->sendInvoiceEmail($invoice, $mailer);
            }

            $this->addFlash('success', 'Statut mis à jour.');
        }

        return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: ['POST'])]
    public function delete(Invoice $invoice, Request $request, EntityManagerInterface $em): Response
    {
        if ($invoice->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete_invoice_' . $invoice->getId(), $request->request->get('_token'))) {
            $em->remove($invoice);
            $em->flush();
            $this->addFlash('success', 'Facture supprimée.');
        }

        return $this->redirectToRoute('app_invoices');
    }

    private function sendInvoiceEmail(Invoice $invoice, InvoiceMailer $mailer): void
    {
        if (!$invoice->getClient()->getEmail()) {
            $this->addFlash('error', 'Email non envoyé : le client n\'a pas d\'adresse email renseignée.');
            return;
        }

        try {
            $mailer->sendToClient($invoice);
            $this->addFlash('success', 'Facture envoyée par email à ' . $invoice->getClient()->getEmail() . '.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Impossible d\'envoyer l\'email : ' . $e->getMessage());
        }
    }

    private function handleForm(Request $request, Invoice $invoice, EntityManagerInterface $em, InvoiceNumberService $numberService, bool $isNew): Invoice|string
    {
        $clientId = $request->request->get('client_id');
        if (!$clientId) {
            return 'Veuillez sélectionner un client.';
        }

        $client = $em->find(\App\Entity\Client::class, $clientId);
        if (!$client || $client->getUser() !== $this->getUser()) {
            return 'Client invalide.';
        }

        $descriptions = $request->request->all('description');
        $quantities   = $request->request->all('quantity');
        $prices       = $request->request->all('unit_price');
        $tvaRates     = $request->request->all('tva_rate');

        if (empty($descriptions) || !array_filter($descriptions)) {
            return 'Ajoutez au moins une ligne de prestation.';
        }

        $allowedCurrencies = ['EUR', 'USD', 'GBP', 'CHF', 'CAD'];
        $currency = strtoupper($request->request->get('currency', 'EUR'));
        if (!in_array($currency, $allowedCurrencies)) {
            $currency = 'EUR';
        }

        $action = $request->request->get('action', 'draft');
        if ($action === 'send') {
            $newStatus = Invoice::STATUS_SENT;
        } elseif ($isNew) {
            $newStatus = Invoice::STATUS_DRAFT;
        } else {
            // En mode édition : ne pas rétrograder un statut déjà avancé
            $newStatus = in_array($invoice->getStatus(), [Invoice::STATUS_DRAFT, Invoice::STATUS_SENT])
                ? Invoice::STATUS_DRAFT
                : $invoice->getStatus();
        }

        $invoice->setClient($client)
                ->setUser($this->getUser())
                ->setStatus($newStatus)
                ->setIssuedAt(new \DateTimeImmutable($request->request->get('issued_at') ?: 'now'))
                ->setCurrency($currency)
                ->setNotes($request->request->get('notes') ?: null);

        $dueAt = $request->request->get('due_at');
        $invoice->setDueAt($dueAt ? new \DateTimeImmutable($dueAt) : null);

        if ($isNew) {
            $invoice->setNumber($numberService->generate($this->getUser()));
        }

        if ($newStatus === Invoice::STATUS_SENT && $invoice->isQuote() && !$invoice->getSignatureToken()) {
            $invoice->setSignatureToken(bin2hex(random_bytes(32)));
        }

        // Supprimer les anciennes lignes
        foreach ($invoice->getLines() as $line) {
            $invoice->removeLine($line);
            $em->remove($line);
        }

        // Ajouter les nouvelles lignes
        $position = 1;
        foreach ($descriptions as $i => $desc) {
            if (!trim($desc)) {
                continue;
            }
            $line = new InvoiceLine();
            $line->setDescription(trim($desc))
                 ->setQuantity($quantities[$i] ?? '1')
                 ->setUnitPrice($prices[$i] ?? '0')
                 ->setTvaRate($tvaRates[$i] ?? '20')
                 ->setPosition($position++);
            $invoice->addLine($line);
            $em->persist($line);
        }

        $invoice->recalculateTotals();
        $em->persist($invoice);
        $em->flush();

        return $invoice;
    }
}
