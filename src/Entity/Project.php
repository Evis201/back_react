<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\Table(name: 'project')]
#[ORM\HasLifecycleCallbacks]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Student::class, inversedBy: 'projects')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Student $student;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Url]
    private ?string $repoUrl = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Url]
    private ?string $demoUrl = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $imageUrls = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isPublic = true;

    #[ORM\Column(type: 'smallint', nullable: true)]
    #[Assert\Range(min: 2000, max: 2100)]
    private ?int $completionYear = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'project', targetEntity: ProjectSkill::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $projectSkills;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->projectSkills = new ArrayCollection();
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

    public function getStudent(): Student
    {
        return $this->student;
    }

    public function setStudent(Student $student): static
    {
        $this->student = $student;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getRepoUrl(): ?string
    {
        return $this->repoUrl;
    }

    public function setRepoUrl(?string $repoUrl): static
    {
        $this->repoUrl = $repoUrl;

        return $this;
    }

    public function getDemoUrl(): ?string
    {
        return $this->demoUrl;
    }

    public function setDemoUrl(?string $demoUrl): static
    {
        $this->demoUrl = $demoUrl;

        return $this;
    }

    public function getImageUrls(): ?array
    {
        return $this->imageUrls;
    }

    public function setImageUrls(?array $imageUrls): static
    {
        $this->imageUrls = $imageUrls;

        return $this;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): static
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    public function getCompletionYear(): ?int
    {
        return $this->completionYear;
    }

    public function setCompletionYear(?int $completionYear): static
    {
        $this->completionYear = $completionYear;

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

    /** @return Collection<int, ProjectSkill> */
    public function getProjectSkills(): Collection
    {
        return $this->projectSkills;
    }

    public function addProjectSkill(ProjectSkill $projectSkill): static
    {
        if (!$this->projectSkills->contains($projectSkill)) {
            $this->projectSkills->add($projectSkill);
            $projectSkill->setProject($this);
        }

        return $this;
    }

    public function removeProjectSkill(ProjectSkill $projectSkill): static
    {
        $this->projectSkills->removeElement($projectSkill);

        return $this;
    }
}
