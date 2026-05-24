<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\InvoiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    normalizationContext: ['groups' => ['invoice:read']],
    denormalizationContext: ['groups' => ['invoice:write']],
    security: "is_granted('ROLE_USER')",
    operations: [
        new GetCollection(),
        new Post(),
        new Get(security: "is_granted('ROLE_USER') and object.getUser() == user"),
        new Patch(security: "is_granted('ROLE_USER') and object.getUser() == user"),
        new Delete(security: "is_granted('ROLE_USER') and object.getUser() == user"),
    ],
)]
#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
#[ORM\Table(name: 'invoices')]
#[ORM\UniqueConstraint(name: 'uniq_invoice_user_number', columns: ['user_id', 'number'])]
class Invoice
{
    public const TYPE_INVOICE = 'invoice';
    public const TYPE_QUOTE   = 'quote';

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_SENT      = 'sent';
    public const STATUS_PAID      = 'paid';
    public const STATUS_OVERDUE   = 'overdue';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_ACCEPTED  = 'accepted';
    public const STATUS_DECLINED  = 'declined';
    public const STATUS_EXPIRED   = 'expired';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SENT,
        self::STATUS_PAID,
        self::STATUS_OVERDUE,
        self::STATUS_CANCELLED,
        self::STATUS_ACCEPTED,
        self::STATUS_DECLINED,
        self::STATUS_EXPIRED,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, options: ['default' => self::TYPE_INVOICE])]
    private string $type = self::TYPE_INVOICE;

    #[ORM\Column(length: 30)]
    #[Groups(['invoice:read'])]
    private ?string $number = null;

    #[ORM\Column(length: 20, options: ['default' => self::STATUS_DRAFT])]
    #[Groups(['invoice:read', 'invoice:write'])]
    private string $status = self::STATUS_DRAFT;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotBlank]
    #[Groups(['invoice:read', 'invoice:write'])]
    private ?\DateTimeImmutable $issuedAt = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Groups(['invoice:read', 'invoice:write'])]
    private ?\DateTimeImmutable $dueAt = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Groups(['invoice:read', 'invoice:write'])]
    private ?\DateTimeImmutable $paidAt = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['invoice:read'])]
    private string $totalHt = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['invoice:read'])]
    private string $totalTva = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['invoice:read'])]
    private string $totalTtc = '0.00';

    #[ORM\Column(length: 3, options: ['default' => 'EUR'])]
    #[Groups(['invoice:read', 'invoice:write'])]
    private string $currency = 'EUR';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['invoice:read', 'invoice:write'])]
    private ?string $notes = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['invoice:read'])]
    private ?string $pdfPath = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastReminderAt = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $reminderCount = 0;

    #[ORM\Column]
    #[Groups(['invoice:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'invoices')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'invoices')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['invoice:read', 'invoice:write'])]
    private ?Client $client = null;

    #[ORM\OneToMany(targetEntity: InvoiceLine::class, mappedBy: 'invoice', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    #[Groups(['invoice:read', 'invoice:write'])]
    private Collection $lines;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->issuedAt  = new \DateTimeImmutable();
        $this->lines     = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }
    public function isQuote(): bool { return $this->type === self::TYPE_QUOTE; }

    public function getNumber(): ?string { return $this->number; }
    public function setNumber(string $number): static { $this->number = $number; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function isDraft(): bool { return $this->status === self::STATUS_DRAFT; }
    public function isPaid(): bool { return $this->status === self::STATUS_PAID; }
    public function isOverdue(): bool { return $this->status === self::STATUS_OVERDUE; }
    public function isAccepted(): bool { return $this->status === self::STATUS_ACCEPTED; }

    public function getIssuedAt(): ?\DateTimeImmutable { return $this->issuedAt; }
    public function setIssuedAt(\DateTimeImmutable $date): static { $this->issuedAt = $date; return $this; }

    public function getDueAt(): ?\DateTimeImmutable { return $this->dueAt; }
    public function setDueAt(?\DateTimeImmutable $date): static { $this->dueAt = $date; return $this; }

    public function getPaidAt(): ?\DateTimeImmutable { return $this->paidAt; }
    public function setPaidAt(?\DateTimeImmutable $date): static { $this->paidAt = $date; return $this; }

    public function getTotalHt(): string { return $this->totalHt; }
    public function setTotalHt(string $amount): static { $this->totalHt = $amount; return $this; }

    public function getTotalTva(): string { return $this->totalTva; }
    public function setTotalTva(string $amount): static { $this->totalTva = $amount; return $this; }

    public function getTotalTtc(): string { return $this->totalTtc; }
    public function setTotalTtc(string $amount): static { $this->totalTtc = $amount; return $this; }

    public function getCurrency(): string { return $this->currency; }
    public function setCurrency(string $currency): static { $this->currency = $currency; return $this; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): static { $this->notes = $notes; return $this; }

    public function getPdfPath(): ?string { return $this->pdfPath; }
    public function setPdfPath(?string $path): static { $this->pdfPath = $path; return $this; }

    public function getLastReminderAt(): ?\DateTimeImmutable { return $this->lastReminderAt; }
    public function setLastReminderAt(?\DateTimeImmutable $d): static { $this->lastReminderAt = $d; return $this; }

    public function getReminderCount(): int { return $this->reminderCount; }
    public function setReminderCount(int $count): static { $this->reminderCount = $count; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getClient(): ?Client { return $this->client; }
    public function setClient(?Client $client): static { $this->client = $client; return $this; }

    public function getLines(): Collection { return $this->lines; }

    public function addLine(InvoiceLine $line): static
    {
        if (!$this->lines->contains($line)) {
            $this->lines->add($line);
            $line->setInvoice($this);
        }
        return $this;
    }

    public function removeLine(InvoiceLine $line): static
    {
        $this->lines->removeElement($line);
        return $this;
    }

    public function recalculateTotals(): void
    {
        $ht  = 0.0;
        $tva = 0.0;
        foreach ($this->lines as $line) {
            $ht  += (float) $line->getTotalHt();
            $tva += (float) $line->getTotalTva();
        }
        $this->totalHt  = number_format($ht, 2, '.', '');
        $this->totalTva = number_format($tva, 2, '.', '');
        $this->totalTtc = number_format($ht + $tva, 2, '.', '');
    }
}
