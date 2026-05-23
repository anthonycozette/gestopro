<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Repository\BalanceSheetRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    normalizationContext: ['groups' => ['balance_sheet:read']],
    denormalizationContext: ['groups' => ['balance_sheet:write']],
    security: "is_granted('ROLE_USER')",
    operations: [
        new GetCollection(),
        new Post(),
        new Get(security: "is_granted('ROLE_USER') and object.getUser() == user"),
    ],
)]
#[ORM\Entity(repositoryClass: BalanceSheetRepository::class)]
#[ORM\Table(name: 'balance_sheets')]
class BalanceSheet
{
    public const STATUS_DRAFT          = 'draft';
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_ANNOTATED      = 'annotated';
    public const STATUS_VALIDATED      = 'validated';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_DRAFT;

    // Période : "2024", "2024-T1", "2024-01"
    #[ORM\Column(length: 10)]
    private ?string $period = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $periodStart = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $periodEnd = null;

    // Données financières agrégées (JSON)
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $financialData = null;

    // Analyse narrative générée par Claude
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $aiAnalysis = null;

    // Annotations de l'expert-comptable
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $accountantAnnotations = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $accountantComment = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stampPath = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $validatedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pdfPath = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'balanceSheets')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Accountant::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Accountant $accountant = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getPeriod(): ?string { return $this->period; }
    public function setPeriod(string $period): static { $this->period = $period; return $this; }

    public function getPeriodStart(): ?\DateTimeImmutable { return $this->periodStart; }
    public function setPeriodStart(\DateTimeImmutable $d): static { $this->periodStart = $d; return $this; }

    public function getPeriodEnd(): ?\DateTimeImmutable { return $this->periodEnd; }
    public function setPeriodEnd(\DateTimeImmutable $d): static { $this->periodEnd = $d; return $this; }

    public function getFinancialData(): ?array { return $this->financialData; }
    public function setFinancialData(?array $data): static { $this->financialData = $data; return $this; }

    public function getAiAnalysis(): ?string { return $this->aiAnalysis; }
    public function setAiAnalysis(?string $analysis): static { $this->aiAnalysis = $analysis; return $this; }

    public function getAccountantAnnotations(): ?array { return $this->accountantAnnotations; }
    public function setAccountantAnnotations(?array $annotations): static { $this->accountantAnnotations = $annotations; return $this; }

    public function getAccountantComment(): ?string { return $this->accountantComment; }
    public function setAccountantComment(?string $comment): static { $this->accountantComment = $comment; return $this; }

    public function getStampPath(): ?string { return $this->stampPath; }
    public function setStampPath(?string $path): static { $this->stampPath = $path; return $this; }

    public function getValidatedAt(): ?\DateTimeImmutable { return $this->validatedAt; }
    public function setValidatedAt(?\DateTimeImmutable $d): static { $this->validatedAt = $d; return $this; }

    public function getPdfPath(): ?string { return $this->pdfPath; }
    public function setPdfPath(?string $path): static { $this->pdfPath = $path; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getAccountant(): ?Accountant { return $this->accountant; }
    public function setAccountant(?Accountant $accountant): static { $this->accountant = $accountant; return $this; }
}
