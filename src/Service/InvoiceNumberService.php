<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\InvoiceRepository;

class InvoiceNumberService
{
    public function __construct(private readonly InvoiceRepository $repo) {}

    public function generate(User $user): string
    {
        $year = (int) date('Y');
        $last = $this->repo->findLastNumberForYear($user, $year);

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return sprintf('FAC-%d-%04d', $year, $seq);
    }
}
