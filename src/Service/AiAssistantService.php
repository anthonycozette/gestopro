<?php

namespace App\Service;

use Anthropic\Client;
use App\Entity\AiConversation;
use App\Entity\AiMessage;
use App\Entity\User;
use App\Repository\ExpenseRepository;
use App\Repository\InvoiceRepository;
use App\Repository\UrssafDeclarationRepository;
use Doctrine\ORM\EntityManagerInterface;

class AiAssistantService
{
    private Client $client;

    private const MONTH_NAMES = [
        1 => 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
        'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre',
    ];

    public function __construct(
        string $apiKey,
        private readonly InvoiceRepository $invoiceRepo,
        private readonly ExpenseRepository $expenseRepo,
        private readonly UrssafDeclarationRepository $urssafRepo,
        private readonly EntityManagerInterface $em,
    ) {
        $this->client = new Client(apiKey: $apiKey);
    }

    public function chat(AiConversation $conv, string $userContent): AiMessage
    {
        $userMsg = new AiMessage();
        $userMsg->setRole(AiMessage::ROLE_USER)->setContent($userContent);
        $conv->addMessage($userMsg);
        $this->em->persist($userMsg);

        $apiMessages = [];
        foreach ($conv->getMessages() as $msg) {
            $apiMessages[] = ['role' => $msg->getRole(), 'content' => $msg->getContent()];
        }

        $user  = $conv->getUser();
        $year  = (int) date('Y');
        $month = (int) date('n');

        $response = $this->client->messages->create(
            maxTokens: 1024,
            system: $this->buildSystemPrompt($user, $year, $month),
            messages: $apiMessages,
            model: 'claude-haiku-4-5-20251001',
        );

        $text         = $response->content[0]->text ?? '';
        $inputTokens  = $response->usage->inputTokens  ?? null;
        $outputTokens = $response->usage->outputTokens ?? null;

        $assistantMsg = new AiMessage();
        $assistantMsg->setRole(AiMessage::ROLE_ASSISTANT)
                     ->setContent($text)
                     ->setInputTokens($inputTokens)
                     ->setOutputTokens($outputTokens);
        $conv->addMessage($assistantMsg);
        $this->em->persist($assistantMsg);

        if ($conv->getMessages()->count() <= 2) {
            $title = mb_substr($userContent, 0, 60) . (mb_strlen($userContent) > 60 ? '…' : '');
            $conv->setTitle($title);
        }

        return $assistantMsg;
    }

    private function buildSystemPrompt(User $user, int $year, int $month): string
    {
        $caYear      = $this->invoiceRepo->getYearRevenue($user, $year);
        $caMonth     = $this->invoiceRepo->getMonthRevenue($user, $year, $month);
        $expYear     = $this->expenseRepo->getYearTotal($user, $year);
        $urssafYear  = $this->urssafRepo->getYearCotisation($user, $year);
        $pending     = $this->invoiceRepo->getPendingStats($user);
        $nextUrssaf  = $this->urssafRepo->findNextUndeclared($user);
        $net         = round($caYear - $expYear - $urssafYear, 2);

        $monthName   = self::MONTH_NAMES[$month];
        $fmt = fn(float $v) => number_format($v, 2, ',', ' ');

        $pendingText = $pending['count'] > 0
            ? "{$pending['count']} facture(s) non payée(s) — {$fmt($pending['amount'])} € TTC"
            : 'Aucune facture en attente';

        $urssafText = $nextUrssaf
            ? "Période {$nextUrssaf->getPeriodLabel()} (jusqu'au {$nextUrssaf->getPeriodEnd()->format('d/m/Y')}) — cotisation estimée {$fmt($nextUrssaf->getCotisationAmount())} €"
            : 'Aucune déclaration en attente';

        return <<<SYSTEM
        Tu es GestoPro Assistant, le comptable virtuel de {$user->getFirstName()} {$user->getLastName()}.
        Tu analyses les données financières de cet auto-entrepreneur et réponds à ses questions de gestion.

        DONNÉES FINANCIÈRES — exercice {$year} :
        • CA encaissé (factures payées) : {$fmt($caYear)} € HT
        • CA ce mois ({$monthName} {$year}) : {$fmt($caMonth)} € HT
        • Dépenses TTC (année) : {$fmt($expYear)} €
        • Cotisations URSSAF (année) : {$fmt($urssafYear)} €
        • Résultat net estimé : {$fmt($net)} €

        FACTURES EN COURS : {$pendingText}
        URSSAF — prochaine déclaration : {$urssafText}

        RÈGLES :
        - Réponds toujours en français, de façon concise et professionnelle.
        - Utilise le format "1 234,00 € HT" ou "1 234,00 € TTC" pour les montants.
        - Structure les réponses longues avec du markdown (gras, listes à puces).
        - Pour les sujets fiscaux complexes, invite à consulter un expert-comptable.
        - Ne fais jamais d'hypothèses sur des données que tu n'as pas.
        SYSTEM;
    }
}
