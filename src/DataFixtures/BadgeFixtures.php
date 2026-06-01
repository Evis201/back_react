<?php

namespace App\DataFixtures;

use App\Entity\Badge;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class BadgeFixtures extends Fixture implements DependentFixtureInterface
{
    private const BADGE_TEMPLATES = [
        ['Meilleur Projet',    'staff',  50],
        ['Expert PHP',         'system', 30],
        ['Expert React',       'system', 30],
        ['Contributeur OSS',   'system', 20],
        ['Hackathon Winner',   'staff',  40],
        ['Leadership',         'staff',  25],
        ['Top Promotion',      'staff',  35],
    ];

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        for ($i = 0; $i < 20; $i++) {
            $student = $this->getReference('student_' . $i);
            $nbBadges = $faker->numberBetween(1, 3);
            $templates = $faker->randomElements(self::BADGE_TEMPLATES, $nbBadges, false);
            $totalPoints = 0;

            foreach ($templates as [$name, $awardedBy, $points]) {
                $badge = new Badge();
                $badge->setStudent($student);
                $badge->setName($name);
                $badge->setPoints($points);
                $badge->setAwardedBy($awardedBy);
                $badge->setDescription($faker->sentence());
                $manager->persist($badge);
                $totalPoints += $points;
            }

            $student->setScore($student->getScore() + $totalPoints);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [StudentFixtures::class];
    }
}
