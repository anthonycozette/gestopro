<?php

namespace App\Service;

use App\Entity\Invoice;
use Knp\Snappy\Pdf;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Twig\Environment;

class InvoiceMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly Pdf $pdf,
        private readonly Environment $twig,
    ) {}

    public function sendQuoteToClient(Invoice $quote): void
    {
        $client = $quote->getClient();
        $user   = $quote->getUser();

        if (!$client->getEmail()) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address('noreply@gestopro.fr', $user->getFullName()))
            ->to(new Address($client->getEmail(), $client->getName()))
            ->subject('Devis ' . $quote->getNumber() . ' — ' . $user->getFullName())
            ->htmlTemplate('email/quote.html.twig')
            ->context(['quote' => $quote, 'user' => $user]);

        try {
            $html       = $this->twig->render('invoice/pdf.html.twig', ['invoice' => $quote]);
            $pdfContent = $this->pdf->getOutputFromHtml($html);
            $email->attach($pdfContent, 'devis-' . $quote->getNumber() . '.pdf', 'application/pdf');
        } catch (\Exception) {
            // wkhtmltopdf non disponible — envoi sans pièce jointe PDF
        }

        $this->mailer->send($email);
    }

    public function sendToClient(Invoice $invoice): void
    {
        $client = $invoice->getClient();
        $user   = $invoice->getUser();

        if (!$client->getEmail()) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address('noreply@gestopro.fr', $user->getFullName()))
            ->to(new Address($client->getEmail(), $client->getName()))
            ->subject('Facture ' . $invoice->getNumber() . ' — ' . $user->getFullName())
            ->htmlTemplate('email/invoice.html.twig')
            ->context(['invoice' => $invoice, 'user' => $user]);

        // Pièce jointe PDF (optionnelle — ignorée si wkhtmltopdf absent)
        try {
            $html       = $this->twig->render('invoice/pdf.html.twig', ['invoice' => $invoice]);
            $pdfContent = $this->pdf->getOutputFromHtml($html);
            $email->attach($pdfContent, 'facture-' . $invoice->getNumber() . '.pdf', 'application/pdf');
        } catch (\Exception) {
            // wkhtmltopdf non disponible — envoi sans pièce jointe PDF
        }

        $this->mailer->send($email);
    }
}
