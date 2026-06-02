<?php

namespace App\DataFixtures;

use App\Entity\Company;
use App\Entity\Enum\OfferStatus;
use App\Entity\Enum\OfferType;
use App\Entity\Offer;
use App\Entity\Skill;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class OfferFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $skillCount = SkillFixtures::getSkillCount();
        $types = OfferType::cases();

        for ($i = 0; $i < 10; $i++) {
            $companyRef = $this->getReference('company_' . ($i % 5), Company::class);

            $offer = new Offer();
            $offer->setCompany($companyRef);
            $offer->setTitle($faker->jobTitle() . ' ' . $faker->randomElement(['Junior', 'Senior', 'Confirmé']));
            $offer->setDescription($faker->paragraphs(3, true));
            $offer->setType($faker->randomElement($types));
            $offer->setStatus(OfferStatus::Published);
            $offer->setLocation($faker->city() . ', France');
            $offer->setIsRemote($faker->boolean(30));
            $offer->setSalaryMin((string) $faker->numberBetween(25000, 40000));
            $offer->setSalaryMax((string) $faker->numberBetween(45000, 70000));
            $offer->setStartsAt(new \DateTimeImmutable('+' . $faker->numberBetween(15, 90) . ' days'));
            $offer->setExpiresAt(new \DateTimeImmutable('+' . $faker->numberBetween(30, 120) . ' days'));
            $manager->persist($offer);

            // 2–4 required skills
            $skillIndices = (array) array_rand(range(0, $skillCount - 1), $faker->numberBetween(2, 4));
            foreach ($skillIndices as $skillIdx) {
                $offer->addRequiredSkill($this->getReference(SkillFixtures::getSkillRef($skillIdx), Skill::class));
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class, SkillFixtures::class];
    }
}
