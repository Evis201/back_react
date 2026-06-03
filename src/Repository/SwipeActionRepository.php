<?php

namespace App\Repository;

use App\Entity\Company;
use App\Entity\Student;
use App\Entity\SwipeAction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SwipeAction>
 */
class SwipeActionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SwipeAction::class);
    }

    /** @return SwipeAction[] */
    public function findLikedByCompany(Company $company): array
    {
        return $this->createQueryBuilder('sa')
            ->join('sa.student', 's')
            ->addSelect('s')
            ->where('sa.company = :company')
            ->andWhere('sa.action = :action')
            ->setParameter('company', $company)
            ->setParameter('action', 'like')
            ->orderBy('sa.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return int[] */
    public function findSwipedStudentIds(Company $company): array
    {
        $rows = $this->createQueryBuilder('sa')
            ->select('IDENTITY(sa.student) AS student_id')
            ->where('sa.company = :company')
            ->setParameter('company', $company)
            ->getQuery()
            ->getScalarResult();

        return array_column($rows, 'student_id');
    }

    public function findOneByCompanyAndStudent(Company $company, Student $student): ?SwipeAction
    {
        return $this->findOneBy(['company' => $company, 'student' => $student]);
    }
}
