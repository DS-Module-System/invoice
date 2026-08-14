<?php

namespace App\Repository\Invoice;

use App\Service\Admin\CMSUtils;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Invoice\ClientInvoiceEmailLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<ClientInvoiceEmailLog>
 *
 * @method ClientInvoiceEmailLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClientInvoiceEmailLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClientInvoiceEmailLog[]    findAll()
 * @method ClientInvoiceEmailLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClientInvoiceEmailLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClientInvoiceEmailLog::class);
    }

    public function save(ClientInvoiceEmailLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ClientInvoiceEmailLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getPaginatedQuery($clientId, array $searchFormData = [])
    {
        $qb = $this->createQueryBuilder('t')
            ->addSelect(['clientAddress', 'client'])
            ->leftJoin('t.clientAddress', 'clientAddress')
            ->leftJoin('clientAddress.client', 'client')
            ->andWhere('client.id = :clientId')
            ->setParameter('clientId', $clientId);



        if (!empty($searchFormData['createdBy'])) {
            $qb->leftJoin('t.createdBy', 'user')->andWhere("user.id = :createdBy")->setParameter('createdBy', $searchFormData['createdBy']->getId());
        }

        if (!empty($searchFormData['fromDate'])) {
            $qb->andWhere("t.createdAt >= :fromDate")->setParameter('fromDate', $searchFormData['fromDate']);
        }

        if (!empty($searchFormData['toDate'])) {
            $qb->andWhere("t.createdAt <= :toDate")->setParameter('toDate', $searchFormData['toDate']);
        }

        if (!empty($searchFormData['invoiceType'])) {
            $qb->andWhere("t.invoiceType = :invoiceType")->setParameter('invoiceType', $searchFormData['invoiceType']->value);
        }

        if (isset($searchFormData[CMSUtils::$SORT_LIST_QUERY_NAME])) {

            foreach ($searchFormData[CMSUtils::$SORT_LIST_QUERY_NAME] as $sortColumn => $sortValue) {

                $qbSortColumn = '';
                if ($sortColumn == 'createdAt') {
                    $qbSortColumn = 't.createdAt';
                }

                if (!empty($qbSortColumn)) {
                    $qb->addOrderBy($qbSortColumn, $sortValue);
                }
            }
        }

        return $qb->getQuery();
    }
}
