<?php

namespace App\DataFixtures;

use App\Entity\Project;
use App\Entity\ProjectSkill;
use App\Entity\Skill;
use App\Entity\Student;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ProjectFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $skillCount = SkillFixtures::getSkillCount();

        for ($i = 0; $i < 20; $i++) {
            $student = $this->getReference('student_' . $i, Student::class);
            $nbProjects = $faker->numberBetween(2, 4);

            for ($j = 0; $j < $nbProjects; $j++) {
                $project = new Project();
                $project->setStudent($student);
                $project->setTitle($faker->sentence(4, false));
                $project->setDescription($faker->paragraph(4));
                $project->setRepoUrl('https://github.com/' . $faker->userName() . '/' . $faker->slug(3));
                $project->setCompletionYear($faker->numberBetween(2022, 2026));
                $project->setIsPublic(true);
                $manager->persist($project);

                // 1–3 distinct skills for this project
                $skillIndices = (array) array_rand(range(0, $skillCount - 1), $faker->numberBetween(1, 3));
                foreach ($skillIndices as $k => $skillIdx) {
                    $ps = new ProjectSkill();
                    $ps->setProject($project);
                    $ps->setSkill($this->getReference(SkillFixtures::getSkillRef($skillIdx), Skill::class));
                    $ps->setIsPrimary($k === 0);
                    $manager->persist($ps);
                }
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [StudentFixtures::class, SkillFixtures::class];
    }
}
