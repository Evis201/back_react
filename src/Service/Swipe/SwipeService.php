<?php

namespace App\Service\Swipe;

use App\Entity\Company;
use App\Entity\StudentSkill;
use App\Entity\SwipeAction;
use App\Repository\StudentRepository;
use App\Repository\SwipeActionRepository;
use Doctrine\ORM\EntityManagerInterface;

class SwipeService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SwipeActionRepository $swipeRepo,
        private readonly StudentRepository $studentRepo,
    ) {
    }

    public function swipe(Company $company, int $studentId, string $action): array
    {
        if (!in_array($action, ['like', 'pass'], true)) {
            throw new \InvalidArgumentException('Action must be "like" or "pass".');
        }

        $student = $this->studentRepo->find($studentId);
        if ($student === null) {
            throw new \DomainException('Student not found.');
        }

        $existing = $this->swipeRepo->findOneByCompanyAndStudent($company, $student);

        if ($existing !== null) {
            $existing->setAction($action);
        } else {
            $existing = new SwipeAction();
            $existing->setCompany($company);
            $existing->setStudent($student);
            $existing->setAction($action);
            $this->em->persist($existing);
        }

        $this->em->flush();

        return ['studentId' => $studentId, 'action' => $action];
    }

    public function getLiked(Company $company): array
    {
        $actions = $this->swipeRepo->findLikedByCompany($company);

        return array_map(fn (SwipeAction $a) => $this->normalizeForCard($a->getStudent()), $actions);
    }

    public function unlike(Company $company, int $studentId): void
    {
        $student = $this->studentRepo->find($studentId);
        if ($student === null) {
            throw new \DomainException('Student not found.');
        }

        $action = $this->swipeRepo->findOneByCompanyAndStudent($company, $student);
        if ($action !== null) {
            $this->em->remove($action);
            $this->em->flush();
        }
    }

    public function getNextBatch(Company $company, int $limit = 10): array
    {
        $excludeIds = $this->swipeRepo->findSwipedStudentIds($company);
        $students   = $this->studentRepo->findVisibleExcludingIds($excludeIds, $limit);

        return array_map($this->normalizeForCard(...), $students);
    }

    // ── Normalizer ────────────────────────────────────────────────────────────

    private function normalizeForCard(\App\Entity\Student $student): array
    {
        return [
            'id'            => $student->getId(),
            'firstName'     => $student->getFirstName(),
            'lastName'      => $student->getLastName(),
            'bio'           => $student->getBio(),
            'avatarUrl'     => $student->getAvatarUrl(),
            'githubUrl'     => $student->getGithubUrl(),
            'linkedinUrl'   => $student->getLinkedinUrl(),
            'cvUrl'         => $student->getCvUrl(),
            'school'        => $student->getSchool(),
            'domain'        => $student->getDomain(),
            'promotionYear' => $student->getPromotionYear(),
            'studyYear'     => $student->getStudyYear(),
            'score'         => $student->getScore(),
            'skills'        => $student->getStudentSkills()
                ->map(fn (StudentSkill $ss) => [
                    'name'     => $ss->getSkill()->getName(),
                    'category' => $ss->getSkill()->getCategory(),
                    'level'    => $ss->getLevel()->value,
                ])
                ->toArray(),
        ];
    }
}
