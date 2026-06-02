<?php

namespace App\DataFixtures;

use App\Entity\Enum\SkillLevel;
use App\Entity\Skill;
use App\Entity\Student;
use App\Entity\StudentSkill;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class StudentFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $skillCount = SkillFixtures::getSkillCount();
        $levels = SkillLevel::cases();

        for ($i = 0; $i < 20; $i++) {
            $user = new User();
            $user->setEmail('student' . $i . '@hexagone.fr');
            $user->setRoles(['ROLE_STUDENT']);
            $user->setPassword($this->hasher->hashPassword($user, 'password'));
            $user->setIsVerified(true);
            $manager->persist($user);

            $student = new Student();
            $student->setUser($user);
            $student->setFirstName($faker->firstName());
            $student->setLastName($faker->lastName());
            $student->setBio($faker->paragraph(3));
            $student->setPromotionYear($faker->numberBetween(2023, 2026));
            $student->setGithubUrl('https://github.com/' . $faker->userName());
            $student->setLinkedinUrl('https://linkedin.com/in/' . $faker->userName());
            $manager->persist($student);

            // Assign 3–6 distinct random skills
            $skillIndices = (array) array_rand(range(0, $skillCount - 1), $faker->numberBetween(3, 6));
            foreach ($skillIndices as $skillIdx) {
                $ss = new StudentSkill();
                $ss->setStudent($student);
                $ss->setSkill($this->getReference(SkillFixtures::getSkillRef($skillIdx), Skill::class));
                $ss->setLevel($faker->randomElement($levels));
                $ss->setYearsOfExperience($faker->numberBetween(0, 4));
                $manager->persist($ss);
            }

            $this->addReference('student_' . $i, $student);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [SkillFixtures::class];
    }
}
