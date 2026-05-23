<?php

namespace App\Repository;

use App\Entity\Accountant;
use App\Entity\AccountantInvitation;
use App\Entity\BalanceSheet;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BalanceSheetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BalanceSheet::class);
    }

    /** @return BalanceSheet[] */
    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['createdAt' => 'DESC']);
    }

    /**
     * Balance sheets visible by the accountant = from users who accepted their invitation,
     * with status != draft.
     *
     * @return BalanceSheet[]
     */
    public function findForAccountant(Accountant $accountant): array
    {
        return $this->createQueryBuilder('b')
            ->join('b.user', 'u')
            ->join(
                AccountantInvitation::class,
                'i',
                'WITH',
                'i.user = u AND i.accountant = :accountant AND i.status = :accepted'
            )
            ->where('b.status != :draft')
            ->setParameter('accountant', $accountant)
            ->setParameter('accepted', AccountantInvitation::STATUS_ACCEPTED)
            ->setParameter('draft', BalanceSheet::STATUS_DRAFT)
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
