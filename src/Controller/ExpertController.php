<?php

namespace App\Controller;

use App\Entity\Accountant;
use App\Entity\AccountantInvitation;
use App\Entity\BalanceSheet;
use App\Entity\User;
use App\Repository\AccountantInvitationRepository;
use App\Repository\BalanceSheetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
        $accountant = $this->accountant();
        $sheets     = $sheetRepo->findForAccountant($accountant);
        $clients    = $invRepo->findAcceptedByAccountant($accountant);

        $pending  = array_filter($sheets, fn($s) => $s->getStatus() === BalanceSheet::STATUS_PENDING_REVIEW);
        $others   = array_filter($sheets, fn($s) => $s->getStatus() !== BalanceSheet::STATUS_PENDING_REVIEW);

        return $this->render('expert/dashboard.html.twig', [
            'accountant'    => $accountant,
            'pending'       => array_values($pending),
            'others'        => array_values($others),
            'clientCount'   => count($clients),
        ]);
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
