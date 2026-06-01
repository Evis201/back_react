<?php

namespace App\DTO\Auth;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterDTO
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 8, minMessage: 'Password must be at least 8 characters.')]
    public string $password = '';

    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['student', 'company'], message: 'Role must be "student" or "company".')]
    public string $role = '';

    // Required when role = student
    public ?string $firstName = null;
    public ?string $lastName = null;

    // Required when role = company
    public ?string $companyName = null;
}
