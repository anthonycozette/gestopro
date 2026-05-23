<?php

namespace App\Entity;

use App\Repository\InvoiceLineRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: InvoiceLineRepository::class)]
#[ORM\Table(name: 'invoice_lines')]
class InvoiceLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\Positive]
    private string $quantity = '1.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\PositiveOrZero]
    private string $unitPrice = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private string $tvaRate = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $totalHt = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $totalTva = '0.00';

    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    #[ORM\ManyToOne(targetEntity: Invoice::class, inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Invoice $invoice = null;

    public function getId(): ?int { return $this->id; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(string $d): static { $this->description = $d; return $this; }

    public function getQuantity(): string { return $this->quantity; }
    public function setQuantity(string $q): static { $this->quantity = $q; $this->compute(); return $this; }

    public function getUnitPrice(): string { return $this->unitPrice; }
    public function setUnitPrice(string $p): static { $this->unitPrice = $p; $this->compute(); return $this; }

    public function getTvaRate(): string { return $this->tvaRate; }
    public function setTvaRate(string $r): static { $this->tvaRate = $r; $this->compute(); return $this; }

    public function getTotalHt(): string { return $this->totalHt; }
    public function getTotalTva(): string { return $this->totalTva; }

    public function getTotalTtc(): string
    {
        return number_format((float) $this->totalHt + (float) $this->totalTva, 2, '.', '');
    }

    public function getPosition(): int { return $this->position; }
    public function setPosition(int $p): static { $this->position = $p; return $this; }

    public function getInvoice(): ?Invoice { return $this->invoice; }
    public function setInvoice(?Invoice $invoice): static { $this->invoice = $invoice; return $this; }

    private function compute(): void
    {
        $ht             = (float) $this->quantity * (float) $this->unitPrice;
        $tva            = $ht * ((float) $this->tvaRate / 100);
        $this->totalHt  = number_format($ht, 2, '.', '');
        $this->totalTva = number_format($tva, 2, '.', '');
    }
}
