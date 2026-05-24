<?php

namespace App\Controller;

use App\Entity\AiConversation;
use App\Repository\AiConversationRepository;
use App\Service\AiAssistantService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/assistant', name: 'app_ai')]
class AiController extends AbstractController
{
    #[Route('', name: '', methods: ['GET'])]
    public function index(AiConversationRepository $repo, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $conversations = $repo->findBy(['user' => $user], ['createdAt' => 'DESC']);

        if (empty($conversations)) {
            $conv = new AiConversation();
            $conv->setTitle('Nouvelle conversation')->setUser($user);
            $em->persist($conv);
            $em->flush();
            return $this->redirectToRoute('app_ai_show', ['id' => $conv->getId()]);
        }

        return $this->redirectToRoute('app_ai_show', ['id' => $conversations[0]->getId()]);
    }

    #[Route('/new', name: '_new', methods: ['POST'])]
    public function new(EntityManagerInterface $em): Response
    {
        $conv = new AiConversation();
        $conv->setTitle('Nouvelle conversation')->setUser($this->getUser());
        $em->persist($conv);
        $em->flush();

        return $this->redirectToRoute('app_ai_show', ['id' => $conv->getId()]);
    }

    #[Route('/{id}', name: '_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(AiConversation $conv, AiConversationRepository $repo): Response
    {
        if ($conv->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $conversations = $repo->findBy(['user' => $this->getUser()], ['createdAt' => 'DESC']);

        return $this->render('ai/show.html.twig', [
            'conversation'  => $conv,
            'conversations' => $conversations,
        ]);
    }

    #[Route('/{id}/message', name: '_message', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function sendMessage(
        AiConversation $conv,
        Request $request,
        AiAssistantService $assistant,
        EntityManagerInterface $em,
    ): JsonResponse {
        if ($conv->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->getUser()->isPro()) {
            return $this->json(['error' => "L'assistant IA est réservé aux plans Pro et Expert."], 403);
        }

        $content = trim($request->request->get('message', ''));
        if ($content === '') {
            return $this->json(['error' => 'Message vide.'], 400);
        }

        try {
            $assistantMsg = $assistant->chat($conv, $content);
            $em->flush();

            return $this->json([
                'content'      => $assistantMsg->getContent(),
                'outputTokens' => $assistantMsg->getOutputTokens(),
            ]);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Erreur lors de la communication avec l\'IA : ' . $e->getMessage()], 500);
        }
    }

    #[Route('/{id}/delete', name: '_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(AiConversation $conv, EntityManagerInterface $em): Response
    {
        if ($conv->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $em->remove($conv);
        $em->flush();

        return $this->redirectToRoute('app_ai');
    }
}
