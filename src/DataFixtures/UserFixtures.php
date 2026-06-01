<?php

namespace App\DataFixtures;

use App\Entity\Company;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Staff accounts
        $staff1 = $this->createUser('admin@hexagone.fr', ['ROLE_STAFF'], $manager);
        $staff2 = $this->createUser('moderator@hexagone.fr', ['ROLE_STAFF'], $manager);
        $this->addReference('user_staff_0', $staff1);
        $this->addReference('user_staff_1', $staff2);

        // Company accounts with profiles
        $companies = [
            ['rh@capgemini.fr',   'Capgemini',        'https://www.capgemini.com'],
            ['jobs@soprasteria.fr','Sopra Steria',     'https://www.soprasteria.com'],
            ['recrutement@atos.fr','Atos',             'https://www.atos.net'],
            ['hr@thales.fr',      'Thales Group',      'https://www.thalesgroup.com'],
            ['careers@dassault.fr','Dassault Systèmes','https://www.3ds.com'],
        ];

        foreach ($companies as $i => [$email, $name, $website]) {
            $user = $this->createUser($email, ['ROLE_COMPANY'], $manager);

            $company = new Company();
            $company->setUser($user);
            $company->setName($name);
            $company->setWebsite($website);
            $company->setDescription('Leader mondial du numérique et des services IT.');
            $manager->persist($company);

            $this->addReference('user_company_' . $i, $user);
            $this->addReference('company_' . $i, $company);
        }

        $manager->flush();
    }

    private function createUser(string $email, array $roles, ObjectManager $manager): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setPassword($this->hasher->hashPassword($user, 'password'));
        $user->setIsVerified(true);
        $manager->persist($user);

        return $user;
    }
}
