<?php

namespace App\DTO\Student;

use Symfony\Component\Validator\Constraints as Assert;

class StudentUpdateDTO
{
    #[Assert\Length(max: 100)]
    public ?string $firstName = null;

    #[Assert\Length(max: 100)]
    public ?string $lastName = null;

    public ?string $bio = null;

    #[Assert\Url]
    public ?string $avatarUrl = null;

    #[Assert\Url]
    public ?string $githubUrl = null;

    #[Assert\Url]
    public ?string $linkedinUrl = null;

    #[Assert\Range(min: 2000, max: 2100)]
    public ?int $promotionYear = null;

    public ?bool $isVisible = null;

    /**
     * If provided, replaces all existing skills.
     * Format: [['skillId' => int, 'level' => string, 'yearsOfExperience' => int], ...]
     */
    public ?array $skills = null;

    /**
     * If provided, replaces all existing projects.
     * Format: [['id' => ?int, 'title' => string, ...], ...]
     */
    public ?array $projects = null;
}
