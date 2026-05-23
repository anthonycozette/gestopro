<?php

namespace App\Controller;

use App\Entity\Invoice;
use App\Entity\InvoiceLine;
use App\Repository\ClientRepository;
use App\Repository\InvoiceRepository;
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
    ): Response {
        $clients = $clientRepo->findBy(['user' => $this->getUser()], ['name' => 'ASC']);

        if ($request->isMethod('POST')) {
            $result = $this->handleForm($request, new Invoice(), $em, $numberService, true);
            if ($result instanceof Invoice) {
                $this->addFlash('success', 'Facture ' . $result->getNumber() . ' créée.');
                return $this->redirectToRoute('app_invoices');
            }
            return $this->render('invoice/form.html.twig', ['invoice' => new Invoice(), 'clients' => $clients, 'error' => $result, 'title' => 'Nouvelle facture']);
        }

        return $this->render('invoice/form.html.twig', ['invoice' => new Invoice(), 'clients' => $clients, 'error' => null, 'title' => 'Nouvelle facture']);
    }

    #[Route('/{id}/edit', name: '_edit')]
    public function edit(Invoice $invoice, Request $request, EntityManagerInterface $em, ClientRepository $clientRepo, InvoiceNumberService $numberService): Response
    {
        if ($invoice->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $clients = $clientRepo->findBy(['user' => $this->getUser()], ['name' => 'ASC']);

        if ($request->isMethod('POST')) {
            $result = $this->handleForm($request, $invoice, $em, $numberService, false);
            if ($result instanceof Invoice) {
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
    public function changeStatus(Invoice $invoice, string $status, Request $request, EntityManagerInterface $em): Response
    {
        if ($invoice->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!in_array($status, Invoice::STATUSES)) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('status_invoice_' . $invoice->getId(), $request->request->get('_token'))) {
            $invoice->setStatus($status);
            if ($status === Invoice::STATUS_PAID) {
                $invoice->setPaidAt(new \DateTimeImmutable());
            }
            $em->flush();
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

        $invoice->setClient($client)
                ->setUser($this->getUser())
                ->setStatus($request->request->get('status', Invoice::STATUS_DRAFT))
                ->setIssuedAt(new \DateTimeImmutable($request->request->get('issued_at') ?: 'now'))
                ->setNotes($request->request->get('notes') ?: null);

        $dueAt = $request->request->get('due_at');
        $invoice->setDueAt($dueAt ? new \DateTimeImmutable($dueAt) : null);

        if ($isNew) {
            $invoice->setNumber($numberService->generate($this->getUser()));
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
