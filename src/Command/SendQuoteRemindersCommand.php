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
    name: 'app:send-quote-reminders',
    description: 'Envoie les relances pour les devis envoyés sans réponse depuis X jours.',
)]
class SendQuoteRemindersCommand extends Command
{
    public function __construct(
        private readonly InvoiceRepository      $invoiceRepo,
        private readonly InvoiceMailer          $mailer,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('interval', null, InputOption::VALUE_REQUIRED, 'Intervalle en jours sans réponse', 7)
             ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulation sans envoi d\'email');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io       = new SymfonyStyle($input, $output);
        $interval = (int) $input->getOption('interval');
        $dryRun   = (bool) $input->getOption('dry-run');

        $quotes = $this->invoiceRepo->findQuotesNeedingReminder($interval);

        if (empty($quotes)) {
            $io->success('Aucun devis à relancer.');
            return Command::SUCCESS;
        }

        $sent   = 0;
        $errors = 0;

        foreach ($quotes as $quote) {
            if (!$quote->getClient()->getEmail()) {
                $io->warning(sprintf('Devis %s — client sans email, ignoré.', $quote->getNumber()));
                continue;
            }

            $io->text(sprintf(
                '[%s] Relance → %s (%s)',
                $quote->getNumber(),
                $quote->getClient()->getName(),
                $quote->getClient()->getEmail()
            ));

            if (!$dryRun) {
                try {
                    $this->mailer->sendQuoteReminder($quote);
                    $quote->setLastReminderAt(new \DateTimeImmutable())
                          ->setReminderCount($quote->getReminderCount() + 1);
                    $this->em->flush();
                    $sent++;
                } catch (\Throwable $e) {
                    $io->error(sprintf('Erreur sur %s : %s', $quote->getNumber(), $e->getMessage()));
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
