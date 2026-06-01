<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Orchestrator — actual data loaded by dependency chain.
        // Load order: SkillFixtures → UserFixtures → StudentFixtures
        //             → ProjectFixtures, BadgeFixtures → OfferFixtures
    }

    public function getDependencies(): array
    {
        return [
            ProjectFixtures::class,
            BadgeFixtures::class,
            OfferFixtures::class,
        ];
    }
}
