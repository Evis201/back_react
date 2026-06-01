<?php

namespace App\Entity;

use App\Repository\SkillRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SkillRepository::class)]
#[ORM\Table(name: 'skill')]
#[ORM\UniqueConstraint(name: 'UNIQ_SKILL_NAME', columns: ['name'])]
class Skill
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    #[Assert\NotBlank]
    private string $name;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank]
    private string $category;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $iconUrl = null;

    #[ORM\OneToMany(mappedBy: 'skill', targetEntity: StudentSkill::class)]
    private Collection $studentSkills;

    #[ORM\OneToMany(mappedBy: 'skill', targetEntity: ProjectSkill::class)]
    private Collection $projectSkills;

    public function __construct()
    {
        $this->studentSkills = new ArrayCollection();
        $this->projectSkills = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getIconUrl(): ?string
    {
        return $this->iconUrl;
    }

    public function setIconUrl(?string $iconUrl): static
    {
        $this->iconUrl = $iconUrl;

        return $this;
    }

    /** @return Collection<int, StudentSkill> */
    public function getStudentSkills(): Collection
    {
        return $this->studentSkills;
    }

    /** @return Collection<int, ProjectSkill> */
    public function getProjectSkills(): Collection
    {
        return $this->projectSkills;
    }
}
