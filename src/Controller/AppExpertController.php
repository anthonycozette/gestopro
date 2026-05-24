<?php

namespace App\Controller;

use App\Entity\Accountant;
use App\Entity\AccountantInvitation;
use App\Repository\AccountantInvitationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AppExpertController extends AbstractController
{
    #[Route('/experts', name: 'app_experts')]
    public function index(
        EntityManagerInterface $em,
        AccountantInvitationRepository $invRepo,
    ): Response {
        $accountants  = $em->getRepository(Accountant::class)->findBy([], ['lastName' => 'ASC']);
        $myInvitation = $invRepo->findActiveByUser($this->getUser());

        return $this->render('experts/index.html.twig', [
            'accountants'  => $accountants,
            'myInvitation' => $myInvitation,
        ]);
    }

    #[Route('/experts/{id}/request', name: 'app_expert_request', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function request(
        Accountant $accountant,
        Request $request,
        EntityManagerInterface $em,
        AccountantInvitationRepository $invRepo,
    ): Response {
        if (!$this->isCsrfTokenValid('expert_connect_' . $accountant->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->getUser();

        if ($invRepo->findActiveByUser($user)) {
            $this->addFlash('error', 'Vous avez déjà une demande en cours ou un expert lié.');
            return $this->redirectToRoute('app_experts');
        }

        $inv = (new AccountantInvitation())
            ->setUser($user)
            ->setAccountant($accountant)
            ->setExpiresAt(null);

        $em->persist($inv);
        $em->flush();

        $this->addFlash('success', 'Demande envoyée à ' . $accountant->getFirstName() . ' ' . $accountant->getLastName() . '.');
        return $this->redirectToRoute('app_experts');
    }

    #[Route('/expert-request/{id}/cancel', name: 'app_expert_cancel', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function cancel(
        AccountantInvitation $invitation,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        if ($invitation->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        if (!$this->isCsrfTokenValid('expert_cancel_' . $invitation->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $em->remove($invitation);
        $em->flush();

        $this->addFlash('success', 'Demande annulée.');
        return $this->redirectToRoute('app_experts');
    }
}
