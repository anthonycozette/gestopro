<?php

namespace App\Entity;

use App\Repository\UrssafDeclarationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UrssafDeclarationRepository::class)]
#[ORM\Table(name: 'urssaf_declarations')]
class UrssafDeclaration
{
    public const PERIOD_MONTHLY   = 'monthly';
    public const PERIOD_QUARTERLY = 'quarterly';

    // Taux de cotisation auto-entrepreneur 2024 (BNC / prestations de services)
    public const RATE_BNC              = 0.212;  // 21.2%
    public const RATE_BIC_SERVICES     = 0.212;
    public const RATE_BIC_COMMERCE     = 0.128;  // 12.8%
    public const RATE_LIBERAL_CIPAV    = 0.218;  // 21.8%

    // Seuils 2024
    public const THRESHOLD_TVA_SERVICES = 36800.0;
    public const THRESHOLD_TVA_COMMERCE = 91900.0;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private string $periodicity = self::PERIOD_QUARTERLY;

    // Période : ex. "2024-T1" ou "2024-01"
    #[ORM\Column(length: 10)]
    #[Assert\NotBlank]
    private ?string $periodLabel = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $periodStart = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $periodEnd = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    #[Assert\PositiveOrZero]
    private string $revenue = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 4)]
    private string $cotisationRate = '0.2120';

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $cotisationAmount = '0.00';

    #[ORM\Column(options: ['default' => false])]
    private bool $declared = false;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $declaredAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'urssafDeclarations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getPeriodicity(): string { return $this->periodicity; }
    public function setPeriodicity(string $p): static { $this->periodicity = $p; return $this; }

    public function getPeriodLabel(): ?string { return $this->periodLabel; }
    public function setPeriodLabel(string $label): static { $this->periodLabel = $label; return $this; }

    public function getPeriodStart(): ?\DateTimeImmutable { return $this->periodStart; }
    public function setPeriodStart(\DateTimeImmutable $d): static { $this->periodStart = $d; return $this; }

    public function getPeriodEnd(): ?\DateTimeImmutable { return $this->periodEnd; }
    public function setPeriodEnd(\DateTimeImmutable $d): static { $this->periodEnd = $d; return $this; }

    public function getRevenue(): string { return $this->revenue; }
    public function setRevenue(string $amount): static
    {
        $this->revenue = $amount;
        $this->computeCotisation();
        return $this;
    }

    public function getCotisationRate(): string { return $this->cotisationRate; }
    public function setCotisationRate(string $rate): static { $this->cotisationRate = $rate; return $this; }

    public function getCotisationAmount(): string { return $this->cotisationAmount; }

    public function isDeclared(): bool { return $this->declared; }
    public function setDeclared(bool $d): static { $this->declared = $d; return $this; }

    public function getDeclaredAt(): ?\DateTimeImmutable { return $this->declaredAt; }
    public function setDeclaredAt(?\DateTimeImmutable $d): static { $this->declaredAt = $d; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    private function computeCotisation(): void
    {
        $amount = (float) $this->revenue * (float) $this->cotisationRate;
        $this->cotisationAmount = number_format($amount, 2, '.', '');
    }
}
