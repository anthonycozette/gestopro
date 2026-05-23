<?php

namespace App\Repository;

use App\Entity\Expense;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ExpenseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Expense::class);
    }

    public function getMonthTotal(User $user, int $year, int $month): float
    {
        $result = $this->createQueryBuilder('e')
            ->select('SUM(e.amountTtc) as total')
            ->where('e.user = :user')
            ->andWhere('YEAR(e.date) = :year')
            ->andWhere('MONTH(e.date) = :month')
            ->setParameter('user', $user)
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /** @return array<int, float> index 1–12 => montant TTC */
    public function getMonthlyTotals(User $user, int $year): array
    {
        $rows = $this->createQueryBuilder('e')
            ->select('MONTH(e.date) as m, SUM(e.amountTtc) as total')
            ->where('e.user = :user')
            ->andWhere('YEAR(e.date) = :year')
            ->setParameter('user', $user)
            ->setParameter('year', $year)
            ->groupBy('m')
            ->getQuery()
            ->getArrayResult();

        $data = array_fill(1, 12, 0.0);
        foreach ($rows as $row) {
            $data[(int) $row['m']] = (float) $row['total'];
        }

        return $data;
    }

    public function getYearTotal(User $user, int $year): float
    {
        $result = $this->createQueryBuilder('e')
            ->select('SUM(e.amountTtc) as total')
            ->where('e.user = :user')
            ->andWhere('YEAR(e.date) = :year')
            ->setParameter('user', $user)
            ->setParameter('year', $year)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    public function getTotalForPeriod(User $user, \DateTimeImmutable $start, \DateTimeImmutable $end): float
    {
        $result = $this->createQueryBuilder('e')
            ->select('SUM(e.amountTtc) as total')
            ->where('e.user = :user')
            ->andWhere('e.date >= :start')
            ->andWhere('e.date <= :end')
            ->setParameter('user', $user)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /** @return array<array{label: string, total: float}> */
    public function getTotalsByCategory(User $user, int $year): array
    {
        return $this->createQueryBuilder('e')
            ->select('cat.label, SUM(e.amountTtc) as total')
            ->leftJoin('e.category', 'cat')
            ->where('e.user = :user')
            ->andWhere('YEAR(e.date) = :year')
            ->setParameter('user', $user)
            ->setParameter('year', $year)
            ->groupBy('cat.id')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }
}
