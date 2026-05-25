<?php

namespace App\Controller;

use App\Repository\InvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/devis/sign', name: 'app_quote_sign')]
class QuoteSignatureController extends AbstractController
{
    #[Route('/{token}', name: '', methods: ['GET'])]
    public function show(string $token, InvoiceRepository $repo): Response
    {
        $quote = $repo->findOneBy(['signatureToken' => $token]);

        if (!$quote || !$quote->isQuote()) {
            throw $this->createNotFoundException('Lien de signature invalide ou expiré.');
        }

        if ($quote->getSignedAt()) {
            return $this->render('quote_signature/already_signed.html.twig', ['quote' => $quote]);
        }

        if (!in_array($quote->getStatus(), ['sent', 'accepted'], true)) {
            return $this->render('quote_signature/unavailable.html.twig', ['quote' => $quote]);
        }

        return $this->render('quote_signature/sign.html.twig', ['quote' => $quote, 'token' => $token]);
    }

    #[Route('/{token}', name: '_submit', methods: ['POST'])]
    public function submit(string $token, Request $request, InvoiceRepository $repo, EntityManagerInterface $em): Response
    {
        $quote = $repo->findOneBy(['signatureToken' => $token]);

        if (!$quote || !$quote->isQuote() || $quote->getSignedAt()) {
            throw $this->createNotFoundException('Lien de signature invalide.');
        }

        if (!in_array($quote->getStatus(), ['sent', 'accepted'], true)) {
            throw $this->createAccessDeniedException('Ce devis ne peut plus être signé.');
        }

        $signatureData = $request->request->get('signature_data', '');
        if (!$signatureData || !str_starts_with($signatureData, 'data:image/')) {
            return $this->render('quote_signature/sign.html.twig', [
                'quote' => $quote,
                'token' => $token,
                'error' => 'Veuillez apposer votre signature avant de valider.',
            ]);
        }

        $signerName = trim($request->request->get('signer_name', ''));
        if (!$signerName) {
            return $this->render('quote_signature/sign.html.twig', [
                'quote' => $quote,
                'token' => $token,
                'error' => 'Veuillez indiquer votre nom complet.',
            ]);
        }

        $ip = $request->getClientIp() ?? '';

        $quote->setSignedAt(new \DateTimeImmutable())
              ->setSignerIp($ip)
              ->setSignatureData($signatureData)
              ->setStatus('accepted');

        $em->flush();

        return $this->render('quote_signature/confirmed.html.twig', ['quote' => $quote]);
    }
}
