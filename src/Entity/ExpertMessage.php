<?php

namespace App\Entity;

use App\Repository\ExpertMessageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExpertMessageRepository::class)]
#[ORM\Table(name: 'expert_messages')]
class ExpertMessage
{
    public const SENDER_CLIENT = 'client';
    public const SENDER_EXPERT = 'expert';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: AccountantInvitation::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?AccountantInvitation $invitation = null;

    #[ORM\Column(length: 10)]
    private string $senderType = self::SENDER_CLIENT;

    #[ORM\Column(type: 'text')]
    private string $content = '';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getInvitation(): ?AccountantInvitation { return $this->invitation; }
    public function setInvitation(AccountantInvitation $inv): static { $this->invitation = $inv; return $this; }

    public function getSenderType(): string { return $this->senderType; }
    public function setSenderType(string $t): static { $this->senderType = $t; return $this; }
    public function isFromClient(): bool { return $this->senderType === self::SENDER_CLIENT; }
    public function isFromExpert(): bool { return $this->senderType === self::SENDER_EXPERT; }

    public function getContent(): string { return $this->content; }
    public function setContent(string $c): static { $this->content = $c; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
