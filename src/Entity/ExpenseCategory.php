<?php

namespace App\Entity;

use App\Repository\ExpenseCategoryRepository;
use Doctrine\ORM\Mapping as ORM;

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
    private ?string $slug = null;

    #[ORM\Column(length: 100)]
    private ?string $label = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $deductible = true;

    public function getId(): ?int { return $this->id; }

    public function getSlug(): ?string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }

    public function getLabel(): ?string { return $this->label; }
    public function setLabel(string $label): static { $this->label = $label; return $this; }

    public function isDeductible(): bool { return $this->deductible; }
    public function setDeductible(bool $d): static { $this->deductible = $d; return $this; }
}
