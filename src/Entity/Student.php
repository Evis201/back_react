<?php

namespace App\Entity;

use App\Repository\StudentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: StudentRepository::class)]
#[ORM\Table(name: 'student')]
#[ORM\HasLifecycleCallbacks]
class Student
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'studentProfile', targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $firstName;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $lastName;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Url]
    private ?string $avatarUrl = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Url]
    private ?string $githubUrl = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Url]
    private ?string $linkedinUrl = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Range(min: 2000, max: 2100)]
    private ?int $promotionYear = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $school = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private ?string $domain = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    #[Assert\Range(min: 1, max: 5)]
    private ?int $studyYear = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Url]
    private ?string $cvUrl = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $score = 0;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isVisible = true;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'student', targetEntity: StudentSkill::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $studentSkills;

    #[ORM\OneToMany(mappedBy: 'student', targetEntity: Project::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $projects;

    #[ORM\OneToMany(mappedBy: 'student', targetEntity: Badge::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $badges;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->studentSkills = new ArrayCollection();
        $this->projects = new ArrayCollection();
        $this->badges = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;

        return $this;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(?string $avatarUrl): static
    {
        $this->avatarUrl = $avatarUrl;

        return $this;
    }

    public function getGithubUrl(): ?string
    {
        return $this->githubUrl;
    }

    public function setGithubUrl(?string $githubUrl): static
    {
        $this->githubUrl = $githubUrl;

        return $this;
    }

    public function getLinkedinUrl(): ?string
    {
        return $this->linkedinUrl;
    }

    public function setLinkedinUrl(?string $linkedinUrl): static
    {
        $this->linkedinUrl = $linkedinUrl;

        return $this;
    }

    public function getPromotionYear(): ?int
    {
        return $this->promotionYear;
    }

    public function setPromotionYear(?int $promotionYear): static
    {
        $this->promotionYear = $promotionYear;

        return $this;
    }

    public function getSchool(): ?string
    {
        return $this->school;
    }

    public function setSchool(?string $school): static
    {
        $this->school = $school;

        return $this;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function setDomain(?string $domain): static
    {
        $this->domain = $domain;

        return $this;
    }

    public function getStudyYear(): ?int
    {
        return $this->studyYear;
    }

    public function setStudyYear(?int $studyYear): static
    {
        $this->studyYear = $studyYear;

        return $this;
    }

    public function getCvUrl(): ?string
    {
        return $this->cvUrl;
    }

    public function setCvUrl(?string $cvUrl): static
    {
        $this->cvUrl = $cvUrl;

        return $this;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function setScore(int $score): static
    {
        $this->score = $score;

        return $this;
    }

    public function isVisible(): bool
    {
        return $this->isVisible;
    }

    public function setIsVisible(bool $isVisible): static
    {
        $this->isVisible = $isVisible;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /** @return Collection<int, StudentSkill> */
    public function getStudentSkills(): Collection
    {
        return $this->studentSkills;
    }

    public function addStudentSkill(StudentSkill $studentSkill): static
    {
        if (!$this->studentSkills->contains($studentSkill)) {
            $this->studentSkills->add($studentSkill);
            $studentSkill->setStudent($this);
        }

        return $this;
    }

    public function removeStudentSkill(StudentSkill $studentSkill): static
    {
        $this->studentSkills->removeElement($studentSkill);

        return $this;
    }

    /** @return Collection<int, Project> */
    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function addProject(Project $project): static
    {
        if (!$this->projects->contains($project)) {
            $this->projects->add($project);
            $project->setStudent($this);
        }

        return $this;
    }

    public function removeProject(Project $project): static
    {
        $this->projects->removeElement($project);

        return $this;
    }

    /** @return Collection<int, Badge> */
    public function getBadges(): Collection
    {
        return $this->badges;
    }

    public function addBadge(Badge $badge): static
    {
        if (!$this->badges->contains($badge)) {
            $this->badges->add($badge);
            $badge->setStudent($this);
        }

        return $this;
    }

    public function removeBadge(Badge $badge): static
    {
        $this->badges->removeElement($badge);

        return $this;
    }
}
