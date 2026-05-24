<?php

namespace App\DataFixtures;

use App\Entity\Accountant;
use App\Entity\AiConversation;
use App\Entity\AiMessage;
use App\Entity\Client;
use App\Entity\Expense;
use App\Entity\ExpenseCategory;
use App\Entity\Invoice;
use App\Entity\InvoiceLine;
use App\Entity\UrssafDeclaration;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        // Catégories de dépenses
        $categories = $this->loadCategories($manager);

        // Utilisateur de test principal
        $user = $this->createUser($manager, 'demo@gestopro.fr', 'password', 'Marie', 'Dupont', User::PLAN_PRO);

        // Utilisateur secondaire
        $user2 = $this->createUser($manager, 'test@gestopro.fr', 'password', 'Jean', 'Martin', User::PLAN_FREE);

        // Clients pour Marie
        $clients = $this->loadClients($manager, $user);

        // Factures
        $this->loadInvoices($manager, $user, $clients);

        // Dépenses
        $this->loadExpenses($manager, $user, $categories);

        // Déclarations URSSAF
        $this->loadUrssafDeclarations($manager, $user);

        // Conversation IA
        $this->loadAiConversation($manager, $user);

        // Experts-comptables
        $this->loadAccountants($manager);

        $manager->flush();
    }

    private function createUser(ObjectManager $manager, string $email, string $password, string $firstName, string $lastName, string $plan): User
    {
        $user = new User();
        $user->setEmail($email)
             ->setFirstName($firstName)
             ->setLastName($lastName)
             ->setPlan($plan)
             ->setPassword($this->hasher->hashPassword($user, $password));

        $manager->persist($user);
        return $user;
    }

    private function loadCategories(ObjectManager $manager): array
    {
        $categories = [];
        foreach (ExpenseCategory::DEFAULTS as $slug => $label) {
            $cat = new ExpenseCategory();
            $cat->setSlug($slug)->setLabel($label);
            $manager->persist($cat);
            $categories[$slug] = $cat;
        }
        return $categories;
    }

    private function loadClients(ObjectManager $manager, User $user): array
    {
        $clientsData = [
            ['Agence Pixel Studio', 'pixel@studio.fr', '75000', 'Paris', '55 Rue de Rivoli'],
            ['Startup TechFlow SAS', 'contact@techflow.io', '69001', 'Lyon', '12 Quai de Saône'],
            ['Boutique Artisanale Leblanc', 'leblanc@artisan.fr', '13001', 'Marseille', '8 Rue Paradis'],
            ['Cabinet RH Conseil', 'rh@conseil.biz', '31000', 'Toulouse', '3 Allée Jean Jaurès'],
            ['Espace Co-working Libres', 'hello@coworking.net', '33000', 'Bordeaux', '22 Quai des Chartrons'],
        ];

        $clients = [];
        foreach ($clientsData as [$name, $email, $postal, $city, $address]) {
            $client = new Client();
            $client->setName($name)
                   ->setEmail($email)
                   ->setPostalCode($postal)
                   ->setCity($city)
                   ->setAddress($address)
                   ->setUser($user);
            $manager->persist($client);
            $clients[] = $client;
        }
        return $clients;
    }

    private function loadInvoices(ObjectManager $manager, User $user, array $clients): void
    {
        $invoicesData = [
            ['FAC-2026-0001', $clients[0], 'paid', '2026-01-15', '2026-02-15', [
                ['Développement site web', '1', '2500.00', '20'],
                ['Maintenance mensuelle', '3', '150.00', '20'],
            ]],
            ['FAC-2026-0002', $clients[1], 'sent', '2026-02-01', '2026-03-01', [
                ['Conseil stratégie digitale', '2', '800.00', '20'],
            ]],
            ['FAC-2026-0003', $clients[2], 'paid', '2026-02-10', '2026-03-10', [
                ['Création logo & charte graphique', '1', '1200.00', '20'],
            ]],
            ['FAC-2026-0004', $clients[3], 'draft', '2026-03-01', '2026-04-01', [
                ['Formation Excel avancé', '1', '600.00', '20'],
                ['Support post-formation', '5', '80.00', '20'],
            ]],
            ['FAC-2026-0005', $clients[4], 'overdue', '2026-01-20', '2026-02-20', [
                ['Location bureau (janvier)', '1', '450.00', '20'],
            ]],
        ];

        foreach ($invoicesData as [$number, $client, $status, $issuedAt, $dueAt, $lines]) {
            $invoice = new Invoice();
            $invoice->setNumber($number)
                    ->setClient($client)
                    ->setStatus($status)
                    ->setIssuedAt(new \DateTimeImmutable($issuedAt))
                    ->setDueAt(new \DateTimeImmutable($dueAt))
                    ->setUser($user);

            if ($status === 'paid') {
                $invoice->setPaidAt(new \DateTimeImmutable($dueAt));
            }

            $position = 1;
            foreach ($lines as [$desc, $qty, $price, $tva]) {
                $line = new InvoiceLine();
                $line->setDescription($desc)
                     ->setQuantity($qty)
                     ->setUnitPrice($price)
                     ->setTvaRate($tva)
                     ->setPosition($position++);
                $invoice->addLine($line);
                $manager->persist($line);
            }

            $invoice->recalculateTotals();
            $manager->persist($invoice);
        }
    }

    private function loadExpenses(ObjectManager $manager, User $user, array $categories): void
    {
        $expensesData = [
            ['Adobe Creative Cloud', '2026-01-01', '59.99', '49.99', '10.00', '20', 'logiciel'],
            ['Déjeuner client Pixel Studio', '2026-01-15', '68.40', '57.00', '11.40', '20', 'repas'],
            ['Train Paris-Lyon', '2026-02-03', '89.00', '74.17', '14.83', '20', 'transport'],
            ['Hébergement web OVH', '2026-02-01', '12.00', '10.00', '2.00', '20', 'logiciel'],
            ['Smartphone professionnel', '2026-02-20', '720.00', '600.00', '120.00', '20', 'materiel'],
            ['Frais bancaires', '2026-03-01', '4.50', '4.50', '0.00', '0', 'bancaire'],
        ];

        foreach ($expensesData as [$vendor, $date, $ttc, $ht, $tva, $tvaRate, $catSlug]) {
            $expense = new Expense();
            $expense->setVendor($vendor)
                    ->setDate(new \DateTimeImmutable($date))
                    ->setAmountTtc($ttc)
                    ->setAmountHt($ht)
                    ->setTva($tva)
                    ->setTvaRate($tvaRate)
                    ->setCategory($categories[$catSlug] ?? null)
                    ->setUser($user);
            $manager->persist($expense);
        }
    }

    private function loadUrssafDeclarations(ObjectManager $manager, User $user): void
    {
        $declarations = [
            ['2026-T1', '2026-01-01', '2026-03-31', 'quarterly', '8500.00', false],
            ['2025-T4', '2025-10-01', '2025-12-31', 'quarterly', '7200.00', true],
            ['2025-T3', '2025-07-01', '2025-09-30', 'quarterly', '6800.00', true],
        ];

        foreach ($declarations as [$label, $start, $end, $periodicity, $revenue, $declared]) {
            $decl = new UrssafDeclaration();
            $decl->setPeriodLabel($label)
                 ->setPeriodStart(new \DateTimeImmutable($start))
                 ->setPeriodEnd(new \DateTimeImmutable($end))
                 ->setPeriodicity($periodicity)
                 ->setRevenue($revenue)
                 ->setDeclared($declared)
                 ->setUser($user);

            if ($declared) {
                $decl->setDeclaredAt(new \DateTimeImmutable($end));
            }

            $manager->persist($decl);
        }
    }

    private function loadAccountants(ObjectManager $manager): void
    {
        $accountantsData = [
            ['sophie.lambert@cabinet-lambert.fr', 'Sophie',  'Lambert', 'Cabinet Lambert & Associés', 'OEC-75-12345'],
            ['pierre.nguyen@fiduciaire-nova.fr',  'Pierre',  'Nguyen',  'Fiduciaire Nova',            'OEC-69-67890'],
        ];

        foreach ($accountantsData as [$email, $first, $last, $firm, $regNumber]) {
            $accountant = new Accountant();
            $accountant->setEmail($email)
                       ->setFirstName($first)
                       ->setLastName($last)
                       ->setFirm($firm)
                       ->setRegistrationNumber($regNumber)
                       ->setPassword($this->hasher->hashPassword($accountant, 'password'));
            $manager->persist($accountant);
        }
    }

    private function loadAiConversation(ObjectManager $manager, User $user): void
    {
        $conv = new AiConversation();
        $conv->setTitle('Optimisation fiscale 2026')
             ->setUser($user);

        $messages = [
            [AiMessage::ROLE_USER, 'Bonjour ! Quel est mon chiffre d\'affaires estimé pour le T1 2026 ?'],
            [AiMessage::ROLE_ASSISTANT, 'Bonjour Marie ! D\'après vos données, votre CA pour le T1 2026 s\'élève à **8 500 €** (3 factures payées). Votre cotisation URSSAF estimée est de **1 802 €** (taux BNC 21,2%). Souhaitez-vous un détail par client ?', 120, 95],
            [AiMessage::ROLE_USER, 'Oui, et quelles dépenses puis-je déduire ?'],
            [AiMessage::ROLE_ASSISTANT, 'Vos dépenses déductibles sur la période : Adobe Creative Cloud (49,99 €), hébergement OVH (10 €), smartphone pro (600 €), frais bancaires (4,50 €) — soit **664,49 € HT** au total. Le smartphone est déductible à 100% s\'il est exclusivement professionnel.', 85, 112],
        ];

        foreach ($messages as $msgData) {
            $msg = new AiMessage();
            $msg->setRole($msgData[0])
                ->setContent($msgData[1]);

            if (isset($msgData[2])) {
                $msg->setInputTokens($msgData[2])
                    ->setOutputTokens($msgData[3]);
            }

            $conv->addMessage($msg);
            $manager->persist($msg);
        }

        $manager->persist($conv);
    }
}
