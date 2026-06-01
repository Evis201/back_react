<?php

namespace App\DataFixtures;

use App\Entity\Skill;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SkillFixtures extends Fixture
{
    private const SKILLS = [
        ['PHP',           'backend'],
        ['Symfony',       'backend'],
        ['Laravel',       'backend'],
        ['Python',        'backend'],
        ['Java',          'backend'],
        ['Node.js',       'backend'],
        ['Go',            'backend'],
        ['React',         'frontend'],
        ['Vue.js',        'frontend'],
        ['Angular',       'frontend'],
        ['TypeScript',    'frontend'],
        ['HTML/CSS',      'frontend'],
        ['Tailwind CSS',  'frontend'],
        ['Docker',        'devops'],
        ['Kubernetes',    'devops'],
        ['CI/CD',         'devops'],
        ['Git',           'devops'],
        ['Linux',         'devops'],
        ['MySQL',         'database'],
        ['PostgreSQL',    'database'],
        ['MongoDB',       'database'],
        ['Redis',         'database'],
        ['REST API',      'architecture'],
        ['GraphQL',       'architecture'],
        ['Microservices', 'architecture'],
        ['TDD',           'methodology'],
        ['Agile/Scrum',   'methodology'],
        ['AWS',           'cloud'],
        ['GCP',           'cloud'],
        ['Communication', 'soft'],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::SKILLS as $index => [$name, $category]) {
            $skill = new Skill();
            $skill->setName($name);
            $skill->setCategory($category);
            $manager->persist($skill);
            $this->addReference(self::getSkillRef($index), $skill);
        }

        $manager->flush();
    }

    public static function getSkillRef(int $index): string
    {
        return 'skill_' . $index;
    }

    public static function getSkillCount(): int
    {
        return count(self::SKILLS);
    }
}
