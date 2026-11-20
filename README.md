# Wild Classrooms

“Spot 60 Species in a Year” — a wildlife-spotting project for schools, families
and groups around Stroud, run by [Stroud Valleys Project](https://www.stroudvalleysproject.org).

Groups register, add members (each gets a bare tree in a virtual woodland), and
tick off monthly species challenges. Every spotting adds a leaf to the member's
tree.

## Stack

| | |
|---|---|
| PHP | 8.3+ |
| Framework | Laravel 13 |
| Admin | Nova 5 |
| Frontend | Inertia 2 + Vue 3 + Vite + Tailwind 4, TypeScript |
| Routing to JS | Wayfinder + Ziggy |
| Mail | [Lettr](https://lettr.com) |
| Errors | Bugsnag |
| Tests | Pest 5 |
| Static analysis | Larastan (level 5) + Sloppy |
| Formatting | Pint, Prettier, ESLint |

## Getting started

```bash
composer setup
```

That installs dependencies, copies `.env`, generates a key, migrates, and builds
assets. Then:

```bash
composer dev
```

This runs the server, queue worker, log tailer and Vite together.

Nova requires credentials for `nova.laravel.com` in your global Composer
`auth.json`; it is a commercial package and is not on Packagist.

## Quality gate

```bash
composer test
```

Runs `pint --test` then the Pest suite.

```bash
composer analyse
```

Runs Larastan and Sloppy.

```bash
npm run lint && npm run typecheck
```

Both must be clean before a commit.

## Database notes

The schema lives in a **single consolidated migration**,
`0001_01_01_000000_create_wild_classrooms_schema.php`, generated from the live
production database and verified against it column-for-column. It replaced 62
migrations written between 2014 and 2024, two of which could not run at all —
one declared a duplicate class name, another dropped a foreign key by name,
which SQLite cannot do. That had made a from-scratch migrate impossible, so the
test suite could never build the schema.

The migration returns early if the `spottings` table already exists, so it is a
no-op against production and builds everything on a fresh database.

Two tables, `schools` (0 rows) and `classrooms` (5 rows), are carried over for
fidelity but are effectively unused and are candidates for removal.

## Configuration that is not obvious

- `NOVA_ADMIN_EMAILS` — comma-separated staff emails allowed into Nova outside
  local. An empty value admits nobody. These were previously hardcoded in
  `NovaServiceProvider`.
- `GOOGLE_MAPS_API_KEY` — necessarily visible in page source, so **restrict it
  by HTTP referrer** in the Google Cloud Console. That is the only real
  protection for a Maps key.
- `LETTR_API_KEY` — Lettr refuses a From address whose domain is not verified in
  the Lettr account, so `MAIL_FROM_ADDRESS` must be on a verified domain.
