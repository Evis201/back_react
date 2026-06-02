-- PostgreSQL setup script
-- Usage: psql -U app -d app -f setup.sql

CREATE TABLE IF NOT EXISTS app_user (
    id          SERIAL PRIMARY KEY,
    email       VARCHAR(180) NOT NULL,
    roles       JSON        NOT NULL DEFAULT '[]',
    password    VARCHAR(255) NOT NULL,
    is_verified BOOLEAN     NOT NULL DEFAULT FALSE,
    created_at  TIMESTAMP   NOT NULL,
    CONSTRAINT UNIQ_USER_EMAIL UNIQUE (email)
);

CREATE TABLE IF NOT EXISTS company (
    id          SERIAL PRIMARY KEY,
    user_id     INTEGER     NOT NULL UNIQUE REFERENCES app_user(id) ON DELETE CASCADE,
    name        VARCHAR(255) NOT NULL,
    logo_url    VARCHAR(255),
    description TEXT,
    website     VARCHAR(255),
    created_at  TIMESTAMP   NOT NULL
);

CREATE TABLE IF NOT EXISTS student (
    id             SERIAL PRIMARY KEY,
    user_id        INTEGER     NOT NULL UNIQUE REFERENCES app_user(id) ON DELETE CASCADE,
    first_name     VARCHAR(100) NOT NULL,
    last_name      VARCHAR(100) NOT NULL,
    bio            TEXT,
    avatar_url     VARCHAR(255),
    github_url     VARCHAR(255),
    linkedin_url   VARCHAR(255),
    promotion_year INTEGER,
    score          INTEGER     NOT NULL DEFAULT 0,
    is_visible     BOOLEAN     NOT NULL DEFAULT TRUE,
    created_at     TIMESTAMP   NOT NULL,
    updated_at     TIMESTAMP
);

CREATE TABLE IF NOT EXISTS skill (
    id       SERIAL PRIMARY KEY,
    name     VARCHAR(100) NOT NULL,
    category VARCHAR(50)  NOT NULL,
    icon_url VARCHAR(255),
    CONSTRAINT UNIQ_SKILL_NAME UNIQUE (name)
);

CREATE TABLE IF NOT EXISTS offer (
    id          SERIAL PRIMARY KEY,
    company_id  INTEGER      NOT NULL REFERENCES company(id) ON DELETE CASCADE,
    title       VARCHAR(255) NOT NULL,
    description TEXT         NOT NULL,
    type        VARCHAR(20)  NOT NULL CHECK (type IN ('job', 'internship', 'alternance')),
    status      VARCHAR(20)  NOT NULL DEFAULT 'draft' CHECK (status IN ('draft', 'published', 'closed')),
    location    VARCHAR(255),
    is_remote   BOOLEAN      NOT NULL DEFAULT FALSE,
    salary_min  DECIMAL(8,2),
    salary_max  DECIMAL(8,2),
    expires_at  DATE,
    starts_at   DATE,
    created_at  TIMESTAMP    NOT NULL,
    updated_at  TIMESTAMP
);

CREATE TABLE IF NOT EXISTS student_skill (
    id                  SERIAL PRIMARY KEY,
    student_id          INTEGER     NOT NULL REFERENCES student(id) ON DELETE CASCADE,
    skill_id            INTEGER     NOT NULL REFERENCES skill(id)   ON DELETE CASCADE,
    level               VARCHAR(20) NOT NULL CHECK (level IN ('beginner', 'intermediate', 'advanced', 'expert')),
    years_of_experience INTEGER     NOT NULL DEFAULT 0,
    created_at          TIMESTAMP   NOT NULL,
    CONSTRAINT UNIQ_STUDENT_SKILL UNIQUE (student_id, skill_id)
);

CREATE TABLE IF NOT EXISTS project (
    id              SERIAL PRIMARY KEY,
    student_id      INTEGER      NOT NULL REFERENCES student(id) ON DELETE CASCADE,
    title           VARCHAR(255) NOT NULL,
    description     TEXT,
    repo_url        VARCHAR(255),
    demo_url        VARCHAR(255),
    image_urls      JSON,
    is_public       BOOLEAN      NOT NULL DEFAULT TRUE,
    completion_year SMALLINT,
    created_at      TIMESTAMP    NOT NULL,
    updated_at      TIMESTAMP
);

CREATE TABLE IF NOT EXISTS project_skill (
    id         SERIAL PRIMARY KEY,
    project_id INTEGER NOT NULL REFERENCES project(id) ON DELETE CASCADE,
    skill_id   INTEGER NOT NULL REFERENCES skill(id)   ON DELETE CASCADE,
    is_primary BOOLEAN NOT NULL DEFAULT FALSE,
    CONSTRAINT UNIQ_PROJECT_SKILL UNIQUE (project_id, skill_id)
);

CREATE TABLE IF NOT EXISTS badge (
    id          SERIAL PRIMARY KEY,
    student_id  INTEGER      NOT NULL REFERENCES student(id) ON DELETE CASCADE,
    name        VARCHAR(100) NOT NULL,
    icon_url    VARCHAR(255),
    points      INTEGER      NOT NULL DEFAULT 0,
    description TEXT,
    awarded_by  VARCHAR(100),
    awarded_at  TIMESTAMP    NOT NULL
);

CREATE TABLE IF NOT EXISTS offer_skill (
    offer_id INTEGER NOT NULL REFERENCES offer(id) ON DELETE CASCADE,
    skill_id INTEGER NOT NULL REFERENCES skill(id) ON DELETE CASCADE,
    PRIMARY KEY (offer_id, skill_id)
);

CREATE TABLE IF NOT EXISTS doctrine_migration_versions (
    version        VARCHAR(191) NOT NULL,
    executed_at    TIMESTAMP,
    execution_time INTEGER,
    PRIMARY KEY (version)
);

