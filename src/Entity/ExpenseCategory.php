<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\ExpenseCategoryRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    normalizationContext: ['groups' => ['expense_category:read']],
    security: "is_granted('ROLE_USER')",
    operations: [new GetCollection()],
)]
#[ORM\Entity(repositoryClass: ExpenseCategoryRepository::class)]
#[ORM\Table(name: 'expense_categories')]
class ExpenseCategory
{
    public const DEFAULTS = [
        'repas'        => 'Repas & restauration',
        'carburant'    => 'Carburant',
        'transport'    => 'Transport & déplacements',
        'hebergement'  => 'Hébergement',
        'materiel'     => 'Matériel & équipement',
        'logiciel'     => 'Logiciels & abonnements',
        'telecom'      => 'Télécom & internet',
        'formation'    => 'Formation',
        'marketing'    => 'Marketing & publicité',
        'bancaire'     => 'Frais bancaires',
        'autre'        => 'Autre',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups(['expense_category:read', 'expense:read'])]
    private ?string $slug = null;

    #[ORM\Column(length: 100)]
    #[Groups(['expense_category:read', 'expense:read'])]
    private ?string $label = null;

    #[ORM\Column(options: ['default' => true])]
    #[Groups(['expense_category:read', 'expense:read'])]
    private bool $deductible = true;

    public function getId(): ?int { return $this->id; }

    public function getSlug(): ?string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }

    public function getLabel(): ?string { return $this->label; }
    public function setLabel(string $label): static { $this->label = $label; return $this; }

    public function isDeductible(): bool { return $this->deductible; }
    public function setDeductible(bool $d): static { $this->deductible = $d; return $this; }
}
