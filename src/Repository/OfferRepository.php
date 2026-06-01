<?php

namespace App\Repository;

use App\Entity\Enum\OfferStatus;
use App\Entity\Offer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Offer>
 */
class OfferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Offer::class);
    }

    public function findPublishedWithFilters(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('o')
            ->join('o.company', 'c')
            ->where('o.status = :status')
            ->setParameter('status', OfferStatus::Published)
            ->orderBy('o.createdAt', 'DESC');

        if (!empty($filters['type'])) {
            $qb->andWhere('o.type = :type')
               ->setParameter('type', $filters['type']);
        }

        if (!empty($filters['skillId'])) {
            $qb->join('o.requiredSkills', 'rs')
               ->andWhere('rs.id = :skillId')
               ->setParameter('skillId', (int) $filters['skillId']);
        }

        if (!empty($filters['isRemote'])) {
            $qb->andWhere('o.isRemote = :isRemote')
               ->setParameter('isRemote', filter_var($filters['isRemote'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filters['search'])) {
            $qb->andWhere('o.title LIKE :search OR o.description LIKE :search OR c.name LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        $offset = ($page - 1) * $limit;

        return $qb->setFirstResult($offset)
                  ->setMaxResults($limit)
                  ->getQuery()
                  ->getResult();
    }

    public function findOneWithDetails(int $id): ?Offer
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.requiredSkills', 'rs')->addSelect('rs')
            ->leftJoin('o.company', 'c')->addSelect('c')
            ->where('o.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
