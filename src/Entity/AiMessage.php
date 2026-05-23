<?php

namespace App\Entity;

use App\Repository\AiMessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AiMessageRepository::class)]
#[ORM\Table(name: 'ai_messages')]
class AiMessage
{
    public const ROLE_USER      = 'user';
    public const ROLE_ASSISTANT = 'assistant';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private string $role = self::ROLE_USER;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    // Tokens utilisés (pour monitoring coût API)
    #[ORM\Column(nullable: true)]
    private ?int $inputTokens = null;

    #[ORM\Column(nullable: true)]
    private ?int $outputTokens = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: AiConversation::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?AiConversation $conversation = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getRole(): string { return $this->role; }
    public function setRole(string $role): static { $this->role = $role; return $this; }

    public function isUser(): bool { return $this->role === self::ROLE_USER; }
    public function isAssistant(): bool { return $this->role === self::ROLE_ASSISTANT; }

    public function getContent(): ?string { return $this->content; }
    public function setContent(string $content): static { $this->content = $content; return $this; }

    public function getInputTokens(): ?int { return $this->inputTokens; }
    public function setInputTokens(?int $t): static { $this->inputTokens = $t; return $this; }

    public function getOutputTokens(): ?int { return $this->outputTokens; }
    public function setOutputTokens(?int $t): static { $this->outputTokens = $t; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getConversation(): ?AiConversation { return $this->conversation; }
    public function setConversation(?AiConversation $c): static { $this->conversation = $c; return $this; }
}
