<?php

namespace App\Entity;

use App\Repository\URSSAFDeclarationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: URSSAFDeclarationRepository::class)]
class URSSAFDeclaration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 7)]
    private ?string $period = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $turnover = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $contributionAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $contributionRate = null;

    #[ORM\Column(length: 20)]
    private ?string $status = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $dueDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $paidDate = null;

    #[ORM\ManyToOne(inversedBy: 'uRSSAFDeclarations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPeriod(): ?string
    {
        return $this->period;
    }

    public function setPeriod(string $period): static
    {
        $this->period = $period;

        return $this;
    }

    public function getTurnover(): ?string
    {
        return $this->turnover;
    }

    public function setTurnover(string $turnover): static
    {
        $this->turnover = $turnover;

        return $this;
    }

    public function getContributionAmount(): ?string
    {
        return $this->contributionAmount;
    }

    public function setContributionAmount(string $contributionAmount): static
    {
        $this->contributionAmount = $contributionAmount;

        return $this;
    }

    public function getContributionRate(): ?string
    {
        return $this->contributionRate;
    }

    public function setContributionRate(string $contributionRate): static
    {
        $this->contributionRate = $contributionRate;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getDueDate(): ?\DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function setDueDate(\DateTimeImmutable $dueDate): static
    {
        $this->dueDate = $dueDate;

        return $this;
    }

    public function getPaidDate(): ?\DateTimeImmutable
    {
        return $this->paidDate;
    }

    public function setPaidDate(?\DateTimeImmutable $paidDate): static
    {
        $this->paidDate = $paidDate;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
