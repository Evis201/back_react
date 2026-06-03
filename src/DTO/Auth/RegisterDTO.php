<?php

namespace App\DTO\Auth;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterDTO
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 16, minMessage: 'Le mot de passe doit contenir au moins 16 caractères (norme ANSSI).')]
    #[Assert\Regex(
        pattern: '/[A-Z]/',
        message: 'Le mot de passe doit contenir au moins une lettre majuscule.'
    )]
    #[Assert\Regex(
        pattern: '/[a-z]/',
        message: 'Le mot de passe doit contenir au moins une lettre minuscule.'
    )]
    #[Assert\Regex(
        pattern: '/[0-9]/',
        message: 'Le mot de passe doit contenir au moins un chiffre.'
    )]
    #[Assert\Regex(
        pattern: '/[\W_]/',
        message: 'Le mot de passe doit contenir au moins un caractère spécial.'
    )]
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
