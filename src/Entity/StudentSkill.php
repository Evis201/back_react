<?php

namespace App\Entity;

use App\Entity\Enum\SkillLevel;
use App\Repository\StudentSkillRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: StudentSkillRepository::class)]
#[ORM\Table(name: 'student_skill')]
#[ORM\UniqueConstraint(name: 'UNIQ_STUDENT_SKILL', columns: ['student_id', 'skill_id'])]
class StudentSkill
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Student::class, inversedBy: 'studentSkills')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Student $student;

    #[ORM\ManyToOne(targetEntity: Skill::class, inversedBy: 'studentSkills')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Skill $skill;

    #[ORM\Column(type: 'string', length: 20, enumType: SkillLevel::class)]
    #[Assert\NotNull]
    private SkillLevel $level;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\PositiveOrZero]
    private int $yearsOfExperience = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStudent(): Student
    {
        return $this->student;
    }

    public function setStudent(Student $student): static
    {
        $this->student = $student;

        return $this;
    }

    public function getSkill(): Skill
    {
        return $this->skill;
    }

    public function setSkill(Skill $skill): static
    {
        $this->skill = $skill;

        return $this;
    }

    public function getLevel(): SkillLevel
    {
        return $this->level;
    }

    public function setLevel(SkillLevel $level): static
    {
        $this->level = $level;

        return $this;
    }

    public function getYearsOfExperience(): int
    {
        return $this->yearsOfExperience;
    }

    public function setYearsOfExperience(int $yearsOfExperience): static
    {
        $this->yearsOfExperience = $yearsOfExperience;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
