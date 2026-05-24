<?php

namespace App\Controller;

use App\Entity\Accountant;
use App\Entity\AccountantInvitation;
use App\Entity\BalanceSheet;
use App\Entity\ExpertMessage;
use App\Entity\User;
use App\Repository\AccountantInvitationRepository;
use App\Repository\BalanceSheetRepository;
use App\Repository\ExpertMessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/expert', name: 'expert_')]
class ExpertController extends AbstractController
{
    private function accountant(): Accountant
    {
        $a = $this->getUser();
        if (!$a instanceof Accountant) {
            throw $this->createAccessDeniedException();
        }
        return $a;
    }

    #[Route('', name: 'dashboard')]
    public function dashboard(BalanceSheetRepository $sheetRepo, AccountantInvitationRepository $invRepo): Response
    {
        $accountant      = $this->accountant();
        $sheets          = $sheetRepo->findForAccountant($accountant);
        $clients         = $invRepo->findAcceptedByAccountant($accountant);
        $pendingRequests = $invRepo->findPendingByAccountant($accountant);

        $pendingSheets = array_filter($sheets, fn($s) => $s->getStatus() === BalanceSheet::STATUS_PENDING_REVIEW);
        $others        = array_filter($sheets, fn($s) => $s->getStatus() !== BalanceSheet::STATUS_PENDING_REVIEW);

        return $this->render('expert/dashboard.html.twig', [
            'accountant'      => $accountant,
            'pending'         => array_values($pendingSheets),
            'others'          => array_values($others),
            'clientCount'     => count($clients),
            'pendingRequests' => $pendingRequests,
        ]);
    }

    #[Route('/requests/{id}/approve', name: 'request_approve', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function approveRequest(AccountantInvitation $invitation, Request $request, EntityManagerInterface $em): Response
    {
        $this->accountant();
        if (!$this->isCsrfTokenValid('inv_' . $invitation->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $invitation->setStatus(AccountantInvitation::STATUS_ACCEPTED)
                   ->setRespondedAt(new \DateTimeImmutable());
        $em->flush();

        $this->addFlash('success', $invitation->getUser()->getFirstName() . ' ' . $invitation->getUser()->getLastName() . ' est maintenant votre client.');
        return $this->redirectToRoute('expert_dashboard');
    }

    #[Route('/requests/{id}/decline', name: 'request_decline', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function declineRequest(AccountantInvitation $invitation, Request $request, EntityManagerInterface $em): Response
    {
        $this->accountant();
        if (!$this->isCsrfTokenValid('inv_' . $invitation->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $invitation->setStatus(AccountantInvitation::STATUS_DECLINED)
                   ->setRespondedAt(new \DateTimeImmutable());
        $em->flush();

        $this->addFlash('success', 'Demande refusée.');
        return $this->redirectToRoute('expert_dashboard');
    }

    #[Route('/profile', name: 'profile')]
    public function profile(Request $request, EntityManagerInterface $em): Response
    {
        $accountant = $this->accountant();
        $error      = null;

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('expert_profile', $request->request->get('_token'))) {
                throw $this->createAccessDeniedException();
            }
            $accountant->setFirm(trim($request->request->get('firm', '')) ?: null)
                       ->setRegistrationNumber(trim($request->request->get('registrationNumber', '')) ?: null)
                       ->setBio(trim($request->request->get('bio', '')) ?: null);
            $em->flush();
            $this->addFlash('success', 'Profil mis à jour.');
            return $this->redirectToRoute('expert_profile');
        }

        return $this->render('expert/profile.html.twig', ['accountant' => $accountant, 'error' => $error]);
    }

    #[Route('/clients', name: 'clients')]
    public function clients(AccountantInvitationRepository $invRepo): Response
    {
        $accountant  = $this->accountant();
        $invitations = $invRepo->findBy(['accountant' => $accountant], ['createdAt' => 'DESC']);

        return $this->render('expert/clients.html.twig', [
            'invitations' => $invitations,
            'accountant'  => $accountant,
        ]);
    }

    #[Route('/clients/{id}', name: 'client_detail', requirements: ['id' => '\d+'])]
    public function clientDetail(
        AccountantInvitation $invitation,
        ExpertMessageRepository $msgRepo,
        EntityManagerInterface $em,
    ): Response {
        $accountant = $this->accountant();
        if ($invitation->getAccountant() !== $accountant || $invitation->getStatus() !== AccountantInvitation::STATUS_ACCEPTED) {
            throw $this->createAccessDeniedException();
        }

        $messages = $msgRepo->findByInvitation($invitation);
        $bilans   = $em->getRepository(BalanceSheet::class)
                       ->findBy(['user' => $invitation->getUser()], ['createdAt' => 'DESC']);

        return $this->render('expert/client_detail.html.twig', [
            'accountant'  => $accountant,
            'invitation'  => $invitation,
            'messages'    => $messages,
            'bilans'      => $bilans,
        ]);
    }

    #[Route('/clients/{id}/message', name: 'client_message', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function sendMessage(
        AccountantInvitation $invitation,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $accountant = $this->accountant();
        if ($invitation->getAccountant() !== $accountant) {
            throw $this->createAccessDeniedException();
        }
        if (!$this->isCsrfTokenValid('expert_msg_' . $invitation->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $content = trim($request->request->get('content', ''));
        if ($content !== '') {
            $msg = (new ExpertMessage())
                ->setInvitation($invitation)
                ->setSenderType(ExpertMessage::SENDER_EXPERT)
                ->setContent($content);
            $em->persist($msg);
            $em->flush();
        }

        return $this->redirectToRoute('expert_client_detail', ['id' => $invitation->getId(), '#' => 'messages']);
    }

    #[Route('/bilans/{id}', name: 'bilan_show')]
    public function bilanShow(BalanceSheet $sheet, AccountantInvitationRepository $invRepo): Response
    {
        $accountant = $this->accountant();
        $this->checkSheetAccess($sheet, $accountant, $invRepo);

        return $this->render('expert/bilan.html.twig', [
            'sheet'      => $sheet,
            'accountant' => $accountant,
        ]);
    }

    #[Route('/bilans/{id}/annotate', name: 'bilan_annotate', methods: ['POST'])]
    public function annotate(BalanceSheet $sheet, Request $request, EntityManagerInterface $em, AccountantInvitationRepository $invRepo): Response
    {
        $accountant = $this->accountant();
        $this->checkSheetAccess($sheet, $accountant, $invRepo);

        if (!$this->isCsrfTokenValid('annotate_' . $sheet->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $comment = trim($request->request->get('comment', ''));
        $sheet->setAccountantComment($comment ?: null)
              ->setStatus(BalanceSheet::STATUS_ANNOTATED)
              ->setAccountant($accountant);

        $em->flush();
        $this->addFlash('success', 'Annotations enregistrées.');

        return $this->redirectToRoute('expert_bilan_show', ['id' => $sheet->getId()]);
    }

    #[Route('/bilans/{id}/validate', name: 'bilan_validate', methods: ['POST'])]
    public function validate(BalanceSheet $sheet, Request $request, EntityManagerInterface $em, AccountantInvitationRepository $invRepo): Response
    {
        $accountant = $this->accountant();
        $this->checkSheetAccess($sheet, $accountant, $invRepo);

        if (!$this->isCsrfTokenValid('validate_' . $sheet->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $sheet->setStatus(BalanceSheet::STATUS_VALIDATED)
              ->setValidatedAt(new \DateTimeImmutable())
              ->setAccountant($accountant);

        $em->flush();
        $this->addFlash('success', 'Bilan validé et tamponné.');

        return $this->redirectToRoute('expert_bilan_show', ['id' => $sheet->getId()]);
    }

    #[Route('/invite', name: 'invite')]
    public function invite(Request $request, EntityManagerInterface $em, AccountantInvitationRepository $invRepo): Response
    {
        $accountant = $this->accountant();
        $error      = null;
        $link       = null;

        if ($request->isMethod('POST')) {
            $email = trim($request->request->get('email', ''));
            $user  = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$email) {
                $error = 'Veuillez saisir une adresse email.';
            } elseif (!$user) {
                $error = 'Aucun compte GestoPro trouvé pour cet email.';
            } elseif ($invRepo->existsPendingForEmail($accountant, $email)) {
                $error = 'Une invitation est déjà en attente pour cet utilisateur.';
            } else {
                $inv = new AccountantInvitation();
                $inv->setAccountant($accountant)->setUser($user);
                $em->persist($inv);
                $em->flush();

                $link = $this->generateUrl('app_invitation_respond', ['token' => $inv->getToken()], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);
                $this->addFlash('success', 'Invitation créée pour ' . $email . '.');
            }
        }

        return $this->render('expert/invite.html.twig', [
            'accountant' => $accountant,
            'error'      => $error,
            'link'       => $link,
        ]);
    }

    private function checkSheetAccess(BalanceSheet $sheet, Accountant $accountant, AccountantInvitationRepository $invRepo): void
    {
        $linked = $invRepo->findOneBy([
            'accountant' => $accountant,
            'user'       => $sheet->getUser(),
            'status'     => AccountantInvitation::STATUS_ACCEPTED,
        ]);

        if (!$linked) {
            throw $this->createAccessDeniedException();
        }
    }
}
