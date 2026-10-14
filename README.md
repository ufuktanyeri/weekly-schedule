# weekly-schedule

University course schedule management system (Haftalık Ders Programı), migrated from a legacy custom MVC
application to **Laravel 12** and **PHP 8.2** (`declare(strict_types=1);` in every PHP file).

## Features

- **30-minute time slots** from 08:30 to 18:00, Monday (Pazartesi) to Friday (Cuma), with a configurable
  12:30–14:00 lunch break.
- **Workload tracking**: an instructor cannot be assigned more than their weekly limit. The default is 20 hours,
  and each instructor can have their own limit in `ogretim_elemanlari.haftalik_ders_limiti`.
- **Automatic conflict detection** (`ConflictValidationService`). It blocks instructor double-booking,
  classroom overlaps, lunch-break and out-of-grid slots, and duplicate entries. Every rejected attempt is
  stored in `cakisma_loglari`.
- **RBAC**: `super_admin`, `admin`, `instructor`, `teacher`, `editor`, `guest`. Users who sign in with Google using a
  verified address on a configured domain (default `@ankara.edu.tr`) automatically become `super_admin`.
- **User approval workflow** (`AuthService`): new accounts start as `pending` and an administrator moves them to
  `approved` or `rejected`.
- Blade views styled with the Tailwind CSS CDN. There is no NPM/Vite build step.

Controllers are thin. All business rules live in `app/Services`: `TimeSlotService`, `WorkloadService`,
`ConflictValidationService`, `ProgramService`, `AuthService`.

## Database

| Table | Purpose |
| --- | --- |
| `admin_users`, `user_approval_requests` | Accounts (local password and/or Google OAuth) and the approval history |
| `programlar` | Vocational / associate degree programs |
| `ogretim_elemanlari` | Faculty, including a HEX colour and pattern (`dots`, `stripes`, `grid`, `diagonal`) for the grid |
| `derslikler` | Classrooms with capacity and type |
| `dersler` | Courses with theory/practice hours and credits |
| `akademik_donemler` | Academic terms with an `aktif` flag |
| `ders_atamalari` | Course → instructor allocations per term (weekly hours) |
| `haftalik_program` | The weekly grid. There is one row per 30-minute slot, and unique indexes protect against double-booking |
| `cakisma_loglari` | Log of failed assignment attempts |

MySQL/MariaDB tables use `utf8mb4` with `utf8mb4_turkish_ci` collation. Tests run on in-memory SQLite.

## Setup

```bash
composer install
cp .env.example .env            # set DB_* (MySQL/MariaDB) and optionally GOOGLE_*
php artisan key:generate
php artisan migrate --seed      # schema + mock dataset
composer dev                    # php artisan serve
```

Seeded accounts (password: `password`):

| E-mail | Role |
| --- | --- |
| super@ankara.edu.tr | super_admin |
| yonetici@ankara.edu.tr | admin |
| editor@ankara.edu.tr | editor |
| ahmet.yilmaz@ankara.edu.tr | instructor |
| selin.aydin@ankara.edu.tr | teacher |

The seeders also create pending and rejected users, 4 programs, 2 terms, 6 classrooms, 8 instructors, 16 courses,
17 allocations, a full weekly grid, and a few deliberately conflicting attempts so that the conflict log has entries.

### Google OAuth

Set `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` and `GOOGLE_REDIRECT_URI` (`/auth/google/callback`).
`SCHEDULE_SUPER_ADMIN_DOMAINS` is a comma-separated list of domains that are granted `super_admin` automatically.
Only exact domain matches with a Google-verified e-mail count.

### Schedule settings

`config/schedule.php` (via `.env`): `SCHEDULE_DAY_START`, `SCHEDULE_DAY_END`, `SCHEDULE_SLOT_MINUTES`,
`SCHEDULE_LUNCH_ENABLED`, `SCHEDULE_LUNCH_START`, `SCHEDULE_LUNCH_END`, `SCHEDULE_MAX_WEEKLY_HOURS`.

## Quality

```bash
composer test        # PHPUnit 11 (SQLite in-memory)
composer analyse     # PHPStan / Larastan level 8
vendor/bin/pint      # code style (Laravel preset + declare_strict_types)
```

## Framework version

The project was originally specified for Laravel 11. Laravel 11 is end of life, and every 11.x release is affected
by unpatched advisories, including a CRLF injection in the default `email` validation rule, which is fixed only in
12.60.0 / 13.10.0. The project therefore requires `laravel/framework` `^12.60`, the newest line that still supports
PHP 8.2 (13.x requires PHP 8.3). `composer audit` reports no advisories.

As defense in depth, all e-mail validation uses `email:filter`, which rejects CR/LF, and
`tests/Feature/EmailValidationRuleTest.php` fails if any form request uses a different e-mail validator.
