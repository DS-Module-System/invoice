<?php

namespace App\Repository\Invoice;

use App\Entity\Invoice\Invoice;
use App\Service\Core\CoreUtils;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Invoice>
 *
 * @method Invoice|null find($id, $lockMode = null, $lockVersion = null)
 * @method Invoice|null findOneBy(array $criteria, array $orderBy = null)
 * @method Invoice[]    findAll()
 * @method Invoice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    public function save(Invoice $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Invoice $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param array<mixed> $searchFormData
     */
    public function getPaginatedQuery(array $searchFormData = []): Query
    {
        $qb = $this->createQueryBuilder('t');

        if (!empty($searchFormData['loadInvoiceItems'])) {
            $qb->leftJoin('t.invoiceItems', 'invoiceItems');
        }

        if (isset($searchFormData['isPaid'])) {
            $conn = $this->getEntityManager()->getConnection();
            $sql = "
SELECT
    i.id,
    i.total_price,
    ip.price
FROM invoice AS i
LEFT JOIN (
    SELECT
        id,
        invoice_id,
        SUM(price) AS price
    FROM invoice_payment
    GROUP BY invoice_id) AS ip
ON ip.invoice_id = i.id
            ";
            $stmt = $conn->prepare($sql);
            $result = $stmt->executeQuery();
            $tmpInvoices = $result->fetchAllAssociative();
            $subInvoiceIds = [];
            if (!empty($tmpInvoices)) {
                foreach ($tmpInvoices as $tmpInvoice) {
                    if ($searchFormData['isPaid'] == true) {
                        if ($tmpInvoice['total_price'] == $tmpInvoice['price']) {
                            $subInvoiceIds[] = $tmpInvoice['id'];
                        }
                    } else {
                        if ($tmpInvoice['total_price'] != $tmpInvoice['price']) {
                            $subInvoiceIds[] = $tmpInvoice['id'];
                        }
                    }
                }
            }

            // if(!empty($subInvoiceIds)) {
            $qb->andWhere('t.id IN (:subInvoiceIds)')->setParameter('subInvoiceIds', $subInvoiceIds);
            // }
        }

        if (!empty($searchFormData['client'])) {
            $qb->andWhere("t.client = :client")->setParameter('client', $searchFormData['client']);
        }

        if (!empty($searchFormData['paymentMethod'])) {
            $qb->andWhere("t.paymentMethod = :paymentMethod")->setParameter('paymentMethod', $searchFormData['paymentMethod']->value);
        }

        if (!empty($searchFormData['issueDate'])) {
            $qb->andWhere("t.issueDate = :issueDate")->setParameter('issueDate', $searchFormData['issueDate']);
        }

        if (!empty($searchFormData['fromIssueDate'])) {
            $qb->andWhere("t.issueDate >= :fromIssueDate")->setParameter('fromIssueDate', $searchFormData['fromIssueDate']);
        }

        if (!empty($searchFormData['toIssueDate'])) {
            $qb->andWhere("t.issueDate <= :toIssueDate")->setParameter('toIssueDate', $searchFormData['toIssueDate']);
        }

        if (!empty($searchFormData['priceNet'])) {
            $qb->andWhere("t.subTotalPrice = :priceNet")->setParameter('priceNet', $searchFormData['priceNet']);
        }

        if (!empty($searchFormData['number'])) {
            $qb->andWhere("t.number LIKE :number")->setParameter('number', "%{$searchFormData['number']}%");
        }

        if (!empty($searchFormData['createdAt'])) {
            $beginningOfDay = $searchFormData['createdAt'];
            $endOfDay = $searchFormData['createdAt']->setTime('23', '59', '59');

            $qb->andWhere("t.createdAt >= :beginningOfDay")->setParameter('beginningOfDay', $beginningOfDay);
            $qb->andWhere("t.createdAt <= :endOfDay")->setParameter('endOfDay', $endOfDay);
        }

        if (isset($searchFormData[CoreUtils::$SORT_LIST_QUERY_NAME])) {

            foreach ($searchFormData[CoreUtils::$SORT_LIST_QUERY_NAME] as $sortColumn => $sortValue) {

                $qbSortColumn = '';
                if ($sortColumn == 'number') {
                    $qbSortColumn = 't.number';
                    if (!empty($qbSortColumn)) {
                        $qb->addOrderBy($qbSortColumn, $sortValue);
                    }
                }
                if ($sortColumn == 'issueDate') {
                    $qbSortColumn = 't.issueDate';
                    if (!empty($qbSortColumn)) {
                        $qb->addOrderBy($qbSortColumn, $sortValue);
                        $qb->addOrderBy('t.number', 'DESC');
                    }
                }
            }
        }



        return $qb->getQuery();
    }

    public function getInvoiceById(int $id): Invoice|null
    {
        return $this->createQueryBuilder('t')
            ->addSelect(['invoiceItems'])
            ->leftJoin('t.invoiceItems', 'invoiceItems')
            ->andWhere('t.id = :id')->setParameter('id', $id)
            ->addOrderBy('invoiceItems.ord', 'ASC')
            ->getQuery()->getOneOrNullResult()
        ;
    }

    // public function getPreviousInvoiceByNumber($number): ?Invoice
    // {
    //     return $this->createQueryBuilder('t')
    //         ->andWhere('t.number < :number')->setParameter('number', $number)
    //         ->orderBy('t.number', 'DESC')
    //         ->setMaxResults(1)
    //         ->getQuery()->getOneOrNullResult()
    //     ;
    // }

    // public function getNextInvoiceByNumber($number): ?Invoice
    // {
    //     return $this->createQueryBuilder('t')
    //         ->andWhere('t.number > :number')->setParameter('number', $number)
    //         ->orderBy('t.number', 'ASC')
    //         ->setMaxResults(1)
    //         ->getQuery()->getOneOrNullResult()
    //     ;
    // }

    public function getPreviousInvoiceByNumberAndType(string $number, $type): ?Invoice
    {
        return $this->createQueryBuilder('i')
            ->where('i.type = :type')
            ->andWhere('i.number < :number')
            ->setParameter('type', $type)
            ->setParameter('number', $number)
            ->orderBy('i.number', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getNextInvoiceByNumberAndType(string $number, $type): ?Invoice
    {
        return $this->createQueryBuilder('i')
            ->where('i.type = :type')
            ->andWhere('i.number > :number')
            ->setParameter('type', $type)
            ->setParameter('number', $number)
            ->orderBy('i.number', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
