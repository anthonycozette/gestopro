<?php

namespace App\Entity;

use App\Repository\AccountantInvitationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AccountantInvitationRepository::class)]
#[ORM\Table(name: 'accountant_invitations')]
class AccountantInvitation
{
    public const STATUS_PENDING  = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DECLINED = 'declined';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64, unique: true)]
    private ?string $token = null;

    #[ORM\Column(length: 20, options: ['default' => self::STATUS_PENDING])]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $respondedAt = null;

    #[ORM\ManyToOne(targetEntity: Accountant::class, inversedBy: 'invitations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Accountant $accountant = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->expiresAt = new \DateTimeImmutable('+7 days');
        $this->token     = bin2hex(random_bytes(32));
    }

    public function getId(): ?int { return $this->id; }

    public function getToken(): ?string { return $this->token; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function isPending(): bool { return $this->status === self::STATUS_PENDING; }
    public function isExpired(): bool { return $this->expiresAt && $this->expiresAt < new \DateTimeImmutable(); }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getExpiresAt(): ?\DateTimeImmutable { return $this->expiresAt; }

    public function getRespondedAt(): ?\DateTimeImmutable { return $this->respondedAt; }
    public function setRespondedAt(?\DateTimeImmutable $d): static { $this->respondedAt = $d; return $this; }

    public function getAccountant(): ?Accountant { return $this->accountant; }
    public function setAccountant(?Accountant $a): static { $this->accountant = $a; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $u): static { $this->user = $u; return $this; }
}
