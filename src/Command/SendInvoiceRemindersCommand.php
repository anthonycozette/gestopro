<?php

namespace App\Command;

use App\Repository\InvoiceRepository;
use App\Service\InvoiceMailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-invoice-reminders',
    description: 'Envoie les relances automatiques pour les factures impayées (tous les 7 jours).',
)]
class SendInvoiceRemindersCommand extends Command
{
    public function __construct(
        private readonly InvoiceRepository    $invoiceRepo,
        private readonly InvoiceMailer        $mailer,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('interval', null, InputOption::VALUE_REQUIRED, 'Intervalle en jours entre deux relances', 7)
             ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulation sans envoi d\'email');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io       = new SymfonyStyle($input, $output);
        $interval = (int) $input->getOption('interval');
        $dryRun   = (bool) $input->getOption('dry-run');

        $invoices = $this->invoiceRepo->findNeedingReminder($interval);

        if (empty($invoices)) {
            $io->success('Aucune facture à relancer.');
            return Command::SUCCESS;
        }

        $sent   = 0;
        $errors = 0;

        foreach ($invoices as $invoice) {
            $clientEmail = $invoice->getClient()->getEmail();

            if (!$clientEmail) {
                $io->warning(sprintf('Facture %s — client sans email, ignorée.', $invoice->getNumber()));
                continue;
            }

            $io->text(sprintf(
                '[%s] Relance n°%d → %s (%s)',
                $invoice->getNumber(),
                $invoice->getReminderCount() + 1,
                $invoice->getClient()->getName(),
                $clientEmail
            ));

            if (!$dryRun) {
                try {
                    $this->mailer->sendReminder($invoice);
                    $invoice->setLastReminderAt(new \DateTimeImmutable())
                            ->setReminderCount($invoice->getReminderCount() + 1);
                    $this->em->flush();
                    $sent++;
                } catch (\Throwable $e) {
                    $io->error(sprintf('Erreur sur %s : %s', $invoice->getNumber(), $e->getMessage()));
                    $errors++;
                }
            } else {
                $sent++;
            }
        }

        $prefix = $dryRun ? '[DRY-RUN] ' : '';
        $io->success(sprintf('%s%d relance(s) envoyée(s), %d erreur(s).', $prefix, $sent, $errors));

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
