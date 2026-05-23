<?php

namespace App\Controller\Api;

use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/clients')]
final class ClientController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('', name: 'api_clients_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $clients = $this->entityManager->getRepository(Client::class)
            ->findBy(['user' => $user], ['createdAt' => 'DESC']);

        $data = array_map(function (Client $client) {
            return [
                'id' => $client->getId(),
                'name' => $client->getName(),
                'email' => $client->getEmail(),
                'phone' => $client->getPhone(),
                'address' => $client->getAddress(),
                'siret' => $client->getSiret(),
                'createdAt' => $client->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $clients);

        return $this->json([
            'success' => true,
            'clients' => $data
        ]);
    }

    #[Route('/{id}', name: 'api_clients_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $client = $this->entityManager->getRepository(Client::class)->find($id);

        if (!$client || $client->getUser() !== $user) {
            return $this->json(['success' => false, 'message' => 'Client non trouvé'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'client' => [
                'id' => $client->getId(),
                'name' => $client->getName(),
                'email' => $client->getEmail(),
                'phone' => $client->getPhone(),
                'address' => $client->getAddress(),
                'siret' => $client->getSiret(),
                'createdAt' => $client->getCreatedAt()->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    #[Route('', name: 'api_clients_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['name']) || !isset($data['email'])) {
            return $this->json([
                'success' => false,
                'message' => 'Nom et email requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        $client = new Client();
        $client->setName($data['name']);
        $client->setEmail($data['email']);
        $client->setPhone($data['phone'] ?? null);
        $client->setAddress($data['address'] ?? null);
        $client->setSiret($data['siret'] ?? null);
        $client->setUser($user);
        $client->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($client);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Client créé avec succès',
            'client' => [
                'id' => $client->getId(),
                'name' => $client->getName(),
                'email' => $client->getEmail(),
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_clients_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $client = $this->entityManager->getRepository(Client::class)->find($id);

        if (!$client || $client->getUser() !== $user) {
            return $this->json(['success' => false, 'message' => 'Client non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) $client->setName($data['name']);
        if (isset($data['email'])) $client->setEmail($data['email']);
        if (isset($data['phone'])) $client->setPhone($data['phone']);
        if (isset($data['address'])) $client->setAddress($data['address']);
        if (isset($data['siret'])) $client->setSiret($data['siret']);

        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Client mis à jour avec succès',
            'client' => [
                'id' => $client->getId(),
                'name' => $client->getName(),
                'email' => $client->getEmail(),
            ]
        ]);
    }

    #[Route('/{id}', name: 'api_clients_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $client = $this->entityManager->getRepository(Client::class)->find($id);

        if (!$client || $client->getUser() !== $user) {
            return $this->json(['success' => false, 'message' => 'Client non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($client);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Client supprimé avec succès'
        ]);
    }
}
