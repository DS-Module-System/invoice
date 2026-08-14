<?php

namespace App\Repository\Invoice;

use App\Entity\Invoice\InvoicePayment;
use App\Repository\Core\CoreRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;

/**
 * @extends ServiceEntityRepository<InvoicePayment>
 */
class InvoicePaymentRepository extends ServiceEntityRepository implements CoreRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InvoicePayment::class);
    }

    //    /**
    //     * @return InvoicePayment[] Returns an array of InvoicePayment objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('i')
    //            ->andWhere('i.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('i.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?InvoicePayment
    //    {
    //        return $this->createQueryBuilder('i')
    //            ->andWhere('i.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function getPaginatedQuery(array $searchFormData = []): Query
    {
        $qb = $this->createQueryBuilder('ip');

        if (isset($searchFormData['invoiceId'])) {
            $qb->andWhere('ip.invoice = :invoiceId')
                ->setParameter('invoiceId', $searchFormData['invoiceId']);
        }

        // Сортиране по дата на транзакция (най-новите първи)
        $qb->orderBy('ip.date', 'DESC');

        return $qb->getQuery();
    }
}
