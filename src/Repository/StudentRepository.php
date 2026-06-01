<?php

namespace App\Repository;

use App\Entity\Student;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Student>
 */
class StudentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Student::class);
    }

    public function findVisibleWithFilters(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('s')
            ->join('s.user', 'u')
            ->where('s.isVisible = :visible')
            ->setParameter('visible', true)
            ->orderBy('s.score', 'DESC')
            ->addOrderBy('s.createdAt', 'DESC');

        if (!empty($filters['skillId'])) {
            $qb->join('s.studentSkills', 'ss')
               ->andWhere('ss.skill = :skillId')
               ->setParameter('skillId', (int) $filters['skillId']);
        }

        if (!empty($filters['promotionYear'])) {
            $qb->andWhere('s.promotionYear = :promotionYear')
               ->setParameter('promotionYear', (int) $filters['promotionYear']);
        }

        if (!empty($filters['search'])) {
            $qb->andWhere('s.firstName LIKE :search OR s.lastName LIKE :search OR s.bio LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        $offset = ($page - 1) * $limit;

        return $qb->setFirstResult($offset)
                  ->setMaxResults($limit)
                  ->getQuery()
                  ->getResult();
    }

    public function countVisibleWithFilters(array $filters = []): int
    {
        $qb = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.isVisible = :visible')
            ->setParameter('visible', true);

        if (!empty($filters['skillId'])) {
            $qb->join('s.studentSkills', 'ss')
               ->andWhere('ss.skill = :skillId')
               ->setParameter('skillId', (int) $filters['skillId']);
        }

        if (!empty($filters['promotionYear'])) {
            $qb->andWhere('s.promotionYear = :promotionYear')
               ->setParameter('promotionYear', (int) $filters['promotionYear']);
        }

        if (!empty($filters['search'])) {
            $qb->andWhere('s.firstName LIKE :search OR s.lastName LIKE :search OR s.bio LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findOneWithDetails(int $id): ?Student
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.studentSkills', 'ss')->addSelect('ss')
            ->leftJoin('ss.skill', 'sk')->addSelect('sk')
            ->leftJoin('s.projects', 'p')->addSelect('p')
            ->leftJoin('p.projectSkills', 'ps')->addSelect('ps')
            ->leftJoin('ps.skill', 'psk')->addSelect('psk')
            ->leftJoin('s.badges', 'b')->addSelect('b')
            ->where('s.id = :id')
            ->andWhere('s.isVisible = :visible')
            ->setParameter('id', $id)
            ->setParameter('visible', true)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
