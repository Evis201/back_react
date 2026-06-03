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

    private const SCHOOLS = [
        'Hexagone',
        'Epitech',
        '42 Paris',
        'EPITA',
        'ESIEA',
        'Efrei Paris',
        'Supinfo',
        'IPI',
        'Ynov Campus',
        'HETIC',
    ];

    private const DOMAINS = [
        'Développement web',
        'Data Science',
        'Cybersécurité',
        'Cloud & DevOps',
        'Intelligence Artificielle',
        'Jeu vidéo',
        'Mobile & iOS/Android',
        'Systèmes embarqués',
    ];

    private const BIOS = [
        'Passionné de développement web full-stack, je construis des applications robustes avec React et Symfony. Curieux et autodidacte, j\'aime résoudre des problèmes complexes et contribuer à des projets open-source.',
        'Développeuse orientée data, je transforme des ensembles de données brutes en insights actionnables. Maîtrise de Python, Pandas et des outils de visualisation comme Tableau et Power BI.',
        'Ingénieur DevOps avec une passion pour l\'automatisation et les architectures cloud. J\'optimise les pipelines CI/CD et déploie des infrastructures scalables sur AWS et GCP.',
        'Étudiant en cybersécurité, je me spécialise dans les tests d\'intrusion et la sécurité applicative. Certifié CompTIA Security+ et passionné par le CTF.',
        'Développeur mobile iOS & Android, je crée des expériences utilisateur fluides et intuitives. Fan de Flutter et de React Native pour le cross-platform.',
        'Fan d\'intelligence artificielle et de machine learning. Je travaille sur des modèles NLP et de vision par ordinateur avec TensorFlow et PyTorch.',
        'Développeur backend spécialisé dans les microservices et les architectures distribuées. J\'aime créer des APIs REST performantes et bien documentées.',
        'Passionné par le jeu vidéo, je développe avec Unity et Unreal Engine. J\'ai participé à plusieurs game jams et publié un jeu sur itch.io.',
        'Étudiante full-stack avec un fort intérêt pour l\'UX/UI design. Je code autant que je maquette, et j\'adore créer des interfaces accessibles et élégantes.',
        'Ingénieur systèmes embarqués, je programme des microcontrôleurs et des systèmes temps réel en C/C++. Passionné par l\'IoT et la robotique.',
    ];

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

            $firstName = $faker->firstName();
            $lastName  = $faker->lastName();
            $gender    = ($i % 2 === 0) ? 'men' : 'women';
            $portraitN = ($i % 50) + 1;

            $student = new Student();
            $student->setUser($user);
            $student->setFirstName($firstName);
            $student->setLastName($lastName);
            $student->setBio(self::BIOS[$i % count(self::BIOS)]);
            $student->setPromotionYear($faker->numberBetween(2024, 2027));
            $student->setSchool(self::SCHOOLS[$i % count(self::SCHOOLS)]);
            $student->setDomain(self::DOMAINS[$i % count(self::DOMAINS)]);
            $student->setStudyYear($faker->numberBetween(1, 5));
            $student->setGithubUrl('https://github.com/' . $faker->userName());
            $student->setLinkedinUrl('https://linkedin.com/in/' . $faker->userName());
            $student->setAvatarUrl("https://randomuser.me/api/portraits/{$gender}/{$portraitN}.jpg");
            $student->setCvUrl("https://www.w3.org/WAI/WCAG21/Techniques/pdf/pdf-sample.pdf");
            $student->setScore($faker->numberBetween(0, 100));
            $student->setIsVisible(true);
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
