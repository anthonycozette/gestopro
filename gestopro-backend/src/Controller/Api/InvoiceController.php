<?php

namespace App\Controller\Api;

use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/invoices')]
final class InvoiceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('', name: 'api_invoices_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $invoices = $this->entityManager->getRepository(Invoice::class)
            ->findBy(['user' => $user], ['createdAt' => 'DESC']);

        $data = array_map(function (Invoice $invoice) {
            return [
                'id' => $invoice->getId(),
                'invoiceNumber' => $invoice->getInvoiceNumber(),
                'status' => $invoice->getStatus(),
                'invoiceDate' => $invoice->getInvoiceDate()->format('Y-m-d'),
                'dueDate' => $invoice->getDueDate()->format('Y-m-d'),
                'totalHT' => (float) $invoice->getTotalHT(),
                'totalTVA' => (float) $invoice->getTotalTVA(),
                'totalTTC' => (float) $invoice->getTotalTTC(),
                'client' => [
                    'id' => $invoice->getClient()->getId(),
                    'name' => $invoice->getClient()->getName(),
                    'email' => $invoice->getClient()->getEmail(),
                ],
                'createdAt' => $invoice->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $invoices);

        return $this->json([
            'success' => true,
            'invoices' => $data
        ]);
    }

    #[Route('/{id}', name: 'api_invoices_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $invoice = $this->entityManager->getRepository(Invoice::class)->find($id);

        if (!$invoice || $invoice->getUser() !== $user) {
            return $this->json(['success' => false, 'message' => 'Facture non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $items = array_map(function (InvoiceItem $item) {
            return [
                'id' => $item->getId(),
                'description' => $item->getDescription(),
                'quantity' => (float) $item->getQuantity(),
                'unitPrice' => (float) $item->getUnitPrice(),
                'vatRate' => (float) $item->getVatRate(),
                'totalHT' => (float) $item->getTotalHT(),
                'totalTVA' => (float) $item->getTotalTVA(),
                'totalTTC' => (float) $item->getTotalTTC(),
            ];
        }, $invoice->getInvoiceItems()->toArray());

        return $this->json([
            'success' => true,
            'invoice' => [
                'id' => $invoice->getId(),
                'invoiceNumber' => $invoice->getInvoiceNumber(),
                'status' => $invoice->getStatus(),
                'invoiceDate' => $invoice->getInvoiceDate()->format('Y-m-d'),
                'dueDate' => $invoice->getDueDate()->format('Y-m-d'),
                'totalHT' => (float) $invoice->getTotalHT(),
                'totalTVA' => (float) $invoice->getTotalTVA(),
                'totalTTC' => (float) $invoice->getTotalTTC(),
                'notes' => $invoice->getNotes(),
                'client' => [
                    'id' => $invoice->getClient()->getId(),
                    'name' => $invoice->getClient()->getName(),
                    'email' => $invoice->getClient()->getEmail(),
                ],
                'items' => $items,
                'createdAt' => $invoice->getCreatedAt()->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    #[Route('', name: 'api_invoices_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['clientId']) || !isset($data['invoiceNumber'])) {
            return $this->json([
                'success' => false,
                'message' => 'Client et numéro de facture requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier que le client appartient à l'utilisateur
        $client = $this->entityManager->getRepository(Client::class)->find($data['clientId']);
        if (!$client || $client->getUser() !== $user) {
            return $this->json(['success' => false, 'message' => 'Client non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $invoice = new Invoice();
        $invoice->setInvoiceNumber($data['invoiceNumber']);
        $invoice->setStatus($data['status'] ?? 'draft');
        $invoice->setInvoiceDate(new \DateTimeImmutable($data['invoiceDate'] ?? 'now'));
        $invoice->setDueDate(new \DateTimeImmutable($data['dueDate'] ?? '+30 days'));
        $invoice->setTotalHT($data['totalHT'] ?? 0);
        $invoice->setTotalTVA($data['totalTVA'] ?? 0);
        $invoice->setTotalTTC($data['totalTTC'] ?? 0);
        $invoice->setNotes($data['notes'] ?? null);
        $invoice->setUser($user);
        $invoice->setClient($client);
        $invoice->setCreatedAt(new \DateTimeImmutable());

        // Ajouter les lignes de facture si fournies
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $itemData) {
                $item = new InvoiceItem();
                $item->setDescription($itemData['description']);
                $item->setQuantity($itemData['quantity']);
                $item->setUnitPrice($itemData['unitPrice']);
                $item->setVatRate($itemData['vatRate'] ?? 20);
                $item->setTotalHT($itemData['totalHT']);
                $item->setTotalTVA($itemData['totalTVA']);
                $item->setTotalTTC($itemData['totalTTC']);
                $item->setInvoice($invoice);

                $this->entityManager->persist($item);
            }
        }

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Facture créée avec succès',
            'invoice' => [
                'id' => $invoice->getId(),
                'invoiceNumber' => $invoice->getInvoiceNumber(),
                'totalTTC' => (float) $invoice->getTotalTTC(),
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_invoices_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $invoice = $this->entityManager->getRepository(Invoice::class)->find($id);

        if (!$invoice || $invoice->getUser() !== $user) {
            return $this->json(['success' => false, 'message' => 'Facture non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['status'])) $invoice->setStatus($data['status']);
        if (isset($data['totalHT'])) $invoice->setTotalHT($data['totalHT']);
        if (isset($data['totalTVA'])) $invoice->setTotalTVA($data['totalTVA']);
        if (isset($data['totalTTC'])) $invoice->setTotalTTC($data['totalTTC']);
        if (isset($data['notes'])) $invoice->setNotes($data['notes']);

        $invoice->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Facture mise à jour avec succès'
        ]);
    }

    #[Route('/{id}', name: 'api_invoices_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $invoice = $this->entityManager->getRepository(Invoice::class)->find($id);

        if (!$invoice || $invoice->getUser() !== $user) {
            return $this->json(['success' => false, 'message' => 'Facture non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($invoice);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Facture supprimée avec succès'
        ]);
    }
}
