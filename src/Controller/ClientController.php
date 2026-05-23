<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\User;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/clients', name: 'app_client')]
class ClientController extends AbstractController
{
    #[Route('', name: 's')]
    public function index(ClientRepository $repo): Response
    {
        $clients = $repo->findBy(['user' => $this->getUser()], ['createdAt' => 'DESC']);

        return $this->render('client/index.html.twig', ['clients' => $clients]);
    }

    #[Route('/new', name: '_new')]
    public function new(Request $request, EntityManagerInterface $em, ClientRepository $repo): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getPlan() === User::PLAN_FREE && count($repo->findBy(['user' => $user])) >= 5) {
            $this->addFlash('error', 'Limite de 5 clients atteinte sur le plan gratuit. Passez au plan Pro pour continuer.');
            return $this->redirectToRoute('app_clients');
        }

        $client = new Client();
        $error  = $this->handleForm($request, $client, $em);

        if ($error === null && $request->isMethod('POST')) {
            $this->addFlash('success', 'Client créé avec succès.');
            return $this->redirectToRoute('app_clients');
        }

        return $this->render('client/form.html.twig', ['client' => $client, 'error' => $error, 'title' => 'Nouveau client']);
    }

    #[Route('/{id}/edit', name: '_edit')]
    public function edit(Client $client, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        if ($client->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $error = $this->handleForm($request, $client, $em);

        if ($error === null && $request->isMethod('POST')) {
            $this->addFlash('success', 'Client modifié.');
            return $this->redirectToRoute('app_clients');
        }

        return $this->render('client/form.html.twig', ['client' => $client, 'error' => $error, 'title' => 'Modifier le client']);
    }

    #[Route('/{id}/delete', name: '_delete', methods: ['POST'])]
    public function delete(Client $client, Request $request, EntityManagerInterface $em): Response
    {
        if ($client->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete_client_' . $client->getId(), $request->request->get('_token'))) {
            $em->remove($client);
            $em->flush();
            $this->addFlash('success', 'Client supprimé.');
        }

        return $this->redirectToRoute('app_clients');
    }

    #[Route('/{id}', name: '_show')]
    public function show(Client $client): Response
    {
        if ($client->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('client/show.html.twig', ['client' => $client]);
    }

    private function handleForm(Request $request, Client $client, EntityManagerInterface $em): ?string
    {
        if (!$request->isMethod('POST')) {
            return false;
        }

        $name = trim($request->request->get('name', ''));
        if (!$name) {
            return 'Le nom est obligatoire.';
        }

        $client->setName($name)
               ->setEmail($request->request->get('email') ?: null)
               ->setSiret($request->request->get('siret') ?: null)
               ->setPhone($request->request->get('phone') ?: null)
               ->setAddress($request->request->get('address') ?: null)
               ->setPostalCode($request->request->get('postalCode') ?: null)
               ->setCity($request->request->get('city') ?: null)
               ->setCountry($request->request->get('country') ?: 'FR')
               ->setUser($this->getUser());

        $em->persist($client);
        $em->flush();

        return null;
    }
}
