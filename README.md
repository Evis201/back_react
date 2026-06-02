# back_react — Symfony 7.3 REST API

JWT-authenticated API for students, companies, and job offers. PostgreSQL + Docker.

## Requirements

| Tool | Version |
|------|---------|
| PHP | ≥ 8.2 |
| Composer | ≥ 2.x |
| Docker + Docker Compose | any recent |
| Symfony CLI | optional but recommended |
| OpenSSL | for JWT keys |

---

## Installation

### 1. Clone & install dependencies

```bash
composer install
```

### 2. Configure environment

Copy and edit the dev env file:

```bash
cp .env.dev .env.local
```

Edit `.env.local` — set these variables:

```dotenv
APP_ENV=dev
APP_SECRET=changeme_random_32chars

DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=16&charset=utf8"
DEFAULT_URI="postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=16&charset=utf8"

JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=changeme
```

### 3. Start PostgreSQL via Docker

```bash
docker compose up -d
```

DB runs on `localhost:5432`, user `app`, password `!ChangeMe!`, database `app`.

### 4. Generate JWT keys

```bash
mkdir -p config/jwt
openssl genpkey -algorithm RSA -out config/jwt/private.pem -pkeyopt rsa_keygen_bits:4096
openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem
```

> If you set a passphrase during key generation, match it in `JWT_PASSPHRASE`.

### 5. Setup database

**Option A — Doctrine (recommended):**

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate --no-interaction
```

### 6. Load fixtures (optional dev data)

```bash
php bin/console doctrine:fixtures:load --no-interaction
```

---

## Run

**With Symfony CLI:**

```bash
symfony server:start
```

API available at `http://localhost:8000`

---

## Tests

```bash
php bin/phpunit
```
