<?php

namespace App\Repository;

use App\Entity\AccountantInvitation;
use App\Entity\ExpertMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ExpertMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExpertMessage::class);
    }

    public function findByInvitation(AccountantInvitation $invitation): array
    {
        return $this->findBy(['invitation' => $invitation], ['createdAt' => 'ASC']);
    }
}
