<?php

namespace App\Service\Auth;

use App\DTO\Auth\RegisterDTO;
use App\Entity\Company;
use App\Entity\Student;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegistrationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly UserRepository $userRepository,
    ) {
    }

    public function register(RegisterDTO $dto): User
    {
        if ($this->userRepository->findByEmail($dto->email)) {
            throw new \DomainException('Email already in use.');
        }

        $user = new User();
        $user->setEmail($dto->email);
        $user->setIsVerified(true);

        if ($dto->role === 'student') {
            $this->validateStudentFields($dto);
            $user->setRoles(['ROLE_STUDENT']);
            $user->setPassword($this->hasher->hashPassword($user, $dto->password));

            $student = new Student();
            $student->setUser($user);
            $student->setFirstName($dto->firstName);
            $student->setLastName($dto->lastName);

            $this->em->persist($student);
        } else {
            $this->validateCompanyFields($dto);
            $user->setRoles(['ROLE_COMPANY']);
            $user->setPassword($this->hasher->hashPassword($user, $dto->password));

            $company = new Company();
            $company->setUser($user);
            $company->setName($dto->companyName);

            $this->em->persist($company);
        }

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function validateStudentFields(RegisterDTO $dto): void
    {
        if (empty($dto->firstName) || empty($dto->lastName)) {
            throw new \InvalidArgumentException('firstName and lastName are required for student registration.');
        }
    }

    private function validateCompanyFields(RegisterDTO $dto): void
    {
        if (empty($dto->companyName)) {
            throw new \InvalidArgumentException('companyName is required for company registration.');
        }
    }
}
