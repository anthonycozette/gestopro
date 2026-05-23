<?php

namespace App\Repository;

use App\Entity\UrssafDeclaration;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UrssafDeclarationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UrssafDeclaration::class);
    }

    public function findNextUndeclared(User $user): ?UrssafDeclaration
    {
        return $this->createQueryBuilder('u')
            ->where('u.user = :user')
            ->andWhere('u.declared = false')
            ->setParameter('user', $user)
            ->orderBy('u.periodEnd', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getCotisationsForPeriod(User $user, \DateTimeImmutable $start, \DateTimeImmutable $end): float
    {
        $result = $this->createQueryBuilder('u')
            ->select('SUM(u.cotisationAmount) as total')
            ->where('u.user = :user')
            ->andWhere('u.periodStart >= :start')
            ->andWhere('u.periodEnd <= :end')
            ->setParameter('user', $user)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    public function getYearCotisation(User $user, int $year): float
    {
        $result = $this->createQueryBuilder('u')
            ->select('SUM(u.cotisationAmount) as total')
            ->where('u.user = :user')
            ->andWhere('YEAR(u.periodStart) = :year')
            ->setParameter('user', $user)
            ->setParameter('year', $year)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }
}
