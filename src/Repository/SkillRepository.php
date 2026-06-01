<?php

namespace App\Repository;

use App\Entity\Skill;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Skill>
 */
class SkillRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Skill::class);
    }

    /** @return Skill[] */
    public function findByCategory(string $category): array
    {
        return $this->findBy(['category' => $category], ['name' => 'ASC']);
    }

    /** @return Skill[] */
    public function findAllIndexedByName(): array
    {
        $skills = $this->findAll();
        $indexed = [];
        foreach ($skills as $skill) {
            $indexed[$skill->getName()] = $skill;
        }

        return $indexed;
    }
}
