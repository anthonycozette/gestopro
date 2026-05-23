<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const PLAN_FREE    = 'free';
    public const PLAN_PRO     = 'pro';
    public const PLAN_EXPERT  = 'expert';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private ?string $lastName = null;

    #[ORM\Column(length: 14, nullable: true)]
    private ?string $siret = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 20, options: ['default' => self::PLAN_FREE])]
    private string $plan = self::PLAN_FREE;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeCustomerId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeSubscriptionId = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $subscriptionEndsAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(targetEntity: Client::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $clients;

    #[ORM\OneToMany(targetEntity: Expense::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $expenses;

    #[ORM\OneToMany(targetEntity: Invoice::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $invoices;

    #[ORM\OneToMany(targetEntity: UrssafDeclaration::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $urssafDeclarations;

    #[ORM\OneToMany(targetEntity: BalanceSheet::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $balanceSheets;

    #[ORM\OneToMany(targetEntity: AiConversation::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $aiConversations;

    public function __construct()
    {
        $this->createdAt         = new \DateTimeImmutable();
        $this->clients           = new ArrayCollection();
        $this->expenses          = new ArrayCollection();
        $this->invoices          = new ArrayCollection();
        $this->urssafDeclarations = new ArrayCollection();
        $this->balanceSheets     = new ArrayCollection();
        $this->aiConversations   = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getUserIdentifier(): string { return (string) $this->email; }

    public function getRoles(): array
    {
        $roles   = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }
    public function setRoles(array $roles): static { $this->roles = $roles; return $this; }

    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }

    public function eraseCredentials(): void {}

    public function getFirstName(): ?string { return $this->firstName; }
    public function setFirstName(string $firstName): static { $this->firstName = $firstName; return $this; }

    public function getLastName(): ?string { return $this->lastName; }
    public function setLastName(string $lastName): static { $this->lastName = $lastName; return $this; }

    public function getFullName(): string { return $this->firstName . ' ' . $this->lastName; }

    public function getSiret(): ?string { return $this->siret; }
    public function setSiret(?string $siret): static { $this->siret = $siret; return $this; }

    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $phone): static { $this->phone = $phone; return $this; }

    public function getPlan(): string { return $this->plan; }
    public function setPlan(string $plan): static { $this->plan = $plan; return $this; }

    public function isPro(): bool { return in_array($this->plan, [self::PLAN_PRO, self::PLAN_EXPERT]); }
    public function isExpert(): bool { return $this->plan === self::PLAN_EXPERT; }

    public function getStripeCustomerId(): ?string { return $this->stripeCustomerId; }
    public function setStripeCustomerId(?string $id): static { $this->stripeCustomerId = $id; return $this; }

    public function getStripeSubscriptionId(): ?string { return $this->stripeSubscriptionId; }
    public function setStripeSubscriptionId(?string $id): static { $this->stripeSubscriptionId = $id; return $this; }

    public function getSubscriptionEndsAt(): ?\DateTimeImmutable { return $this->subscriptionEndsAt; }
    public function setSubscriptionEndsAt(?\DateTimeImmutable $date): static { $this->subscriptionEndsAt = $date; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getClients(): Collection { return $this->clients; }
    public function getExpenses(): Collection { return $this->expenses; }
    public function getInvoices(): Collection { return $this->invoices; }
    public function getUrssafDeclarations(): Collection { return $this->urssafDeclarations; }
    public function getBalanceSheets(): Collection { return $this->balanceSheets; }
    public function getAiConversations(): Collection { return $this->aiConversations; }
}
