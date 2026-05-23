<?php

namespace App\Repository;

use App\Entity\Invoice;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    public function findLastNumberForYear(User $user, int $year): ?string
    {
        $result = $this->createQueryBuilder('i')
            ->select('i.number')
            ->where('i.user = :user')
            ->andWhere('i.number LIKE :prefix')
            ->setParameter('user', $user)
            ->setParameter('prefix', 'FAC-' . $year . '-%')
            ->orderBy('i.number', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result ? $result['number'] : null;
    }
}
