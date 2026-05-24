<?php

namespace App\Controller;

use App\Entity\Accountant;
use App\Entity\AccountantInvitation;
use App\Entity\BalanceSheet;
use App\Entity\ExpertMessage;
use App\Repository\AccountantInvitationRepository;
use App\Repository\ExpertMessageRepository;
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
        ExpertMessageRepository $msgRepo,
    ): Response {
        $user         = $this->getUser();
        $myInvitation = $invRepo->findActiveByUser($user);

        $messages = [];
        $bilans   = [];

        if ($myInvitation && $myInvitation->getStatus() === AccountantInvitation::STATUS_ACCEPTED) {
            $messages = $msgRepo->findByInvitation($myInvitation);
            $bilans   = $em->getRepository(BalanceSheet::class)
                           ->findBy(['user' => $user], ['createdAt' => 'DESC']);
        }

        $accountants = $myInvitation ? [] : $em->getRepository(Accountant::class)->findBy([], ['lastName' => 'ASC']);

        return $this->render('experts/index.html.twig', [
            'accountants'  => $accountants,
            'myInvitation' => $myInvitation,
            'messages'     => $messages,
            'bilans'       => $bilans,
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
        if ($invRepo->findActiveByUser($this->getUser())) {
            $this->addFlash('error', 'Vous avez déjà une demande en cours ou un expert lié.');
            return $this->redirectToRoute('app_experts');
        }

        $inv = (new AccountantInvitation())
            ->setUser($this->getUser())
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

    #[Route('/experts/message', name: 'app_expert_message', methods: ['POST'])]
    public function sendMessage(
        Request $request,
        EntityManagerInterface $em,
        AccountantInvitationRepository $invRepo,
    ): Response {
        $invitation = $invRepo->findActiveByUser($this->getUser());
        if (!$invitation || $invitation->getStatus() !== AccountantInvitation::STATUS_ACCEPTED) {
            throw $this->createAccessDeniedException();
        }
        if (!$this->isCsrfTokenValid('expert_msg', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $content = trim($request->request->get('content', ''));
        if ($content !== '') {
            $msg = (new ExpertMessage())
                ->setInvitation($invitation)
                ->setSenderType(ExpertMessage::SENDER_CLIENT)
                ->setContent($content);
            $em->persist($msg);
            $em->flush();
        }

        return $this->redirectToRoute('app_experts', ['#' => 'messages']);
    }

    #[Route('/experts/bilans/{id}/submit', name: 'app_expert_bilan_submit', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function submitBilan(
        BalanceSheet $sheet,
        Request $request,
        EntityManagerInterface $em,
        AccountantInvitationRepository $invRepo,
    ): Response {
        $user = $this->getUser();
        if ($sheet->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }
        $invitation = $invRepo->findActiveByUser($user);
        if (!$invitation || $invitation->getStatus() !== AccountantInvitation::STATUS_ACCEPTED) {
            throw $this->createAccessDeniedException();
        }
        if (!$this->isCsrfTokenValid('submit_bilan_' . $sheet->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        if ($sheet->getStatus() === BalanceSheet::STATUS_DRAFT) {
            $sheet->setStatus(BalanceSheet::STATUS_PENDING_REVIEW);
            $em->flush();
            $this->addFlash('success', 'Bilan soumis à votre expert pour révision.');
        }

        return $this->redirectToRoute('app_experts', ['#' => 'bilans']);
    }

    #[Route('/experts/disconnect', name: 'app_expert_disconnect', methods: ['POST'])]
    public function disconnect(
        Request $request,
        EntityManagerInterface $em,
        AccountantInvitationRepository $invRepo,
    ): Response {
        $invitation = $invRepo->findActiveByUser($this->getUser());
        if (!$invitation) {
            return $this->redirectToRoute('app_experts');
        }
        if (!$this->isCsrfTokenValid('expert_disconnect', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $invitation->setStatus(AccountantInvitation::STATUS_DECLINED)
                   ->setRespondedAt(new \DateTimeImmutable());
        $em->flush();

        $this->addFlash('success', 'Expert-comptable dissocié. Vous pouvez en choisir un nouveau.');
        return $this->redirectToRoute('app_experts');
    }
}
