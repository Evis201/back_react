<?php

namespace App\DTO\Offer;

use Symfony\Component\Validator\Constraints as Assert;

class OfferCreateDTO
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $title = '';

    #[Assert\NotBlank]
    public string $description = '';

    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['job', 'internship', 'alternance'])]
    public string $type = '';

    #[Assert\Choice(choices: ['draft', 'published', 'closed'])]
    public string $status = 'published';

    public ?string $location = null;

    public bool $isRemote = false;

    #[Assert\PositiveOrZero]
    public ?float $salaryMin = null;

    #[Assert\PositiveOrZero]
    public ?float $salaryMax = null;

    public ?string $startsAt = null;

    public ?string $expiresAt = null;

    /**
     * Array of skill IDs: [int, int, ...]
     */
    public array $skillIds = [];
}
