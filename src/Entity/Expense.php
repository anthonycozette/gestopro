<?php

namespace App\Entity;

use App\Repository\ExpenseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ExpenseRepository::class)]
#[ORM\Table(name: 'expenses')]
class Expense
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $vendor = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotBlank]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\PositiveOrZero]
    private string $amountTtc = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $amountHt = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $tva = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private string $tvaRate = '0.00';

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $paymentMethod = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $deductible = true;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    // Justificatif (géré par VichUploader)
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $receiptPath = null;

    private ?File $receiptFile = null;

    // Données OCR extraites par Claude Vision
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $ocrData = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 3, scale: 2, nullable: true)]
    private ?string $ocrConfidence = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $ocrVerified = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'expenses')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: ExpenseCategory::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?ExpenseCategory $category = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->date      = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getVendor(): ?string { return $this->vendor; }
    public function setVendor(string $vendor): static { $this->vendor = $vendor; return $this; }

    public function getDate(): ?\DateTimeImmutable { return $this->date; }
    public function setDate(\DateTimeImmutable $date): static { $this->date = $date; return $this; }

    public function getAmountTtc(): string { return $this->amountTtc; }
    public function setAmountTtc(string $amount): static { $this->amountTtc = $amount; return $this; }

    public function getAmountHt(): string { return $this->amountHt; }
    public function setAmountHt(string $amount): static { $this->amountHt = $amount; return $this; }

    public function getTva(): string { return $this->tva; }
    public function setTva(string $tva): static { $this->tva = $tva; return $this; }

    public function getTvaRate(): string { return $this->tvaRate; }
    public function setTvaRate(string $rate): static { $this->tvaRate = $rate; return $this; }

    public function getPaymentMethod(): ?string { return $this->paymentMethod; }
    public function setPaymentMethod(?string $method): static { $this->paymentMethod = $method; return $this; }

    public function isDeductible(): bool { return $this->deductible; }
    public function setDeductible(bool $d): static { $this->deductible = $d; return $this; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): static { $this->notes = $notes; return $this; }

    public function getReceiptPath(): ?string { return $this->receiptPath; }
    public function setReceiptPath(?string $path): static { $this->receiptPath = $path; return $this; }

    public function getReceiptFile(): ?File { return $this->receiptFile; }
    public function setReceiptFile(?File $file): static { $this->receiptFile = $file; return $this; }

    public function getOcrData(): ?array { return $this->ocrData; }
    public function setOcrData(?array $data): static { $this->ocrData = $data; return $this; }

    public function getOcrConfidence(): ?string { return $this->ocrConfidence; }
    public function setOcrConfidence(?string $confidence): static { $this->ocrConfidence = $confidence; return $this; }

    public function isOcrVerified(): bool { return $this->ocrVerified; }
    public function setOcrVerified(bool $verified): static { $this->ocrVerified = $verified; return $this; }

    public function getOcrConfidenceLevel(): string
    {
        if ($this->ocrConfidence === null) { return 'none'; }
        $c = (float) $this->ocrConfidence;
        if ($c >= 0.85) { return 'high'; }
        if ($c >= 0.65) { return 'medium'; }
        return 'low';
    }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getCategory(): ?ExpenseCategory { return $this->category; }
    public function setCategory(?ExpenseCategory $category): static { $this->category = $category; return $this; }
}
