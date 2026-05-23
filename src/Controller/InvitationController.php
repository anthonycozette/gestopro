<?php

namespace App\Controller;

use App\Entity\AccountantInvitation;
use App\Repository\AccountantInvitationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/invitation', name: 'app_invitation')]
class InvitationController extends AbstractController
{
    #[Route('/{token}', name: '_respond')]
    public function respond(string $token, AccountantInvitationRepository $repo): Response
    {
        $invitation = $repo->findByToken($token);

        if (!$invitation) {
            throw $this->createNotFoundException('Invitation introuvable.');
        }

        if ($invitation->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Cette invitation ne vous est pas destinée.');
        }

        return $this->render('invitation/respond.html.twig', ['invitation' => $invitation]);
    }

    #[Route('/{token}/accept', name: '_accept', methods: ['POST'])]
    public function accept(string $token, Request $request, AccountantInvitationRepository $repo, EntityManagerInterface $em): Response
    {
        $invitation = $this->getValidInvitation($token, $repo);

        if (!$this->isCsrfTokenValid('invitation_' . $token, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        if ($invitation->isPending() && !$invitation->isExpired()) {
            $invitation->setStatus(AccountantInvitation::STATUS_ACCEPTED)
                       ->setRespondedAt(new \DateTimeImmutable());
            $em->flush();
            $this->addFlash('success', 'Vous avez accepté la mise en relation avec ' . $invitation->getAccountant()->getFullName() . '.');
        }

        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/{token}/decline', name: '_decline', methods: ['POST'])]
    public function decline(string $token, Request $request, AccountantInvitationRepository $repo, EntityManagerInterface $em): Response
    {
        $invitation = $this->getValidInvitation($token, $repo);

        if (!$this->isCsrfTokenValid('invitation_' . $token, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        if ($invitation->isPending()) {
            $invitation->setStatus(AccountantInvitation::STATUS_DECLINED)
                       ->setRespondedAt(new \DateTimeImmutable());
            $em->flush();
            $this->addFlash('success', 'Invitation refusée.');
        }

        return $this->redirectToRoute('app_dashboard');
    }

    private function getValidInvitation(string $token, AccountantInvitationRepository $repo): AccountantInvitation
    {
        $invitation = $repo->findByToken($token);

        if (!$invitation) {
            throw $this->createNotFoundException();
        }
        if ($invitation->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $invitation;
    }
}
