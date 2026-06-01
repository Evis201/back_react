<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return static function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};

  Routes registered:

  ┌───────────┬────────────────────┬────────────────────────────────────────────────────────┐
  │  Method   │        Path        │                         Guard                          │
  ├───────────┼────────────────────┼────────────────────────────────────────────────────────┤
  │ POST      │ /api/auth/register │ PUBLIC                                                 │
  ├───────────┼────────────────────┼────────────────────────────────────────────────────────┤
  │ POST      │ /api/auth/login    │ PUBLIC (JWT firewall)                                  │
  ├───────────┼────────────────────┼────────────────────────────────────────────────────────┤
  │ GET       │ /api/students      │ PUBLIC — ?skillId=&promotionYear=&search=&page=&limit= │
  ├───────────┼────────────────────┼────────────────────────────────────────────────────────┤
  │ GET       │ /api/students/{id} │ PUBLIC                                                 │
  ├───────────┼────────────────────┼────────────────────────────────────────────────────────┤
  │ POST      │ /api/students      │ ROLE_STUDENT                                           │
  ├───────────┼────────────────────┼────────────────────────────────────────────────────────┤
  │ PUT/PATCH │ /api/students/{id} │ ROLE_STUDENT + ownership check                         │
  ├───────────┼────────────────────┼────────────────────────────────────────────────────────┤
  │ GET       │ /api/offers        │ PUBLIC — ?type=&skillId=&isRemote=&search=             │
  ├───────────┼────────────────────┼────────────────────────────────────────────────────────┤
  ├───────────┼────────────────────┼────────────────────────────────────────────────────────┤
  │ GET       │ /api/offers/{id}   │ PUBLIC                                                 │
  ├───────────┼────────────────────┼────────────────────────────────────────────────────────┤
  │ POST      │ /api/offers        │ ROLE_COMPANY                                           │
  ├───────────┼────────────────────┼────────────────────────────────────────────────────────┤
  │ PUT/PATCH │ /api/offers/{id}   │ ROLE_COMPANY + ownership check                         │
  ├───────────┼────────────────────┼────────────────────────────────────────────────────────┤
  │ DELETE    │ /api/offers/{id}   │ ROLE_COMPANY + ownership check                         │
  └───────────┴────────────────────┴────────────────────────────────────────────────────────┘

  Architecture:
  - Controllers → thin: decode JSON, validate DTO, call service, return JSON
  - Services → business logic + normalization (no Serializer magic, full control)
  - Repositories → query logic (findVisibleWithFilters, findOneWithDetails)

  To run against a real DB:
  php bin/console doctrine:database:create
  php bin/console doctrine:migrations:diff
  php bin/console doctrine:migrations:migrate --no-interaction
  php bin/console doctrine:fixtures:load --no-interaction