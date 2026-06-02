<?php

namespace App\Service\Student;

use App\DTO\Student\StudentCreateDTO;
use App\DTO\Student\StudentUpdateDTO;
use App\Entity\Enum\SkillLevel;
use App\Entity\Project;
use App\Entity\ProjectSkill;
use App\Entity\Skill;
use App\Entity\Student;
use App\Entity\StudentSkill;
use App\Entity\User;
use App\Repository\SkillRepository;
use App\Repository\StudentRepository;
use Doctrine\ORM\EntityManagerInterface;

class StudentService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly StudentRepository $studentRepository,
        private readonly SkillRepository $skillRepository,
    ) {
    }

    public function list(array $queryParams): array
    {
        $page  = max(1, (int) ($queryParams['page']  ?? 1));
        $limit = min(50, max(1, (int) ($queryParams['limit'] ?? 20)));

        $filters = array_filter([
            'skillId'       => $queryParams['skillId']       ?? null,
            'promotionYear' => $queryParams['promotionYear'] ?? null,
            'search'        => $queryParams['search']        ?? null,
        ]);

        $students = $this->studentRepository->findVisibleWithFilters($filters, $page, $limit);
        $total    = $this->studentRepository->countVisibleWithFilters($filters);

        return [
            'items' => array_map($this->normalizeListItem(...), $students),
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ];
    }

    public function getDetail(int $id): ?array
    {
        $student = $this->studentRepository->findOneWithDetails($id);
        if ($student === null) {
            return null;
        }

        return $this->normalizeDetail($student);
    }

    public function create(User $user, StudentCreateDTO $dto): Student
    {
        if ($user->getStudentProfile() !== null) {
            throw new \DomainException('Student profile already exists. Use PUT/PATCH to update it.');
        }

        $student = new Student();
        $student->setUser($user);
        $this->applyCreateData($student, $dto);

        $this->em->persist($student);
        $this->em->flush();

        return $student;
    }

    public function update(Student $student, StudentUpdateDTO $dto): Student
    {
        if ($dto->firstName !== null) {
            $student->setFirstName($dto->firstName);
        }
        if ($dto->lastName !== null) {
            $student->setLastName($dto->lastName);
        }
        if ($dto->bio !== null) {
            $student->setBio($dto->bio);
        }
        if ($dto->avatarUrl !== null) {
            $student->setAvatarUrl($dto->avatarUrl);
        }
        if ($dto->githubUrl !== null) {
            $student->setGithubUrl($dto->githubUrl);
        }
        if ($dto->linkedinUrl !== null) {
            $student->setLinkedinUrl($dto->linkedinUrl);
        }
        if ($dto->promotionYear !== null) {
            $student->setPromotionYear($dto->promotionYear);
        }
        if ($dto->school !== null) {
            $student->setSchool($dto->school);
        }
        if ($dto->domain !== null) {
            $student->setDomain($dto->domain);
        }
        if ($dto->studyYear !== null) {
            $student->setStudyYear($dto->studyYear);
        }
        if ($dto->cvUrl !== null) {
            $student->setCvUrl($dto->cvUrl);
        }
        if ($dto->isVisible !== null) {
            $student->setIsVisible($dto->isVisible);
        }

        if ($dto->skills !== null) {
            $this->replaceSkills($student, $dto->skills);
        }

        if ($dto->projects !== null) {
            $this->replaceProjects($student, $dto->projects);
        }

        $this->em->flush();

        return $student;
    }

    // ── Normalizers ──────────────────────────────────────────────────────────

    public function normalizeListItem(Student $student): array
    {
        return [
            'id'            => $student->getId(),
            'firstName'     => $student->getFirstName(),
            'lastName'      => $student->getLastName(),
            'promotionYear' => $student->getPromotionYear(),
            'school'        => $student->getSchool(),
            'domain'        => $student->getDomain(),
            'studyYear'     => $student->getStudyYear(),
            'score'         => $student->getScore(),
            'avatarUrl'     => $student->getAvatarUrl(),
            'skills'        => $student->getStudentSkills()
                ->map(fn (StudentSkill $ss) => [
                    'name'     => $ss->getSkill()->getName(),
                    'category' => $ss->getSkill()->getCategory(),
                    'level'    => $ss->getLevel()->value,
                ])
                ->toArray(),
            'badges' => $student->getBadges()
                ->map(fn ($b) => ['name' => $b->getName(), 'points' => $b->getPoints()])
                ->toArray(),
        ];
    }

    public function normalizeDetail(Student $student): array
    {
        return [
            'id'            => $student->getId(),
            'email'         => $student->getUser()->getEmail(),
            'firstName'     => $student->getFirstName(),
            'lastName'      => $student->getLastName(),
            'bio'           => $student->getBio(),
            'avatarUrl'     => $student->getAvatarUrl(),
            'githubUrl'     => $student->getGithubUrl(),
            'linkedinUrl'   => $student->getLinkedinUrl(),
            'promotionYear' => $student->getPromotionYear(),
            'school'        => $student->getSchool(),
            'domain'        => $student->getDomain(),
            'studyYear'     => $student->getStudyYear(),
            'cvUrl'         => $student->getCvUrl(),
            'score'         => $student->getScore(),
            'isVisible'     => $student->isVisible(),
            'createdAt'     => $student->getCreatedAt()->format('Y-m-d\TH:i:s\Z'),
            'updatedAt'     => $student->getUpdatedAt()?->format('Y-m-d\TH:i:s\Z'),
            'skills' => $student->getStudentSkills()
                ->map(fn (StudentSkill $ss) => [
                    'id'                => $ss->getId(),
                    'name'              => $ss->getSkill()->getName(),
                    'category'          => $ss->getSkill()->getCategory(),
                    'level'             => $ss->getLevel()->value,
                    'yearsOfExperience' => $ss->getYearsOfExperience(),
                ])
                ->toArray(),
            'projects' => $student->getProjects()
                ->map(fn (Project $p) => [
                    'id'             => $p->getId(),
                    'title'          => $p->getTitle(),
                    'description'    => $p->getDescription(),
                    'repoUrl'        => $p->getRepoUrl(),
                    'demoUrl'        => $p->getDemoUrl(),
                    'imageUrls'      => $p->getImageUrls() ?? [],
                    'completionYear' => $p->getCompletionYear(),
                    'isPublic'       => $p->isPublic(),
                    'skills' => $p->getProjectSkills()
                        ->map(fn (ProjectSkill $ps) => [
                            'name'      => $ps->getSkill()->getName(),
                            'isPrimary' => $ps->isPrimary(),
                        ])
                        ->toArray(),
                ])
                ->toArray(),
            'badges' => $student->getBadges()
                ->map(fn ($b) => [
                    'id'          => $b->getId(),
                    'name'        => $b->getName(),
                    'points'      => $b->getPoints(),
                    'description' => $b->getDescription(),
                    'awardedBy'   => $b->getAwardedBy(),
                    'awardedAt'   => $b->getAwardedAt()->format('Y-m-d'),
                ])
                ->toArray(),
        ];
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function applyCreateData(Student $student, StudentCreateDTO $dto): void
    {
        $student->setFirstName($dto->firstName);
        $student->setLastName($dto->lastName);

        if ($dto->bio !== null) {
            $student->setBio($dto->bio);
        }
        if ($dto->avatarUrl !== null) {
            $student->setAvatarUrl($dto->avatarUrl);
        }
        if ($dto->githubUrl !== null) {
            $student->setGithubUrl($dto->githubUrl);
        }
        if ($dto->linkedinUrl !== null) {
            $student->setLinkedinUrl($dto->linkedinUrl);
        }
        if ($dto->promotionYear !== null) {
            $student->setPromotionYear($dto->promotionYear);
        }
        if ($dto->school !== null) {
            $student->setSchool($dto->school);
        }
        if ($dto->domain !== null) {
            $student->setDomain($dto->domain);
        }
        if ($dto->studyYear !== null) {
            $student->setStudyYear($dto->studyYear);
        }
        if ($dto->cvUrl !== null) {
            $student->setCvUrl($dto->cvUrl);
        }

        $this->em->persist($student);

        $this->replaceSkills($student, $dto->skills);
        $this->replaceProjects($student, $dto->projects);
    }

    private function replaceSkills(Student $student, array $skillsData): void
    {
        foreach ($student->getStudentSkills()->toArray() as $existing) {
            $this->em->remove($existing);
        }

        $skillMap = $this->buildSkillMap(array_column($skillsData, 'skillId'));

        foreach ($skillsData as $item) {
            $skillId = (int) ($item['skillId'] ?? 0);
            if (!isset($skillMap[$skillId])) {
                continue;
            }

            $level = SkillLevel::tryFrom($item['level'] ?? '') ?? SkillLevel::Beginner;

            $ss = new StudentSkill();
            $ss->setStudent($student);
            $ss->setSkill($skillMap[$skillId]);
            $ss->setLevel($level);
            $ss->setYearsOfExperience((int) ($item['yearsOfExperience'] ?? 0));
            $this->em->persist($ss);
        }
    }

    private function replaceProjects(Student $student, array $projectsData): void
    {
        foreach ($student->getProjects()->toArray() as $existing) {
            $this->em->remove($existing);
        }

        foreach ($projectsData as $item) {
            if (empty($item['title'])) {
                continue;
            }

            $project = new Project();
            $project->setStudent($student);
            $project->setTitle($item['title']);
            $project->setDescription($item['description'] ?? null);
            $project->setRepoUrl($item['repoUrl'] ?? null);
            $project->setDemoUrl($item['demoUrl'] ?? null);
            $project->setCompletionYear(isset($item['completionYear']) ? (int) $item['completionYear'] : null);
            $project->setIsPublic((bool) ($item['isPublic'] ?? true));
            $this->em->persist($project);

            $projectSkillIds = array_column($item['skills'] ?? [], 'skillId');
            $projectSkillMap = $this->buildSkillMap($projectSkillIds);

            foreach ($item['skills'] ?? [] as $k => $ps) {
                $skillId = (int) ($ps['skillId'] ?? 0);
                if (!isset($projectSkillMap[$skillId])) {
                    continue;
                }

                $projectSkill = new ProjectSkill();
                $projectSkill->setProject($project);
                $projectSkill->setSkill($projectSkillMap[$skillId]);
                $projectSkill->setIsPrimary((bool) ($ps['isPrimary'] ?? ($k === 0)));
                $this->em->persist($projectSkill);
            }
        }
    }

    /** @return array<int, Skill> */
    private function buildSkillMap(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $skills = $this->skillRepository->findBy(['id' => array_unique(array_filter($ids))]);
        $map    = [];
        foreach ($skills as $skill) {
            $map[$skill->getId()] = $skill;
        }

        return $map;
    }
}
