<?php

namespace App\Command;

use App\Repository\UrssafDeclarationRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

#[AsCommand(
    name: 'app:send-urssaf-reminders',
    description: 'Envoie des rappels email avant les échéances URSSAF (J-7 et J-3).',
)]
class SendUrssafRemindersCommand extends Command
{
    public function __construct(
        private readonly UrssafDeclarationRepository $repo,
        private readonly MailerInterface             $mailer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulation sans envoi d\'email');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io     = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $today  = new \DateTimeImmutable('today');
        $sent   = 0;

        // Récupère toutes les déclarations non encore déclarées
        $pending = $this->repo->findBy(['declared' => false]);

        foreach ($pending as $declaration) {
            $dueDate = $this->computeDueDate($declaration->getPeriodEnd(), $declaration->getPeriodicity());

            if (!$dueDate) {
                continue;
            }

            $daysLeft = (int) $today->diff($dueDate)->days;
            $isFuture = $dueDate >= $today;

            if (!$isFuture || !in_array($daysLeft, [7, 3])) {
                continue;
            }

            $user = $declaration->getUser();
            if (!$user->getEmail()) {
                continue;
            }

            $io->text(sprintf(
                '[%s] Rappel J-%d → %s (%s)',
                $declaration->getPeriodLabel(),
                $daysLeft,
                $user->getEmail(),
                $dueDate->format('d/m/Y')
            ));

            if (!$dryRun) {
                try {
                    $email = (new TemplatedEmail())
                        ->from(new Address('noreply@gestopro.fr', 'GestoPro'))
                        ->to(new Address($user->getEmail(), $user->getFullName()))
                        ->subject(sprintf('Rappel URSSAF — Déclaration %s à saisir dans %d jours', $declaration->getPeriodLabel(), $daysLeft))
                        ->htmlTemplate('email/urssaf_reminder.html.twig')
                        ->context([
                            'declaration' => $declaration,
                            'user'        => $user,
                            'daysLeft'    => $daysLeft,
                            'dueDate'     => $dueDate,
                        ]);

                    $this->mailer->send($email);
                    $sent++;
                } catch (\Throwable $e) {
                    $io->error('Erreur : ' . $e->getMessage());
                }
            } else {
                $sent++;
            }
        }

        $prefix = $dryRun ? '[DRY-RUN] ' : '';
        $io->success(sprintf('%s%d rappel(s) envoyé(s).', $prefix, $sent));

        return Command::SUCCESS;
    }

    private function computeDueDate(\DateTimeImmutable $periodEnd, string $periodicity): ?\DateTimeImmutable
    {
        // Échéance URSSAF auto-entrepreneur :
        // Mensuelle → dernier jour du mois suivant
        // Trimestrielle → dernier jour du mois suivant la fin du trimestre
        $nextMonth = $periodEnd->modify('first day of next month');
        $lastDay   = $nextMonth->modify('last day of this month');
        return $lastDay;
    }
}
