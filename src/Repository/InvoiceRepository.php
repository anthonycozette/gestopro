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

    public function countThisMonth(User $user): int
    {
        $now = new \DateTimeImmutable();

        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.user = :user')
            ->andWhere('YEAR(i.issuedAt) = :year')
            ->andWhere('MONTH(i.issuedAt) = :month')
            ->setParameter('user', $user)
            ->setParameter('year', (int) $now->format('Y'))
            ->setParameter('month', (int) $now->format('n'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findLastNumberForYear(User $user, int $year, string $prefix = 'FAC'): ?string
    {
        $result = $this->createQueryBuilder('i')
            ->select('i.number')
            ->where('i.user = :user')
            ->andWhere('i.number LIKE :prefix')
            ->setParameter('user', $user)
            ->setParameter('prefix', $prefix . '-' . $year . '-%')
            ->orderBy('i.number', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result ? $result['number'] : null;
    }

    public function getYearRevenue(User $user, int $year): float
    {
        $result = $this->createQueryBuilder('i')
            ->select('SUM(i.totalHt) as total')
            ->where('i.user = :user')
            ->andWhere('i.status = :status')
            ->andWhere('YEAR(i.issuedAt) = :year')
            ->setParameter('user', $user)
            ->setParameter('status', Invoice::STATUS_PAID)
            ->setParameter('year', $year)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    public function getMonthRevenue(User $user, int $year, int $month): float
    {
        $result = $this->createQueryBuilder('i')
            ->select('SUM(i.totalHt) as total')
            ->where('i.user = :user')
            ->andWhere('i.status = :status')
            ->andWhere('YEAR(i.issuedAt) = :year')
            ->andWhere('MONTH(i.issuedAt) = :month')
            ->setParameter('user', $user)
            ->setParameter('status', Invoice::STATUS_PAID)
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /** @return array{ht: float, tva: float, ttc: float} */
    public function getMonthRevenueDetails(User $user, int $year, int $month): array
    {
        $result = $this->createQueryBuilder('i')
            ->select('SUM(i.totalHt) as ht, SUM(i.totalTva) as tva, SUM(i.totalTtc) as ttc')
            ->where('i.user = :user')
            ->andWhere('i.status = :status')
            ->andWhere('YEAR(i.issuedAt) = :year')
            ->andWhere('MONTH(i.issuedAt) = :month')
            ->setParameter('user', $user)
            ->setParameter('status', Invoice::STATUS_PAID)
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->getQuery()
            ->getSingleResult();

        return [
            'ht'  => (float) ($result['ht']  ?? 0),
            'tva' => (float) ($result['tva'] ?? 0),
            'ttc' => (float) ($result['ttc'] ?? 0),
        ];
    }

    /** @return array<int, float> index 1–12 => montant TTC */
    public function getMonthlyRevenueTtc(User $user, int $year): array
    {
        $rows = $this->createQueryBuilder('i')
            ->select('MONTH(i.issuedAt) as m, SUM(i.totalTtc) as total')
            ->where('i.user = :user')
            ->andWhere('i.status = :status')
            ->andWhere('YEAR(i.issuedAt) = :year')
            ->setParameter('user', $user)
            ->setParameter('status', Invoice::STATUS_PAID)
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

    /** @return array<int, float> index 1–12 => montant HT */
    public function getMonthlyRevenue(User $user, int $year): array
    {
        $rows = $this->createQueryBuilder('i')
            ->select('MONTH(i.issuedAt) as m, SUM(i.totalHt) as total')
            ->where('i.user = :user')
            ->andWhere('i.status = :status')
            ->andWhere('YEAR(i.issuedAt) = :year')
            ->setParameter('user', $user)
            ->setParameter('status', Invoice::STATUS_PAID)
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

    /** @return array{count: int, amount: float} */
    public function getPendingStats(User $user): array
    {
        $result = $this->createQueryBuilder('i')
            ->select('COUNT(i.id) as cnt, SUM(i.totalTtc) as amount')
            ->where('i.user = :user')
            ->andWhere('i.status IN (:statuses)')
            ->setParameter('user', $user)
            ->setParameter('statuses', [Invoice::STATUS_SENT, Invoice::STATUS_OVERDUE])
            ->getQuery()
            ->getSingleResult();

        return ['count' => (int) $result['cnt'], 'amount' => (float) ($result['amount'] ?? 0)];
    }

    public function countOverdue(User $user): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.user = :user')
            ->andWhere('i.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Invoice::STATUS_OVERDUE)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getRecoveryRate(User $user, int $year): float
    {
        $result = $this->createQueryBuilder('i')
            ->select('COUNT(i.id) as total, SUM(CASE WHEN i.status = :paid THEN 1 ELSE 0 END) as paid')
            ->where('i.user = :user')
            ->andWhere('i.status IN (:statuses)')
            ->andWhere('YEAR(i.issuedAt) = :year')
            ->setParameter('user', $user)
            ->setParameter('paid', Invoice::STATUS_PAID)
            ->setParameter('statuses', [Invoice::STATUS_PAID, Invoice::STATUS_SENT, Invoice::STATUS_OVERDUE])
            ->setParameter('year', $year)
            ->getQuery()
            ->getSingleResult();

        if ((int) $result['total'] === 0) {
            return 0.0;
        }

        return round((float) $result['paid'] / (float) $result['total'] * 100, 1);
    }

    /** @return array<array{name: string, total: float}> */
    public function getTopClients(User $user, int $limit = 5): array
    {
        return $this->createQueryBuilder('i')
            ->select('c.name, SUM(i.totalHt) as total')
            ->join('i.client', 'c')
            ->where('i.user = :user')
            ->andWhere('i.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Invoice::STATUS_PAID)
            ->groupBy('c.id')
            ->orderBy('total', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    public function getRevenueForPeriod(User $user, \DateTimeImmutable $start, \DateTimeImmutable $end): float
    {
        $result = $this->createQueryBuilder('i')
            ->select('SUM(i.totalHt) as total')
            ->where('i.user = :user')
            ->andWhere('i.status = :status')
            ->andWhere('i.issuedAt >= :start')
            ->andWhere('i.issuedAt <= :end')
            ->setParameter('user', $user)
            ->setParameter('status', Invoice::STATUS_PAID)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /** @return Invoice[] */
    public function findRecentByUser(User $user, int $limit = 5): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.user = :user')
            ->setParameter('user', $user)
            ->orderBy('i.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** @return array{sent: int, paid: int, overdue: int, draft: int} */
    public function getStatusCounts(User $user): array
    {
        $rows = $this->createQueryBuilder('i')
            ->select('i.status, COUNT(i.id) as cnt')
            ->where('i.user = :user')
            ->andWhere('i.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', Invoice::TYPE_INVOICE)
            ->groupBy('i.status')
            ->getQuery()
            ->getArrayResult();

        $counts = ['sent' => 0, 'paid' => 0, 'overdue' => 0, 'draft' => 0];
        foreach ($rows as $row) {
            if (isset($counts[$row['status']])) {
                $counts[$row['status']] = (int) $row['cnt'];
            }
        }

        return $counts;
    }

    /**
     * Retourne les factures (type invoice) envoyées/en retard dont l'échéance
     * est dans les $days prochains jours.
     *
     * @return Invoice[]
     */
    public function findDueSoon(User $user, int $days = 14): array
    {
        $now  = new \DateTimeImmutable();
        $limit = $now->modify("+{$days} days");

        return $this->createQueryBuilder('i')
            ->where('i.user = :user')
            ->andWhere('i.type = :type')
            ->andWhere('i.status IN (:statuses)')
            ->andWhere('i.dueAt >= :now')
            ->andWhere('i.dueAt <= :limit')
            ->setParameter('user', $user)
            ->setParameter('type', Invoice::TYPE_INVOICE)
            ->setParameter('statuses', [Invoice::STATUS_SENT, Invoice::STATUS_OVERDUE])
            ->setParameter('now', $now)
            ->setParameter('limit', $limit)
            ->orderBy('i.dueAt', 'ASC')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les devis envoyés sans réponse depuis plus de $intervalDays jours.
     *
     * @return Invoice[]
     */
    public function findQuotesNeedingReminder(int $intervalDays = 7): array
    {
        $threshold = new \DateTimeImmutable("-{$intervalDays} days");

        return $this->createQueryBuilder('i')
            ->where('i.type = :type')
            ->andWhere('i.status = :status')
            ->andWhere(
                '(i.lastReminderAt IS NULL AND i.issuedAt <= :threshold) OR i.lastReminderAt <= :threshold'
            )
            ->setParameter('type', Invoice::TYPE_QUOTE)
            ->setParameter('status', Invoice::STATUS_SENT)
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les factures envoyées/en retard dont la dernière relance date de plus de
     * $intervalDays jours (ou jamais relancées depuis plus de $intervalDays jours après envoi).
     *
     * @return Invoice[]
     */
    public function findNeedingReminder(int $intervalDays = 7): array
    {
        $threshold = new \DateTimeImmutable("-{$intervalDays} days");

        return $this->createQueryBuilder('i')
            ->join('i.user', 'u')
            ->where('i.status IN (:statuses)')
            ->andWhere('i.type = :type')
            ->andWhere(
                '(i.lastReminderAt IS NULL AND i.issuedAt <= :threshold) OR i.lastReminderAt <= :threshold'
            )
            ->setParameter('statuses', [Invoice::STATUS_SENT, Invoice::STATUS_OVERDUE])
            ->setParameter('type', Invoice::TYPE_INVOICE)
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getResult();
    }
}
