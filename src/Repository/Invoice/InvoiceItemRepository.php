<?php

namespace App\Repository\Invoice;

use App\Entity\Invoice\InvoiceItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InvoiceItem>
 *
 * @method InvoiceItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method InvoiceItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method InvoiceItem[]    findAll()
 * @method InvoiceItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InvoiceItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InvoiceItem::class);
    }

    public function save(InvoiceItem $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(InvoiceItem $entity, bool $flush = false): void
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

        return $qb->getQuery();
    }
}
