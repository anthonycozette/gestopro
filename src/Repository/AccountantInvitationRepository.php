<?php

namespace App\Repository;

use App\Entity\Accountant;
use App\Entity\AccountantInvitation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AccountantInvitationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccountantInvitation::class);
    }

    public function findByToken(string $token): ?AccountantInvitation
    {
        return $this->findOneBy(['token' => $token]);
    }

    public function findForUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['createdAt' => 'DESC']);
    }

    /** @return AccountantInvitation[] */
    public function findAcceptedByAccountant(Accountant $accountant): array
    {
        return $this->findBy(['accountant' => $accountant, 'status' => AccountantInvitation::STATUS_ACCEPTED]);
    }

    public function existsPendingForEmail(Accountant $accountant, string $email): bool
    {
        return (bool) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->join('i.user', 'u')
            ->where('i.accountant = :accountant')
            ->andWhere('u.email = :email')
            ->andWhere('i.status = :status')
            ->setParameter('accountant', $accountant)
            ->setParameter('email', $email)
            ->setParameter('status', AccountantInvitation::STATUS_PENDING)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
