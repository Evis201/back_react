<?php

namespace App\DTO\Student;

use Symfony\Component\Validator\Constraints as Assert;

class StudentCreateDTO
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $firstName = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $lastName = '';

    public ?string $bio = null;

    #[Assert\Url]
    public ?string $avatarUrl = null;

    #[Assert\Url]
    public ?string $githubUrl = null;

    #[Assert\Url]
    public ?string $linkedinUrl = null;

    #[Assert\Range(min: 2000, max: 2100)]
    public ?int $promotionYear = null;

    /**
     * Expected format: [['skillId' => int, 'level' => string, 'yearsOfExperience' => int], ...]
     */
    public array $skills = [];

    /**
     * Expected format: [['title' => string, 'description' => string, 'repoUrl' => string,
     *                    'demoUrl' => string, 'completionYear' => int,
     *                    'skills' => [['skillId' => int, 'isPrimary' => bool], ...]], ...]
     */
    public array $projects = [];
}
