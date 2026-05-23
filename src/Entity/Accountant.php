<?php

namespace App\Entity;

use App\Repository\AccountantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AccountantRepository::class)]
#[ORM\Table(name: 'accountants')]
#[UniqueEntity(fields: ['email'])]
class Accountant implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firm = null;

    // Numéro ORIAS ou inscription Ordre des Experts-Comptables
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $registrationNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stampPath = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(targetEntity: AccountantInvitation::class, mappedBy: 'accountant', cascade: ['persist', 'remove'])]
    private Collection $invitations;

    public function __construct()
    {
        $this->createdAt   = new \DateTimeImmutable();
        $this->invitations = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getUserIdentifier(): string { return (string) $this->email; }
    public function getRoles(): array { return ['ROLE_ACCOUNTANT', 'ROLE_USER']; }
    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }
    public function eraseCredentials(): void {}

    public function getFirstName(): ?string { return $this->firstName; }
    public function setFirstName(string $n): static { $this->firstName = $n; return $this; }

    public function getLastName(): ?string { return $this->lastName; }
    public function setLastName(string $n): static { $this->lastName = $n; return $this; }

    public function getFullName(): string { return $this->firstName . ' ' . $this->lastName; }

    public function getFirm(): ?string { return $this->firm; }
    public function setFirm(?string $firm): static { $this->firm = $firm; return $this; }

    public function getRegistrationNumber(): ?string { return $this->registrationNumber; }
    public function setRegistrationNumber(?string $n): static { $this->registrationNumber = $n; return $this; }

    public function getStampPath(): ?string { return $this->stampPath; }
    public function setStampPath(?string $path): static { $this->stampPath = $path; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getInvitations(): Collection { return $this->invitations; }
}
