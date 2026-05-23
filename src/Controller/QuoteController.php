<?php

namespace App\Controller;

use App\Entity\Invoice;
use App\Entity\InvoiceLine;
use App\Repository\ClientRepository;
use App\Repository\InvoiceRepository;
use App\Service\InvoiceMailer;
use App\Service\InvoiceNumberService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/devis', name: 'app_quote')]
class QuoteController extends AbstractController
{
    #[Route('', name: 's')]
    public function index(InvoiceRepository $repo): Response
    {
        $quotes = $repo->findBy(
            ['user' => $this->getUser(), 'type' => Invoice::TYPE_QUOTE],
            ['createdAt' => 'DESC']
        );

        return $this->render('quote/index.html.twig', ['quotes' => $quotes]);
    }

    #[Route('/new', name: '_new')]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        ClientRepository $clientRepo,
        InvoiceNumberService $numberService,
        InvoiceMailer $mailer,
    ): Response {
        $clients = $clientRepo->findBy(['user' => $this->getUser()], ['name' => 'ASC']);

        if ($request->isMethod('POST')) {
            $result = $this->handleForm($request, new Invoice(), $em, $numberService);
            if ($result instanceof Invoice) {
                if ($result->getStatus() === Invoice::STATUS_SENT) {
                    $this->sendQuoteEmail($result, $mailer);
                }
                $this->addFlash('success', 'Devis ' . $result->getNumber() . ' créé.');
                return $this->redirectToRoute('app_quotes');
            }
            return $this->render('quote/form.html.twig', ['quote' => new Invoice(), 'clients' => $clients, 'error' => $result, 'title' => 'Nouveau devis']);
        }

        return $this->render('quote/form.html.twig', ['quote' => new Invoice(), 'clients' => $clients, 'error' => null, 'title' => 'Nouveau devis']);
    }

    #[Route('/{id}', name: '_show')]
    public function show(Invoice $quote): Response
    {
        $this->checkAccess($quote);

        return $this->render('quote/show.html.twig', ['quote' => $quote]);
    }

    #[Route('/{id}/edit', name: '_edit')]
    public function edit(
        Invoice $quote,
        Request $request,
        EntityManagerInterface $em,
        ClientRepository $clientRepo,
        InvoiceNumberService $numberService,
    ): Response {
        $this->checkAccess($quote);

        $clients = $clientRepo->findBy(['user' => $this->getUser()], ['name' => 'ASC']);

        if ($request->isMethod('POST')) {
            $result = $this->handleForm($request, $quote, $em, $numberService, false);
            if ($result instanceof Invoice) {
                $this->addFlash('success', 'Devis modifié.');
                return $this->redirectToRoute('app_quote_show', ['id' => $quote->getId()]);
            }
            return $this->render('quote/form.html.twig', ['quote' => $quote, 'clients' => $clients, 'error' => $result, 'title' => 'Modifier le devis']);
        }

        return $this->render('quote/form.html.twig', ['quote' => $quote, 'clients' => $clients, 'error' => null, 'title' => 'Modifier le devis']);
    }

    #[Route('/{id}/send', name: '_send', methods: ['POST'])]
    public function send(Invoice $quote, Request $request, EntityManagerInterface $em, InvoiceMailer $mailer): Response
    {
        $this->checkAccess($quote);

        if (!$this->isCsrfTokenValid('send_quote_' . $quote->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        if ($quote->getStatus() === Invoice::STATUS_DRAFT) {
            $quote->setStatus(Invoice::STATUS_SENT);
            $em->flush();
            $this->sendQuoteEmail($quote, $mailer);
            $this->addFlash('success', 'Devis envoyé au client.');
        }

        return $this->redirectToRoute('app_quote_show', ['id' => $quote->getId()]);
    }

    #[Route('/{id}/accept', name: '_accept', methods: ['POST'])]
    public function accept(Invoice $quote, Request $request, EntityManagerInterface $em): Response
    {
        $this->checkAccess($quote);

        if (!$this->isCsrfTokenValid('accept_quote_' . $quote->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $quote->setStatus(Invoice::STATUS_ACCEPTED);
        $em->flush();
        $this->addFlash('success', 'Devis marqué comme accepté.');

        return $this->redirectToRoute('app_quote_show', ['id' => $quote->getId()]);
    }

    #[Route('/{id}/decline', name: '_decline', methods: ['POST'])]
    public function decline(Invoice $quote, Request $request, EntityManagerInterface $em): Response
    {
        $this->checkAccess($quote);

        if (!$this->isCsrfTokenValid('decline_quote_' . $quote->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $quote->setStatus(Invoice::STATUS_DECLINED);
        $em->flush();
        $this->addFlash('success', 'Devis marqué comme refusé.');

        return $this->redirectToRoute('app_quote_show', ['id' => $quote->getId()]);
    }

    #[Route('/{id}/convert', name: '_convert', methods: ['POST'])]
    public function convert(Invoice $quote, Request $request, EntityManagerInterface $em, InvoiceNumberService $numberService): Response
    {
        $this->checkAccess($quote);

        if (!$this->isCsrfTokenValid('convert_quote_' . $quote->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        if (!in_array($quote->getStatus(), [Invoice::STATUS_SENT, Invoice::STATUS_ACCEPTED])) {
            $this->addFlash('error', 'Seul un devis envoyé ou accepté peut être converti en facture.');
            return $this->redirectToRoute('app_quote_show', ['id' => $quote->getId()]);
        }

        $invoice = new Invoice();
        $invoice->setType(Invoice::TYPE_INVOICE)
                ->setUser($quote->getUser())
                ->setClient($quote->getClient())
                ->setNumber($numberService->generate($quote->getUser()))
                ->setStatus(Invoice::STATUS_DRAFT)
                ->setIssuedAt(new \DateTimeImmutable())
                ->setDueAt($quote->getDueAt())
                ->setNotes($quote->getNotes());

        foreach ($quote->getLines() as $line) {
            $newLine = new InvoiceLine();
            $newLine->setDescription($line->getDescription())
                    ->setQuantity($line->getQuantity())
                    ->setUnitPrice($line->getUnitPrice())
                    ->setTvaRate($line->getTvaRate())
                    ->setPosition($line->getPosition());
            $invoice->addLine($newLine);
            $em->persist($newLine);
        }

        $invoice->recalculateTotals();
        $em->persist($invoice);

        $quote->setStatus(Invoice::STATUS_ACCEPTED);
        $em->flush();

        $this->addFlash('success', 'Facture ' . $invoice->getNumber() . ' créée depuis le devis ' . $quote->getNumber() . '.');

        return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: ['POST'])]
    public function delete(Invoice $quote, Request $request, EntityManagerInterface $em): Response
    {
        $this->checkAccess($quote);

        if ($this->isCsrfTokenValid('delete_quote_' . $quote->getId(), $request->request->get('_token'))) {
            $em->remove($quote);
            $em->flush();
            $this->addFlash('success', 'Devis supprimé.');
        }

        return $this->redirectToRoute('app_quotes');
    }

    private function checkAccess(Invoice $quote): void
    {
        if (!$quote->isQuote() || $quote->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
    }

    private function sendQuoteEmail(Invoice $quote, InvoiceMailer $mailer): void
    {
        if (!$quote->getClient()->getEmail()) {
            $this->addFlash('error', 'Email non envoyé : le client n\'a pas d\'adresse email.');
            return;
        }
        try {
            $mailer->sendQuoteToClient($quote);
            $this->addFlash('success', 'Devis envoyé par email à ' . $quote->getClient()->getEmail() . '.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Impossible d\'envoyer l\'email : ' . $e->getMessage());
        }
    }

    private function handleForm(Request $request, Invoice $quote, EntityManagerInterface $em, InvoiceNumberService $numberService, bool $isNew = true): Invoice|string
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
        if (empty($descriptions) || !array_filter($descriptions)) {
            return 'Ajoutez au moins une ligne de prestation.';
        }

        $quantities = $request->request->all('quantity');
        $prices     = $request->request->all('unit_price');
        $tvaRates   = $request->request->all('tva_rate');

        $quote->setType(Invoice::TYPE_QUOTE)
              ->setClient($client)
              ->setUser($this->getUser())
              ->setStatus($request->request->get('status', Invoice::STATUS_DRAFT))
              ->setIssuedAt(new \DateTimeImmutable($request->request->get('issued_at') ?: 'now'))
              ->setNotes($request->request->get('notes') ?: null);

        $dueAt = $request->request->get('due_at');
        $quote->setDueAt($dueAt ? new \DateTimeImmutable($dueAt) : null);

        if ($isNew) {
            $quote->setNumber($numberService->generateQuote($this->getUser()));
        }

        foreach ($quote->getLines() as $line) {
            $quote->removeLine($line);
            $em->remove($line);
        }

        $position = 1;
        foreach ($descriptions as $i => $desc) {
            if (!trim($desc)) continue;
            $line = new InvoiceLine();
            $line->setDescription(trim($desc))
                 ->setQuantity($quantities[$i] ?? '1')
                 ->setUnitPrice($prices[$i] ?? '0')
                 ->setTvaRate($tvaRates[$i] ?? '20')
                 ->setPosition($position++);
            $quote->addLine($line);
            $em->persist($line);
        }

        $quote->recalculateTotals();
        $em->persist($quote);
        $em->flush();

        return $quote;
    }
}
