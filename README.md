# Élève Finder — Symfony 7.3 API + React 19 Frontend

Full-stack job platform connecting students and companies. JWT-authenticated REST API (Symfony) + React SPA (Vite). PostgreSQL + Docker.

## Requirements

| Tool | Version |
|------|---------|
| PHP | ≥ 8.2 |
| Composer | ≥ 2.x |
| Node.js | ≥ 18 |
| Docker + Docker Compose | any recent |
| Symfony CLI | optional but recommended |
| OpenSSL | for JWT keys |

---

## Installation

### 1. Clone & install dependencies

```bash
composer install
composer require symfony/mime
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

---

## Frontend (React)

The React app is in `projectReact/`. It communicates with the Symfony API via a Vite dev-server proxy (`/api` → `http://localhost:8000`).

### Setup

```bash
cd projectReact
npm install
```

### Run (dev)

```bash
npm run dev
```

Frontend available at `http://localhost:5173`.

**Symfony must be running on port 8000** (see Run section above) before starting the React dev server.

### Build (production)

```bash
npm run build
```

Output in `projectReact/dist/`. Serve the static files with any web server or copy them into Symfony's `public/` directory.

---

## Full-stack startup ordef
```bash
# 1. Start PostgreSQL
docker compose up -d

# 2. Apply migrations
php bin/console doctrine:migrations:migrate --no-interaction

# 3. Start Symfony API (terminal 1)
symfony server:start

# 4. Start React dev server (terminal 2)
cd projectReact && npm run dev
```

Open `http://localhost:5173` in your browser.

---

## Auth flow

| Action | Endpoint |
|--------|----------|
| Register | `POST /api/auth/register` — body: `{email, password, role, firstName?, lastName?, companyName?}` |
| Login | `POST /api/auth/login` — body: `{email, password}` → returns `{token}` |

The React app stores the JWT in `localStorage` (`jwt_token`) and sends it as `Authorization: Bearer <token>` for protected requests.

**Roles:** `ROLE_STUDENT` (student profile), `ROLE_COMPANY` (manage offers).
