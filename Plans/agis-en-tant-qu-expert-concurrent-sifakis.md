# Plan — Hexagone (got) Talents — Symfony 7 REST API

## Context

Blank git repo. Need a full Symfony 7 backend API for a digital CV platform ("CVthèque") for an IT school. JSON-only, no Twig. Three user roles (student, company, staff), JWT auth, Doctrine ORM, PostgreSQL or MySQL. Phase 1 = structure + entities + migrations + fixtures. Controllers/services come in Phase 2 on user signal.

---

## Phase 1 Scope (This Plan)

1. Bootstrap Symfony 7 project via Composer
2. Write all entities with relations
3. Generate single initial migration
4. Write DataFixtures to seed DB

---

## 1. Bootstrap Commands

```bash
composer create-project symfony/skeleton:"7.2.*" .
composer require doctrine/doctrine-bundle doctrine/doctrine-migrations-bundle doctrine/orm
composer require lexik/jwt-authentication-bundle
composer require symfony/serializer symfony/validator symfony/security-bundle
composer require nelmio/cors-bundle symfony/uid
composer require --dev doctrine/doctrine-fixtures-bundle fakerphp/faker symfony/maker-bundle phpunit/phpunit symfony/phpunit-bridge
```

Generate JWT keypair:
```bash
php bin/console lexik:jwt:generate-keypair
```

---

## 2. File Structure

```
back_react/
├── .env                              # DATABASE_URL, JWT_*, APP_ENV
├── composer.json
├── config/
│   ├── packages/
│   │   ├── doctrine.yaml
│   │   ├── doctrine_migrations.yaml
│   │   ├── lexik_jwt_authentication.yaml
│   │   ├── security.yaml
│   │   └── validator.yaml
│   └── services.yaml
├── migrations/
│   └── Version20260601000001.php     # single initial migration (generated)
├── public/index.php
├── src/
│   ├── Controller/
│   │   ├── AbstractApiController.php
│   │   ├── Auth/AuthController.php
│   │   ├── Student/StudentController.php
│   │   └── Offer/OfferController.php
│   ├── Entity/
│   │   ├── Enum/
│   │   │   ├── SkillLevel.php
│   │   │   ├── OfferType.php
│   │   │   └── OfferStatus.php
│   │   ├── User.php
│   │   ├── Student.php
│   │   ├── Company.php
│   │   ├── Skill.php
│   │   ├── StudentSkill.php
│   │   ├── ProjectSkill.php
│   │   ├── Project.php
│   │   ├── Badge.php
│   │   └── Offer.php
│   ├── Repository/          (one per entity, extends ServiceEntityRepository)
│   ├── Service/
│   │   ├── Auth/RegistrationService.php
│   │   ├── Student/StudentService.php
│   │   ├── Student/StudentFilterService.php
│   │   └── Offer/OfferService.php
│   ├── DTO/
│   │   ├── Auth/RegisterDTO.php
│   │   ├── Student/StudentCreateDTO.php
│   │   ├── Student/StudentUpdateDTO.php
│   │   └── Offer/OfferCreateDTO.php
│   ├── EventListener/JwtCreatedListener.php
│   └── DataFixtures/
│       ├── AppFixtures.php
│       ├── SkillFixtures.php
│       ├── UserFixtures.php
│       ├── StudentFixtures.php
│       ├── ProjectFixtures.php
│       ├── BadgeFixtures.php
│       └── OfferFixtures.php
└── tests/Api/
    ├── AuthTest.php
    ├── StudentTest.php
    └── OfferTest.php
```

---

## 3. Entities & Relations

### DB Tables Generated: 10
`user`, `student`, `company`, `skill`, `student_skill`, `project`, `project_skill`, `badge`, `offer`, `offer_skill`

### Relation Map
```
User (1) ──── (0..1) Student        [OneToOne, cascade persist/remove]
User (1) ──── (0..1) Company        [OneToOne, cascade persist/remove]
Student (1) ── (N) StudentSkill ─── (1) Skill   [join entity with SkillLevel enum]
Student (1) ── (N) Project
Project (1) ── (N) ProjectSkill ─── (1) Skill   [join entity, isPrimary bool]
Student (1) ── (N) Badge
Company (1) ── (N) Offer
Offer (N) ──── (N) Skill            [pure ManyToMany via offer_skill, no extra cols]
```

### User (`src/Entity/User.php`)
| Field | Type | Notes |
|---|---|---|
| id | int | PK auto |
| email | string(180) | unique |
| roles | JSON | `['ROLE_STUDENT'\|'ROLE_COMPANY'\|'ROLE_STAFF']` |
| password | string | bcrypt hashed |
| isVerified | bool | default false |
| createdAt | DateTimeImmutable | |
| studentProfile | OneToOne→Student | nullable, mappedBy |
| companyProfile | OneToOne→Company | nullable, mappedBy |

### Student (`src/Entity/Student.php`)
| Field | Type | Notes |
|---|---|---|
| id | int | PK |
| user | OneToOne→User | owning side, JoinColumn, onDelete CASCADE |
| firstName | string(100) | NotBlank |
| lastName | string(100) | NotBlank |
| bio | text | nullable |
| avatarUrl | string(255) | nullable |
| githubUrl | string(255) | nullable |
| linkedinUrl | string(255) | nullable |
| promotionYear | int | nullable |
| score | int | default 0, aggregated from badges |
| isVisible | bool | default true |
| createdAt | DateTimeImmutable | |
| updatedAt | DateTimeImmutable | nullable |
| studentSkills | OneToMany→StudentSkill | cascade, orphanRemoval |
| projects | OneToMany→Project | cascade, orphanRemoval |
| badges | OneToMany→Badge | cascade, orphanRemoval |

### Company (`src/Entity/Company.php`)
| Field | Type | Notes |
|---|---|---|
| id | int | PK |
| user | OneToOne→User | owning side |
| name | string(255) | NotBlank |
| logoUrl | string(255) | nullable |
| description | text | nullable |
| website | string(255) | nullable |
| createdAt | DateTimeImmutable | |
| offers | OneToMany→Offer | cascade, orphanRemoval |

### Skill (`src/Entity/Skill.php`)
| Field | Type | Notes |
|---|---|---|
| id | int | PK |
| name | string(100) | unique |
| category | string(50) | backend/frontend/devops/database/cloud/soft/methodology/architecture |
| iconUrl | string(255) | nullable |

### StudentSkill (`src/Entity/StudentSkill.php`) — join entity
| Field | Type | Notes |
|---|---|---|
| id | int | PK |
| student | ManyToOne→Student | onDelete CASCADE |
| skill | ManyToOne→Skill | onDelete CASCADE |
| level | string (enumType: SkillLevel) | beginner/intermediate/advanced/expert |
| yearsOfExperience | int | default 0 |
| createdAt | DateTimeImmutable | |
| UniqueConstraint | (student_id, skill_id) | |

### Project (`src/Entity/Project.php`)
| Field | Type | Notes |
|---|---|---|
| id | int | PK |
| student | ManyToOne→Student | onDelete CASCADE |
| title | string(255) | NotBlank |
| description | text | nullable |
| repoUrl | string(255) | nullable |
| demoUrl | string(255) | nullable |
| imageUrls | JSON | nullable, array of CDN URLs |
| isPublic | bool | default true |
| completionYear | smallint | nullable |
| createdAt | DateTimeImmutable | |
| updatedAt | DateTimeImmutable | nullable |
| projectSkills | OneToMany→ProjectSkill | cascade, orphanRemoval |

### ProjectSkill (`src/Entity/ProjectSkill.php`) — join entity
| Field | Type | Notes |
|---|---|---|
| id | int | PK |
| project | ManyToOne→Project | onDelete CASCADE |
| skill | ManyToOne→Skill | onDelete CASCADE |
| isPrimary | bool | default false |
| UniqueConstraint | (project_id, skill_id) | |

### Badge (`src/Entity/Badge.php`)
| Field | Type | Notes |
|---|---|---|
| id | int | PK |
| student | ManyToOne→Student | onDelete CASCADE |
| name | string(100) | NotBlank |
| iconUrl | string(255) | nullable |
| points | int | default 0 |
| description | text | nullable |
| awardedBy | string(100) | nullable — 'staff', 'system', company name |
| awardedAt | DateTimeImmutable | |

### Offer (`src/Entity/Offer.php`)
| Field | Type | Notes |
|---|---|---|
| id | int | PK |
| company | ManyToOne→Company | onDelete CASCADE |
| title | string(255) | NotBlank |
| description | text | NotBlank |
| type | string (enumType: OfferType) | job/internship/alternance |
| status | string (enumType: OfferStatus) | draft/published/closed |
| location | string(255) | nullable |
| isRemote | bool | default false |
| salaryMin | decimal(8,2) | nullable, stored as string in PHP |
| salaryMax | decimal(8,2) | nullable |
| expiresAt | DateImmutable | nullable |
| startsAt | DateImmutable | nullable |
| createdAt | DateTimeImmutable | |
| updatedAt | DateTimeImmutable | nullable |
| requiredSkills | ManyToMany→Skill | JoinTable: offer_skill |

---

## 4. PHP 8.1 Backed Enums

```php
// src/Entity/Enum/SkillLevel.php
enum SkillLevel: string {
    case Beginner     = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced     = 'advanced';
    case Expert       = 'expert';
}

// src/Entity/Enum/OfferType.php
enum OfferType: string {
    case Job        = 'job';
    case Internship = 'internship';
    case Alternance = 'alternance';
}

// src/Entity/Enum/OfferStatus.php
enum OfferStatus: string {
    case Draft     = 'draft';
    case Published = 'published';
    case Closed    = 'closed';
}
```

---

## 5. Migration

Single migration after all entities written:
```bash
php bin/console doctrine:schema:validate
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate --no-interaction
```

`doctrine_migrations.yaml`:
```yaml
doctrine_migrations:
    migrations_paths:
        'App\Migrations': '%kernel.project_dir%/migrations'
    all_or_nothing: true
    check_database_platform: true
```

---

## 6. Fixtures (Load Order via DependentFixtureInterface)

```
SkillFixtures   → 30 canonical skills (PHP, Symfony, React, Docker, etc.)
UserFixtures    → 2 staff users, 5 company users
StudentFixtures → 20 students, each with 3–6 StudentSkills (faker fr_FR)
ProjectFixtures → 2–4 projects per student, each with 1–3 ProjectSkills
BadgeFixtures   → 1–3 badges per student, random points
OfferFixtures   → 10 offers across company users, 2–4 requiredSkills each
```

Load:
```bash
php bin/console doctrine:fixtures:load --no-interaction
```

---

## 7. Key Config Files

### security.yaml (outline)
```yaml
security:
    password_hashers:
        App\Entity\User: { algorithm: auto }
    providers:
        app_user_provider:
            entity: { class: App\Entity\User, property: email }
    firewalls:
        login:
            pattern: ^/api/auth/login
            stateless: true
            json_login:
                check_path: /api/auth/login
                username_path: email
                password_path: password
                success_handler: lexik_jwt_authentication.handler.authentication_success
                failure_handler: lexik_jwt_authentication.handler.authentication_failure
        api:
            pattern: ^/api
            stateless: true
            jwt: ~
    access_control:
        - { path: ^/api/auth,          roles: PUBLIC_ACCESS }
        - { path: ^/api/students$,     roles: PUBLIC_ACCESS,  methods: [GET] }
        - { path: ^/api/students/\d+,  roles: PUBLIC_ACCESS,  methods: [GET] }
        - { path: ^/api/offers,        roles: PUBLIC_ACCESS,  methods: [GET] }
        - { path: ^/api/students,      roles: ROLE_STUDENT,   methods: [POST, PUT, PATCH] }
        - { path: ^/api/offers,        roles: ROLE_COMPANY,   methods: [POST, PUT, PATCH, DELETE] }
        - { path: ^/api/admin,         roles: ROLE_STAFF }
```

### lexik_jwt_authentication.yaml
```yaml
lexik_jwt_authentication:
    secret_key:  '%env(resolve:JWT_SECRET_KEY)%'
    public_key:  '%env(resolve:JWT_PUBLIC_KEY)%'
    pass_phrase: '%env(JWT_PASSPHRASE)%'
    token_ttl:   3600
    token_extractors:
        authorization_header: { enabled: true, prefix: Bearer, name: Authorization }
```

### .env additions
```
DATABASE_URL="mysql://app:!ChangeMe!@127.0.0.1:3306/hexagone_talents?serverVersion=8.0"
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=changeme
```

---

## 8. API Routes (Phase 2 implementation target)

| Method | Path | Role | Description |
|---|---|---|---|
| POST | /api/auth/register | PUBLIC | Create user account |
| POST | /api/auth/login | PUBLIC | Get JWT token |
| POST | /api/students | ROLE_STUDENT | Create own student profile |
| GET | /api/students | PUBLIC | List profiles (filters: skill, promotionYear, isVisible) |
| GET | /api/students/{id} | PUBLIC | Profile detail (full CV JSON) |
| PUT/PATCH | /api/students/{id} | ROLE_STUDENT | Update own profile |
| POST | /api/offers | ROLE_COMPANY | Create offer |
| GET | /api/offers | PUBLIC | List offers (filters: type, status, skill) |
| GET | /api/offers/{id} | PUBLIC | Offer detail |

---

## 9. Verification

After Phase 1 is complete:
```bash
# Schema valid
php bin/console doctrine:schema:validate

# Migration runs clean
php bin/console doctrine:migrations:migrate --no-interaction

# Fixtures load without FK violations
php bin/console doctrine:fixtures:load --no-interaction

# Spot-check seeded data
php bin/console dbal:run-sql "SELECT COUNT(*) FROM student"
php bin/console dbal:run-sql "SELECT name, category FROM skill LIMIT 10"
php bin/console dbal:run-sql "SELECT s.first_name, sk.name, ss.level FROM student_skill ss JOIN student s ON ss.student_id = s.id JOIN skill sk ON ss.skill_id = sk.id LIMIT 5"
```

---

## Non-Obvious Decisions

| Decision | Choice | Reason |
|---|---|---|
| User ↔ Student | OneToOne separate tables | Company users also exist; keeps auth entity clean |
| SkillLevel on relation | Full `StudentSkill` entity | Doctrine ManyToMany can't carry extra columns |
| Offer ↔ Skill | Pure ManyToMany (`offer_skill`) | No extra data needed on this relation |
| Badge | ManyToOne Student, no catalog | YAGNI — catalog is a trivial refactor later |
| Enums | PHP 8.1 backed + Doctrine `enumType` | Native Doctrine 3 support, no custom type class |
| DECIMAL salary | `string` in PHP | Avoids float precision loss |
| Fixture locale | `fr_FR` | School is French |
| Single migration | Yes | Greenfield — one coherent snapshot easier to review |
