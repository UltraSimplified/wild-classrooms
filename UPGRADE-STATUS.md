# Port status

The foundation is complete and verified. The application's own pages are not
yet ported — the old site still serves traffic until they are.

## Done

- **Laravel 13.43** on the modern `bootstrap/app.php` skeleton.
- **Nova 5.11** installed, with all 9 resources, 3 metrics and the dashboard
  ported. `Place` fields became `Text`: Nova removed `Place` when Algolia
  Places shut down, which is what the `ALGOLIA_*` keys in the old `.env` were
  for.
- **Models** moved `app/` → `app/Models`, with typed relations, `casts()` and
  factories for every one.
- **Schema** consolidated into one migration generated from production and
  verified column-for-column against all 14 tables.
- **Inertia 2 + Vue 3 + Vite 7 + Tailwind 4 + TypeScript** building clean,
  with Wayfinder and Ziggy wired.
- **Lettr** as the mail transport; all three mailables rewritten.
- **Quality regime** matching Ex-Libris: Pest, Larastan level 5, Sloppy,
  Rector, Pint, Prettier, ESLint. All green, no baseline entries used.
- **Auth** via Breeze (Inertia/Vue), with email verification removed to match
  existing behaviour.

## Not yet ported

### Controllers → Inertia (~2,200 lines)

| Controller | Lines | Notes |
|---|---|---|
| `TreesController` | 659 | Generates the woodland SVG — the tree-with-leaves graphic that is the heart of the site. Largest and most delicate piece. |
| `SpotsController` | 306 | Spotting upload, EXIF extraction, image handling. Needs `intervention/image` v3 API changes. |
| `ProfilesController` | 189 | Group onboarding and member management. |
| `ChallengesController` | 132 | Monthly challenge listings. Mostly empty resource stubs that can be dropped. |
| `UnitsController` | 99 | Member tree pages. |
| `BlogsController` | 93 | “Dig Deeper” posts. |
| `Admin\UsersController` | 86 | Role management. |
| `GalleryController` | 40 | Gallery and likes. |
| `GroupsController` | 46 | Woodland and scene pages. |
| `HomeController` | 25 | Featured spottings and current challenges. |
| `SchoolsController`, `FamiliesController`, `MapController` | 200 | Reference `schools`/`classrooms`, which are effectively dead. Confirm before porting. |
| `What3WordsController` | 48 | W3W was deactivated (unsupported on free accounts). Drop unless reinstated. |

### Frontend

Every page is still an unported Blade template using Foundation CSS, inline
styles, mmenu, awesomplete and **FontAwesome Pro from a CDN** — a paid licence
loaded client-side. The Tailwind theme tokens for the woodland palette and
IM Fell English are already in `resources/css/app.css`; the pages themselves
need building as Vue components.

### Known issues to address during the port

- `GalleryController@like` mutates state on a `GET` route, so it is
  CSRF-exempt and crawler-triggerable. Should be a `POST`.
- `GalleryController@index` passes a hardcoded seed to `inRandomOrder()`, so
  the “random” gallery order never actually changes.
- The Google Maps key is hardcoded in three Blade templates. It moves to
  `config/services.php` and reaches the page as an Inertia shared prop.
- Several controllers use `date('m')` rather than Carbon, which ignores the
  app timezone.

## Blocking the public repo

`SECRETS-ROTATION.md` in the old repository lists five live credentials that
are in that repository's git history and must be rotated at the provider.
Starting this repo with clean history does not undo that exposure — anyone
holding a clone of the old repo still has those keys.
