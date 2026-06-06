<?php

namespace App\Controller;

use App\Entity\Accountant;
use App\Entity\AccountantInvitation;
use App\Entity\BalanceSheet;
use App\Entity\ExpertMessage;
use App\Entity\User;
use App\Repository\AccountantInvitationRepository;
use App\Repository\ExpertMessageRepository;
use App\Repository\InvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/expert/cabinet', name: 'expert_')]
class AccountantDashboardController extends AbstractController
{
    public function __construct(
        private readonly AccountantInvitationRepository $invRepo,
        private readonly ExpertMessageRepository $msgRepo,
        private readonly EntityManagerInterface $em,
        private readonly InvoiceRepository $invoiceRepo,
    ) {}

    private function accountant(): Accountant
    {
        $user = $this->getUser();
        if (!$user instanceof Accountant) {
            throw $this->createAccessDeniedException('Accès réservé aux experts-comptables.');
        }
        return $user;
    }

    private function topbarVars(): array
    {
        $accountant = $this->accountant();
        $accepted   = $this->invRepo->findAcceptedByAccountant($accountant);
        $now        = new \DateTimeImmutable();

        $pendingCount       = 0;
        $msgCount           = 0;
        $validatedToday     = 0;

        foreach ($accepted as $inv) {
            $pendingCount += $this->em->getRepository(BalanceSheet::class)->count([
                'user'   => $inv->getUser(),
                'status' => BalanceSheet::STATUS_PENDING_REVIEW,
            ]);

            foreach ($this->msgRepo->findByInvitation($inv) as $msg) {
                if ($msg->isFromClient()) {
                    $msgCount++;
                }
            }

            $validated = $this->em->getRepository(BalanceSheet::class)->findBy([
                'user'       => $inv->getUser(),
                'accountant' => $accountant,
                'status'     => BalanceSheet::STATUS_VALIDATED,
            ]);
            foreach ($validated as $sheet) {
                if ($sheet->getValidatedAt() && $sheet->getValidatedAt()->format('Y-m-d') === $now->format('Y-m-d')) {
                    $validatedToday++;
                }
            }
        }

        return [
            'topbar_pending'         => $pendingCount,
            'topbar_msgs'            => $msgCount,
            'topbar_clients'         => count($accepted),
            'topbar_validated_today' => $validatedToday,
        ];
    }

    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(): Response
    {
        $accountant = $this->accountant();
        $accepted   = $this->invRepo->findAcceptedByAccountant($accountant);
        $pending    = $this->invRepo->findPendingByAccountant($accountant);
        $now        = new \DateTimeImmutable();

        $pendingBilans      = [];
        $validatedThisMonth = 0;

        foreach ($accepted as $inv) {
            $sheets = $this->em->getRepository(BalanceSheet::class)->findBy(
                ['user' => $inv->getUser(), 'status' => BalanceSheet::STATUS_PENDING_REVIEW],
                ['createdAt' => 'ASC']
            );
            foreach ($sheets as $sheet) {
                $ageHours = (int) (($now->getTimestamp() - $sheet->getCreatedAt()->getTimestamp()) / 3600);
                $pendingBilans[] = ['sheet' => $sheet, 'invitation' => $inv, 'ageHours' => $ageHours];
            }

            $validated = $this->em->getRepository(BalanceSheet::class)->findBy([
                'user'       => $inv->getUser(),
                'accountant' => $accountant,
                'status'     => BalanceSheet::STATUS_VALIDATED,
            ]);
            foreach ($validated as $sheet) {
                if ($sheet->getValidatedAt() && $sheet->getValidatedAt()->format('Y-m') === $now->format('Y-m')) {
                    $validatedThisMonth++;
                }
            }
        }

        // Build recent activity across all clients
        $recentActivity = [];
        foreach ($accepted as $inv) {
            foreach ($this->msgRepo->findByInvitation($inv) as $msg) {
                $recentActivity[] = [
                    'type'     => $msg->isFromClient() ? 'msg_client' : 'msg_expert',
                    'who'      => $msg->isFromClient() ? $inv->getUser()->getFirstName() : 'Vous',
                    'text'     => $msg->isFromClient() ? 'a envoyé un message' : 'avez répondu à ' . $inv->getUser()->getFirstName(),
                    'date'     => $msg->getCreatedAt(),
                ];
            }
            foreach ($this->em->getRepository(BalanceSheet::class)->findBy(['user' => $inv->getUser()]) as $sheet) {
                $recentActivity[] = [
                    'type'  => 'bilan_' . $sheet->getStatus(),
                    'who'   => $inv->getUser()->getFirstName() . ' ' . $inv->getUser()->getLastName(),
                    'text'  => match($sheet->getStatus()) {
                        'pending_review' => 'a soumis son bilan ' . $sheet->getPeriod() . ' pour révision',
                        'validated'      => 'Bilan ' . $sheet->getPeriod() . ' validé',
                        'annotated'      => 'Bilan ' . $sheet->getPeriod() . ' annoté',
                        default          => 'a créé le bilan ' . $sheet->getPeriod(),
                    },
                    'date'  => $sheet->getValidatedAt() ?? $sheet->getCreatedAt(),
                ];
            }
        }
        usort($recentActivity, fn($a, $b) => $b['date'] <=> $a['date']);
        $recentActivity = array_slice($recentActivity, 0, 8);

        // Risk alerts: pending invitations + bilans >7 days in review
        $riskAlerts = [];
        foreach ($accepted as $inv) {
            $sheets = $this->em->getRepository(BalanceSheet::class)->findBy(
                ['user' => $inv->getUser(), 'status' => BalanceSheet::STATUS_PENDING_REVIEW],
                ['createdAt' => 'ASC']
            );
            foreach ($sheets as $sheet) {
                $ageDays = (int) (($now->getTimestamp() - $sheet->getCreatedAt()->getTimestamp()) / 86400);
                if ($ageDays >= 5) {
                    $riskAlerts[] = [
                        'client'  => $inv->getUser()->getFirstName() . ' ' . $inv->getUser()->getLastName(),
                        'type'    => 'Bilan non traité',
                        'delta'   => 'J+' . $ageDays,
                        'detail'  => 'Bilan ' . $sheet->getPeriod() . ' en attente depuis ' . $ageDays . ' jours',
                        'color'   => '#dc2626',
                    ];
                }
            }
            $caYear = $this->invoiceRepo->getYearRevenue($inv->getUser(), (int) $now->format('Y'));
            if ($caYear >= 77700 * 0.85) {
                $riskAlerts[] = [
                    'client'  => $inv->getUser()->getFirstName() . ' ' . $inv->getUser()->getLastName(),
                    'type'    => 'Plafond AE proche',
                    'delta'   => (int) round(($caYear / 77700) * 100) . '%',
                    'detail'  => number_format($caYear, 0, ',', ' ') . ' € / 77 700 €',
                    'color'   => '#d97706',
                ];
            }
        }

        return $this->render('accountant/dashboard.html.twig', array_merge($this->topbarVars(), [
            'accepted'           => $accepted,
            'pending'            => $pending,
            'pendingBilans'      => $pendingBilans,
            'validatedThisMonth' => $validatedThisMonth,
            'riskAlerts'         => array_slice($riskAlerts, 0, 4),
            'recentActivity'     => $recentActivity,
            'active_nav'         => 'dashboard',
        ]));
    }

    #[Route('/clients', name: 'clients')]
    public function clients(): Response
    {
        $accountant = $this->accountant();
        $accepted   = $this->invRepo->findAcceptedByAccountant($accountant);
        $pending    = $this->invRepo->findPendingByAccountant($accountant);
        $year       = (int) date('Y');
        $plafond    = 77700;
        $now        = new \DateTimeImmutable();

        $clientsData = [];
        $totalCa     = 0;
        $riskCount   = 0;
        $bilansYtd   = 0;

        foreach ($accepted as $inv) {
            $user    = $inv->getUser();
            $caYear  = $this->invoiceRepo->getYearRevenue($user, $year);
            $pct     = $plafond > 0 ? min(100, (int) round(($caYear / $plafond) * 100)) : 0;
            $totalCa += $caYear;

            $latestBilan = $this->em->getRepository(BalanceSheet::class)->findOneBy(
                ['user' => $user],
                ['createdAt' => 'DESC']
            );

            if ($latestBilan && $latestBilan->getStatus() === BalanceSheet::STATUS_VALIDATED) {
                $bilansYtd++;
            }

            $msgs     = $this->msgRepo->findByInvitation($inv);
            $msgCount = count(array_filter($msgs, fn($m) => $m->isFromClient()));
            $lastMsg  = $msgs ? end($msgs) : null;

            $risk = match(true) {
                $pct >= 85 => 'high',
                $pct >= 65 => 'med',
                default    => 'low',
            };

            if ($risk !== 'low') {
                $riskCount++;
            }

            $clientsData[] = [
                'invitation'  => $inv,
                'user'        => $user,
                'caYear'      => $caYear,
                'pct'         => $pct,
                'latestBilan' => $latestBilan,
                'msgCount'    => $msgCount,
                'lastMsg'     => $lastMsg,
                'risk'        => $risk,
            ];
        }

        return $this->render('accountant/clients.html.twig', array_merge($this->topbarVars(), [
            'clientsData' => $clientsData,
            'pending'     => $pending,
            'plafond'     => $plafond,
            'year'        => $year,
            'totalCa'     => $totalCa,
            'riskCount'   => $riskCount,
            'bilansYtd'   => $bilansYtd,
            'active_nav'  => 'clients',
        ]));
    }

    #[Route('/bilan/{id}', name: 'bilan_show', requirements: ['id' => '\d+'])]
    public function bilanShow(BalanceSheet $sheet): Response
    {
        $accountant = $this->accountant();
        $invitation = $this->invRepo->findOneBy([
            'user'       => $sheet->getUser(),
            'accountant' => $accountant,
            'status'     => AccountantInvitation::STATUS_ACCEPTED,
        ]);

        if (!$invitation) {
            throw $this->createAccessDeniedException();
        }

        $messages = $this->msgRepo->findByInvitation($invitation);

        return $this->render('accountant/bilan_show.html.twig', array_merge($this->topbarVars(), [
            'sheet'      => $sheet,
            'invitation' => $invitation,
            'messages'   => $messages,
            'active_nav' => 'queue',
        ]));
    }

    #[Route('/invitation/{id}/accept', name: 'invitation_accept', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function acceptInvitation(AccountantInvitation $invitation, Request $request): Response
    {
        if ($invitation->getAccountant()?->getId() !== $this->accountant()->getId()) {
            throw $this->createAccessDeniedException();
        }

        $invitation->setStatus(AccountantInvitation::STATUS_ACCEPTED)->setRespondedAt(new \DateTimeImmutable());
        $this->em->flush();

        $name = $invitation->getUser()->getFirstName() . ' ' . $invitation->getUser()->getLastName();
        $this->addFlash('success', $name . ' ajouté(e) à vos clients.');
        return $this->redirectToRoute('expert_clients');
    }

    #[Route('/invitation/{id}/decline', name: 'invitation_decline', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function declineInvitation(AccountantInvitation $invitation, Request $request): Response
    {
        if ($invitation->getAccountant()?->getId() !== $this->accountant()->getId()) {
            throw $this->createAccessDeniedException();
        }

        $invitation->setStatus(AccountantInvitation::STATUS_DECLINED)->setRespondedAt(new \DateTimeImmutable());
        $this->em->flush();

        $this->addFlash('info', 'Demande refusée.');
        return $this->redirectToRoute('expert_clients');
    }

    #[Route('/bilan/{id}/validate', name: 'bilan_validate', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function validateBilan(BalanceSheet $sheet, Request $request): Response
    {
        $accountant = $this->accountant();
        if (!$this->invRepo->findOneBy(['user' => $sheet->getUser(), 'accountant' => $accountant, 'status' => AccountantInvitation::STATUS_ACCEPTED])) {
            throw $this->createAccessDeniedException();
        }
        $sheet->setStatus(BalanceSheet::STATUS_VALIDATED)
              ->setAccountant($accountant)
              ->setValidatedAt(new \DateTimeImmutable());

        if ($accountant->getStampPath()) {
            $sheet->setStampPath($accountant->getStampPath());
        }

        $this->em->flush();
        $this->addFlash('success', 'Bilan validé et signé.');
        return $this->redirectToRoute('expert_bilan_show', ['id' => $sheet->getId()]);
    }

    #[Route('/bilan/{id}/comment', name: 'bilan_comment', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function commentBilan(BalanceSheet $sheet, Request $request): Response
    {
        $accountant = $this->accountant();
        if (!$this->invRepo->findOneBy(['user' => $sheet->getUser(), 'accountant' => $accountant, 'status' => AccountantInvitation::STATUS_ACCEPTED])) {
            throw $this->createAccessDeniedException();
        }
        $comment = trim($request->request->get('comment', ''));
        if ($comment !== '') {
            $sheet->setAccountantComment($comment)->setStatus(BalanceSheet::STATUS_ANNOTATED);
            $this->em->flush();
        }

        return $this->redirectToRoute('expert_bilan_show', ['id' => $sheet->getId()]);
    }

    #[Route('/bilan/{id}/message', name: 'bilan_message', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function sendMessage(BalanceSheet $sheet, Request $request): Response
    {
        $accountant = $this->accountant();
        $invitation = $this->invRepo->findOneBy([
            'user'       => $sheet->getUser(),
            'accountant' => $accountant,
            'status'     => AccountantInvitation::STATUS_ACCEPTED,
        ]);

        if (!$invitation) {
            throw $this->createAccessDeniedException();
        }
        $content = trim($request->request->get('content', ''));
        if ($content !== '') {
            $msg = (new ExpertMessage())
                ->setInvitation($invitation)
                ->setSenderType(ExpertMessage::SENDER_EXPERT)
                ->setContent($content);
            $this->em->persist($msg);
            $this->em->flush();
        }

        return $this->redirectToRoute('expert_bilan_show', ['id' => $sheet->getId()]);
    }

    #[Route('/queue', name: 'queue')]
    public function queue(): Response
    {
        $accountant = $this->accountant();
        $accepted   = $this->invRepo->findAcceptedByAccountant($accountant);
        $now        = new \DateTimeImmutable();
        $year       = (int) $now->format('Y');

        $queueItems    = [];
        $urgentCount   = 0;
        $readyCount    = 0;
        $waitingCount  = 0;
        $totalSections = 0;

        foreach ($accepted as $inv) {
            $user   = $inv->getUser();
            $bilans = $this->em->getRepository(BalanceSheet::class)->findBy(
                ['user' => $user, 'status' => [BalanceSheet::STATUS_PENDING_REVIEW, BalanceSheet::STATUS_ANNOTATED]],
                ['createdAt' => 'ASC']
            );

            foreach ($bilans as $sheet) {
                $ageHours = (int) round(($now->getTimestamp() - $sheet->getCreatedAt()->getTimestamp()) / 3600);
                $ageDays  = (int) floor($ageHours / 24);
                $daysLeft = max(0, 7 - $ageDays);
                $deadline = \DateTime::createFromImmutable($sheet->getCreatedAt())->modify('+7 days');

                $annotations = json_decode($sheet->getAccountantAnnotations() ?? '[]', true);
                $flagsCount  = is_array($annotations) ? count($annotations) : 0;

                $msgs     = $this->msgRepo->findByInvitation($inv);
                $msgCount = count(array_filter($msgs, fn($m) => $m->isFromClient()));
                $caYear   = $this->invoiceRepo->getYearRevenue($user, $year);

                $isAnnotated  = $sheet->getStatus() === BalanceSheet::STATUS_ANNOTATED;
                $progress     = $isAnnotated ? ($flagsCount === 0 ? 89 : 55) : 11;
                $sectionsDone = $isAnnotated ? ($flagsCount === 0 ? 8 : 5) : 1;
                $slaHours     = max(0, (int) round(4 * (1 - $progress / 100)));

                $isUrgent  = $daysLeft <= 1;
                $isReady   = $isAnnotated && $flagsCount === 0;
                $isWaiting = $flagsCount > 0;
                $aiScore   = $sheet->getAiAnalysis() ? min(99, 70 + $sectionsDone * 3) : 0;

                if ($isUrgent) $urgentCount++;
                if ($isReady)  $readyCount++;
                if ($isWaiting || $msgCount > 0) $waitingCount++;
                $totalSections += $sectionsDone;

                $queueItems[] = [
                    'sheet'        => $sheet,
                    'invitation'   => $inv,
                    'user'         => $user,
                    'ageHours'     => $ageHours,
                    'progress'     => $progress,
                    'sectionsDone' => $sectionsDone,
                    'flagsCount'   => $flagsCount,
                    'msgCount'     => $msgCount,
                    'caYear'       => $caYear,
                    'daysLeft'     => $daysLeft,
                    'deadline'     => $deadline,
                    'slaHours'     => $slaHours,
                    'isUrgent'     => $isUrgent,
                    'isReady'      => $isReady,
                    'isWaiting'    => $isWaiting,
                    'aiScore'      => $aiScore,
                ];
            }
        }

        usort($queueItems, static function (array $a, array $b): int {
            if ($a['isUrgent'] !== $b['isUrgent']) return $a['isUrgent'] ? -1 : 1;
            return $b['ageHours'] - $a['ageHours'];
        });

        $count       = count($queueItems);
        $avgSections = $count > 0 ? (int) round($totalSections / $count) : 0;
        $avgPct      = $count > 0
            ? (int) round(array_sum(array_column($queueItems, 'progress')) / $count)
            : 0;
        $estHoursDay = min(8, $count * 4);

        $weekDays  = [];
        $weekStart = new \DateTime('monday this week');
        $dayLabels = ['LUN', 'MAR', 'MER', 'JEU', 'VEN', 'SAM', 'DIM'];
        for ($i = 0; $i < 7; $i++) {
            $day    = (clone $weekStart)->modify("+$i days");
            $dayStr = $day->format('Y-m-d');
            $cnt    = 0;
            foreach ($queueItems as $item) {
                if ($item['sheet']->getCreatedAt()->format('Y-m-d') === $dayStr) {
                    $cnt++;
                }
            }
            $weekDays[] = [
                'label'     => $dayLabels[$i] . ' ' . $day->format('d'),
                'hours'     => $cnt * 4,
                'bilans'    => $cnt,
                'today'     => $day->format('Y-m-d') === (new \DateTime())->format('Y-m-d'),
                'isWeekend' => $i >= 5,
            ];
        }

        return $this->render('accountant/queue.html.twig', array_merge($this->topbarVars(), [
            'queueItems'   => $queueItems,
            'urgentCount'  => $urgentCount,
            'readyCount'   => $readyCount,
            'waitingCount' => $waitingCount,
            'avgSections'  => $avgSections,
            'avgPct'       => $avgPct,
            'estHoursDay'  => $estHoursDay,
            'weekDays'     => $weekDays,
            'active_nav'   => 'queue',
        ]));
    }

    #[Route('/profile', name: 'profile')]
    public function profile(Request $request): Response
    {
        $accountant = $this->accountant();

        if ($request->isMethod('POST')) {
            $accountant->setFirm(trim($request->request->get('firm', '')) ?: null)
                       ->setRegistrationNumber(trim($request->request->get('registrationNumber', '')) ?: null)
                       ->setBio(trim($request->request->get('bio', '')) ?: null);
            $this->em->flush();
            $this->addFlash('success', 'Profil mis à jour.');
            return $this->redirectToRoute('expert_profile');
        }

        return $this->render('accountant/profile.html.twig', array_merge($this->topbarVars(), [
            'active_nav' => 'profile',
        ]));
    }

    #[Route('/invite', name: 'invite')]
    public function invite(Request $request): Response
    {
        $accountant = $this->accountant();
        $error = null;
        $link  = null;

        if ($request->isMethod('POST')) {
            $email = trim($request->request->get('email', ''));
            $user  = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$email) {
                $error = 'Veuillez saisir une adresse email.';
            } elseif (!$user) {
                $error = 'Aucun compte GestoPro trouvé pour cet email.';
            } elseif ($this->invRepo->existsPendingForEmail($accountant, $email)) {
                $error = 'Une invitation est déjà en attente pour cet utilisateur.';
            } else {
                $inv = new AccountantInvitation();
                $inv->setAccountant($accountant)->setUser($user);
                $this->em->persist($inv);
                $this->em->flush();

                $link = $this->generateUrl(
                    'app_invitation_respond',
                    ['token' => $inv->getToken()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
                $this->addFlash('success', 'Invitation créée pour ' . $email . '.');
            }
        }

        return $this->render('accountant/invite.html.twig', array_merge($this->topbarVars(), [
            'error'      => $error,
            'link'       => $link,
            'active_nav' => 'invite',
        ]));
    }

    #[Route('/clients/{id}', name: 'client_detail', requirements: ['id' => '\d+'])]
    public function clientDetail(AccountantInvitation $invitation): Response
    {
        $accountant = $this->accountant();
        if ($invitation->getAccountant()?->getId() !== $accountant->getId() || $invitation->getStatus() !== AccountantInvitation::STATUS_ACCEPTED) {
            throw $this->createAccessDeniedException();
        }

        $messages = $this->msgRepo->findByInvitation($invitation);
        $bilans   = $this->em->getRepository(BalanceSheet::class)
                             ->findBy(['user' => $invitation->getUser()], ['createdAt' => 'DESC']);

        return $this->render('accountant/client_detail.html.twig', array_merge($this->topbarVars(), [
            'invitation' => $invitation,
            'messages'   => $messages,
            'bilans'     => $bilans,
            'active_nav' => 'clients',
        ]));
    }

    #[Route('/clients/{id}/message', name: 'client_message', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function clientMessage(AccountantInvitation $invitation, Request $request): Response
    {
        $accountant = $this->accountant();
        if ($invitation->getAccountant()?->getId() !== $accountant->getId()) {
            throw $this->createAccessDeniedException();
        }

        $content = trim($request->request->get('content', ''));
        if ($content !== '') {
            $msg = (new ExpertMessage())
                ->setInvitation($invitation)
                ->setSenderType(ExpertMessage::SENDER_EXPERT)
                ->setContent($content);
            $this->em->persist($msg);
            $this->em->flush();
        }

        return $this->redirectToRoute('expert_client_detail', ['id' => $invitation->getId(), '#' => 'messages']);
    }

    // ── Facturation cabinet ──────────────────────────────────────────────
    #[Route('/billing', name: 'billing')]
    public function billing(): Response
    {
        $accountant = $this->accountant();
        $accepted   = $this->invRepo->findAcceptedByAccountant($accountant);
        $now        = new \DateTimeImmutable();
        $year       = (int) $now->format('Y');
        $month      = (int) $now->format('n');

        $invoiceRows  = [];
        $mrrExpert    = 0;
        $mrrPro       = 0;
        $encaisseMois = 0;
        $enAttente    = 0;
        $enRetard     = 0;
        $num          = 1;

        foreach ($accepted as $inv) {
            $user = $inv->getUser();
            $plan = $user->getPlan() ?? 'pro';
            $fee  = match($plan) { 'expert' => 49, 'pro' => 19, default => 0 };

            if ($plan === 'expert') $mrrExpert += 49;
            elseif ($plan === 'pro') $mrrPro   += 19;

            if ($fee > 0) {
                $d = new \DateTime('first day of this month');
                $invoiceRows[] = [
                    'num' => 'HON-'.$year.'-'.str_pad($num++, 3, '0', STR_PAD_LEFT),
                    'client' => $user->getFirstName().' '.$user->getLastName(),
                    'service' => 'Abonnement '.ucfirst($plan).' · '.$this->monthName($month),
                    'amount' => $fee, 'status' => 'paid',
                    'issuedAt' => $d, 'dueAt' => $d, 'recurring' => true,
                ];
                $encaisseMois += $fee;
            }

            $validatedBilans = $this->em->getRepository(BalanceSheet::class)->findBy([
                'user' => $user, 'accountant' => $accountant, 'status' => BalanceSheet::STATUS_VALIDATED,
            ]);
            foreach ($validatedBilans as $sheet) {
                if (!$sheet->getValidatedAt() || $sheet->getValidatedAt()->format('Y') !== (string) $year) continue;
                $issued = \DateTime::createFromImmutable($sheet->getValidatedAt());
                $due    = (clone $issued)->modify('+30 days');
                $isPast = $due < new \DateTime();
                $status = $isPast ? 'paid' : 'sent';
                $isPast ? ($encaisseMois += 390) : ($enAttente += 390);
                $invoiceRows[] = [
                    'num' => 'HON-'.$year.'-'.str_pad($num++, 3, '0', STR_PAD_LEFT),
                    'client' => $user->getFirstName().' '.$user->getLastName(),
                    'service' => 'Validation bilan '.$sheet->getPeriod(),
                    'amount' => 390, 'status' => $status,
                    'issuedAt' => $issued, 'dueAt' => $due, 'recurring' => false,
                ];
            }
        }

        usort($invoiceRows, fn($a, $b) => $b['issuedAt'] <=> $a['issuedAt']);
        $mrr    = $mrrExpert + $mrrPro;
        $caYear = $mrr * $month + array_sum(array_column($invoiceRows, 'amount'));

        return $this->render('accountant/billing.html.twig', array_merge($this->topbarVars(), [
            'invoiceRows'  => $invoiceRows,
            'caYear'       => $caYear,
            'mrr'          => $mrr,
            'mrrExpert'    => $mrrExpert,
            'mrrPro'       => $mrrPro,
            'encaisseMois' => $encaisseMois,
            'enAttente'    => $enAttente,
            'enRetard'     => $enRetard,
            'year'         => $year,
            'active_nav'   => 'bill',
        ]));
    }

    // ── Statistiques ─────────────────────────────────────────────────────
    #[Route('/stats', name: 'stats')]
    public function stats(): Response
    {
        $accountant = $this->accountant();
        $accepted   = $this->invRepo->findAcceptedByAccountant($accountant);
        $now        = new \DateTimeImmutable();
        $year       = (int) $now->format('Y');

        $bilansValidated = 0;
        $caYear          = 0;
        $monthlyData     = array_fill(1, 12, ['validated' => 0, 'returned' => 0]);
        $topClients      = [];

        foreach ($accepted as $inv) {
            $user = $inv->getUser();
            $ca   = $this->invoiceRepo->getYearRevenue($user, $year);
            $caYear += $ca;
            $topClients[] = ['user' => $user, 'ca' => $ca];

            foreach ($this->em->getRepository(BalanceSheet::class)->findBy(['user' => $user]) as $sheet) {
                if ($sheet->getStatus() === BalanceSheet::STATUS_VALIDATED) {
                    $bilansValidated++;
                    if ($sheet->getValidatedAt() && $sheet->getValidatedAt()->format('Y') === (string) $year) {
                        $m = (int) $sheet->getValidatedAt()->format('n');
                        $monthlyData[$m]['validated']++;
                    }
                }
                if ($sheet->getStatus() === BalanceSheet::STATUS_ANNOTATED
                    && $sheet->getCreatedAt()->format('Y') === (string) $year) {
                    $m = (int) $sheet->getCreatedAt()->format('n');
                    $monthlyData[$m]['returned']++;
                }
            }
        }

        usort($topClients, fn($a, $b) => $b['ca'] - $a['ca']);

        return $this->render('accountant/stats.html.twig', array_merge($this->topbarVars(), [
            'bilansValidated' => $bilansValidated,
            'caYear'          => $caYear,
            'clientCount'     => count($accepted),
            'monthlyData'     => $monthlyData,
            'topClients'      => array_slice($topClients, 0, 5),
            'year'            => $year,
            'active_nav'      => 'stats',
        ]));
    }

    // ── Messages ─────────────────────────────────────────────────────────
    #[Route('/messages', name: 'messages')]
    public function messages(Request $request): Response
    {
        $accountant = $this->accountant();
        $accepted   = $this->invRepo->findAcceptedByAccountant($accountant);

        $conversations = [];
        $totalUnread   = 0;

        foreach ($accepted as $inv) {
            $msgs = $this->msgRepo->findByInvitation($inv);
            usort($msgs, fn($a, $b) => $a->getCreatedAt() <=> $b->getCreatedAt());
            $lastMsg = empty($msgs) ? null : end($msgs);
            $unread  = count(array_filter($msgs, fn($m) => $m->isFromClient()));
            $totalUnread += $unread;
            $conversations[] = [
                'invitation' => $inv,
                'user'       => $inv->getUser(),
                'messages'   => $msgs,
                'lastMsg'    => $lastMsg,
                'unread'     => $unread,
            ];
        }

        usort($conversations, function ($a, $b) {
            if (!$a['lastMsg'] && !$b['lastMsg']) return 0;
            if (!$a['lastMsg']) return 1;
            if (!$b['lastMsg']) return -1;
            return $b['lastMsg']->getCreatedAt() <=> $a['lastMsg']->getCreatedAt();
        });

        $convId     = (int) $request->query->get('conv', 0);
        $activeConv = null;
        foreach ($conversations as $conv) {
            if ($convId && $conv['invitation']->getId() === $convId) {
                $activeConv = $conv;
                break;
            }
        }
        if (!$activeConv) {
            foreach ($conversations as $conv) {
                if ($conv['lastMsg']) { $activeConv = $conv; break; }
            }
        }
        if (!$activeConv && !empty($conversations)) {
            $activeConv = $conversations[0];
        }

        return $this->render('accountant/messages.html.twig', array_merge($this->topbarVars(), [
            'conversations' => $conversations,
            'activeConv'    => $activeConv,
            'totalUnread'   => $totalUnread,
            'active_nav'    => 'messages',
        ]));
    }

    // ── Messages · send from messages page ───────────────────────────────
    #[Route('/messages/{id}/send', name: 'messages_send', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function messagesSend(AccountantInvitation $invitation, Request $request): Response
    {
        $accountant = $this->accountant();
        if ($invitation->getAccountant()?->getId() !== $accountant->getId()) {
            throw $this->createAccessDeniedException();
        }
        $content = trim($request->request->get('content', ''));
        if ($content !== '') {
            $msg = (new ExpertMessage())
                ->setInvitation($invitation)
                ->setSenderType(ExpertMessage::SENDER_EXPERT)
                ->setContent($content);
            $this->em->persist($msg);
            $this->em->flush();
        }
        return $this->redirectToRoute('expert_messages');
    }

    // ── Échéances clients ────────────────────────────────────────────────
    #[Route('/deadlines', name: 'deadlines')]
    public function deadlines(): Response
    {
        $accountant = $this->accountant();
        $accepted   = $this->invRepo->findAcceptedByAccountant($accountant);
        $now        = new \DateTimeImmutable();
        $year       = (int) $now->format('Y');

        $deadlines    = [];
        $lateCount    = 0;
        $weekCount    = 0;
        $totalAmount  = 0;
        $declaredMois = 0;
        $nextBig      = null;

        foreach ($accepted as $inv) {
            $user = $inv->getUser();

            $declarations = $this->em->getRepository(\App\Entity\UrssafDeclaration::class)
                ->findBy(['user' => $user], ['periodEnd' => 'ASC']);

            foreach ($declarations as $decl) {
                if (!$decl->getPeriodEnd()) continue;
                $end      = $decl->getPeriodEnd();
                $daysLeft = (int) ceil(($end->getTimestamp() - $now->getTimestamp()) / 86400);
                $amount   = (int) round((float) $decl->getCotisationAmount());

                if ($decl->isDeclared()) {
                    $status = 'done';
                    $declaredMois++;
                } elseif ($daysLeft < 0) {
                    $status = 'late';
                    $lateCount++;
                    $totalAmount += $amount;
                } elseif ($daysLeft <= 7) {
                    $status = 'todo';
                    $weekCount++;
                    $totalAmount += $amount;
                } else {
                    $status = 'todo';
                    $totalAmount += $amount;
                }

                if ($status !== 'done' && ($nextBig === null || $end < $nextBig['date'])) {
                    $nextBig = ['date' => $end, 'client' => $user->getFirstName(), 'amount' => $amount];
                }

                $deadlines[] = [
                    'client'   => $user->getFirstName().' '.$user->getLastName(),
                    'user'     => $user,
                    'type'     => 'URSSAF',
                    'label'    => 'Cotisations '.($decl->getPeriodLabel() ?? 'URSSAF'),
                    'date'     => \DateTime::createFromImmutable($end),
                    'daysLeft' => $daysLeft,
                    'amount'   => $amount,
                    'status'   => $status,
                    'kind'     => 'social',
                    'plan'     => $user->getPlan() ?? 'pro',
                ];
            }

            $bilans = $this->em->getRepository(BalanceSheet::class)->findBy([
                'user' => $user, 'status' => BalanceSheet::STATUS_PENDING_REVIEW,
            ]);
            foreach ($bilans as $sheet) {
                $deadline = \DateTime::createFromImmutable($sheet->getCreatedAt())->modify('+7 days');
                $daysLeft = (int) ceil(($deadline->getTimestamp() - (new \DateTime())->getTimestamp()) / 86400);
                $status   = $daysLeft < 0 ? 'late' : 'todo';
                if ($status === 'late') $lateCount++;
                $deadlines[] = [
                    'client'   => $user->getFirstName().' '.$user->getLastName(),
                    'user'     => $user,
                    'type'     => 'Bilan',
                    'label'    => 'Validation bilan '.$sheet->getPeriod(),
                    'date'     => $deadline,
                    'daysLeft' => $daysLeft,
                    'amount'   => null,
                    'status'   => $status,
                    'kind'     => 'compta',
                    'plan'     => $user->getPlan() ?? 'pro',
                ];
            }
        }

        usort($deadlines, function ($a, $b) {
            $p = ['late' => 0, 'todo' => 1, 'progress' => 2, 'done' => 3];
            $ap = $p[$a['status']] ?? 1;
            $bp = $p[$b['status']] ?? 1;
            if ($ap !== $bp) return $ap - $bp;
            return $a['date'] <=> $b['date'];
        });

        return $this->render('accountant/deadlines.html.twig', array_merge($this->topbarVars(), [
            'deadlines'    => $deadlines,
            'lateCount'    => $lateCount,
            'weekCount'    => $weekCount,
            'totalAmount'  => $totalAmount,
            'declaredMois' => $declaredMois,
            'nextBig'      => $nextBig,
            'year'         => $year,
            'active_nav'   => 'deadlines',
        ]));
    }

    private function monthName(int $m): string
    {
        return ['','janvier','février','mars','avril','mai','juin',
                'juillet','août','septembre','octobre','novembre','décembre'][$m];
    }
}
