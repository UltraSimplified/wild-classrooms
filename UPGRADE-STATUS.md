# Port status

The application is ported. Laravel 13, Nova 5, and every public and member
page rebuilt on Inertia 2 + Vue 3.

## Done

### Framework and admin

- **Laravel 13.43** on the modern `bootstrap/app.php` skeleton.
- **Nova 5.11** with all 9 resources, 3 metrics and the dashboard ported.
  `Place` fields became `Text`: Nova dropped `Place` when Algolia Places shut
  down, which is what the old `ALGOLIA_*` keys were for.
- **Models** moved to `app/Models` with typed relations, `casts()` and
  factories.
- **Schema** consolidated into one migration generated from production and
  verified column-for-column across all 14 tables.

### Pages

Every controller is ported and every page rebuilt in Vue:

| Area | Pages |
|---|---|
| Home | hero tree, this month's five, featured photos, group list |
| Challenges | index, month, species detail (at the old `/spottings/{id}/{slug}` URL) |
| Gallery | masonry, liking |
| Groups | index, woodland (trees and signposts) |
| Members | tree, monthly challenges |
| Spottings | add, edit, delete, photo upload |
| Dig Deeper | index, post |
| Onboarding | group details, members |
| Account | group editing, Breeze profile |
| Admin | user list, user editing |
| Static | about, terms, cookie policy |

The woodland artwork is unchanged: all 60 leaf groups, the trunk and the
signpost were extracted verbatim from the old 659-line `TreesController` into
`resources/svg/` and are rendered by `TreeRenderer` and `SignRenderer`.

### Frontend

Inertia 2 + Vue 3 + Vite + Tailwind 4 + TypeScript, with Wayfinder and Ziggy.
Display type is IM Fell English (kept from the old theme); body type is
Atkinson Hyperlegible, designed for legibility, since the readers are
children. The palette is sampled from the fill values in the leaf and trunk
SVGs so the interface and the artwork match exactly.

### Mail

Lettr is the transport. All three mailables were rewritten to
Envelope/Content, and the user is now passed in rather than read from `Auth`
inside the view, which a queued job cannot do.

### Quality

Pest, Larastan level 5, Sloppy, Rector, Pint, Prettier, ESLint. All green:
79 tests, no PHPStan errors, Sloppy 100/100, no baseline entries anywhere.

## Security fixes made during the port

Two of these were exploitable by any signed-in user against any group. All are
covered by tests in `tests/Feature/AuthorizationTest.php`.

- **Any user could edit any group.** `ProfilesController::update()` loaded
  `Group::find($request->group_id)` from request input and saved it with no
  ownership check — all 91 groups, including their member lists, and clearing
  a member deletes their spottings. The group now comes from the session and
  goes through `GroupPolicy`.
- **Any user could file a spotting against any member.** `SpotsController::save()`
  took `unit_id` from input unchecked, so a photo could be attached to any of
  the 266 members of any group. Now behind `SpottingPolicy`.
- **Any user could add members to any group.** `onboarding_finish()` trusted
  `group_id` from the form.
- **A hardcoded What3Words API key** appeared three times in the old source
  (`SpotsController` twice, `What3WordsController` once). W3W was already
  disabled, so the dependency is gone entirely — but that key was committed and
  should be treated as exposed and revoked.
- **Member names went into SVG unescaped**, so a name containing markup broke
  or injected into the signpost image. Escaped now.
- **The Nova gate was dead.** Nova 5 calls `gate()` from the parent
  `register()`; an empty override removes it, and `viewNova` is then never
  defined — nobody could have reached Nova in production.
- **Uploads had no validation at all** — no type, no size. Now limited to
  JPEG/PNG/WebP/HEIC at 12MB.
- **Liking was a GET** that mutated state, so it was CSRF-exempt and any
  crawler following links inflated the counts. It is a POST.
- **Staff emails were hardcoded** in two controllers and had drifted apart —
  one said `stroudvalleysproject.org`, the other `stroudvalleysproject.org.uk`,
  so one set of notifications was going nowhere. Now `STAFF_NOTIFICATION_EMAILS`.
- **Nova admin emails were hardcoded** in `NovaServiceProvider`. Now
  `NOVA_ADMIN_EMAILS`; an empty value admits nobody.

## Other bugs fixed

- `Image::read()` does not exist in Intervention Image v4 — it is
  `decodePath()`. The upload path would have thrown at runtime.
- Wild spottings grew a January leaf they had not earned: the "Wild spotting"
  challenge has `month = NULL`, and the old code fed that to `strtotime`,
  which failed and fell back to the epoch.
- `sign()` called `strpos(' ', $label)` with the arguments reversed, so names
  were never shortened to one word and long ones ran off the signpost.
- Editing a group's members matched submitted names positionally against the
  existing list, so reordering the form reassigned members' spottings to each
  other. Members are matched by id now.
- Staff registration emails printed `$group->postcode`, which does not exist —
  the column is `postal_code`, so the postcode never appeared.
- Nova's `Post` resource eager-loaded and linked a `user` relation that does
  not exist; `posts.author_id` means the relation is `author`.
- Nova's `Spotting` resource used `$title = 'title'` with no such column, so
  records were blank in search and relation pickers.
- Nova's `Classroom` resource declared `$title` twice.
- December asked for month 13 and got nothing back; it wraps to January.
- The gallery passed a fixed seed to `inRandomOrder()`, so the "random" order
  never changed.
- Replacing a spotting's photo left the old file on disk.
- EXIF GPS parsing was duplicated between create and edit and the two copies
  had drifted, so the same photo could yield a location on one path and not
  the other. A `0/0` denominator divided by zero.
- Image URLs were resolved three different ways across the old templates; the
  home page asked for `/storage//uploads/...`, which did not load. `MediaUrl`
  resolves all three stored formats in one place.
- Breeze's email verification was removed: none of the 207 existing users has
  a verified email, so enforcing it would have locked out every one of them.

## Deliberately dropped

- **What3Words** — already disabled on the old site ("unsupported for free
  accounts"). The field is still stored and displayed; nothing calls the API.
- **`SchoolsController`, `FamiliesController`, `MapController`** — these drive
  `schools` (0 rows) and `classrooms` (5 rows). The tables and Nova resources
  are kept for fidelity but the pages are gone. Confirm before deleting the
  tables.
- **FontAwesome Pro from a CDN** — a paid licence loaded client-side on every
  page. The new pages use no icon font.
- **The third-party cookie-reporting script** on the cookie policy page.

## Still to do

- **Rotate the live secrets** in the old repository's history. See
  `SECRETS-ROTATION.md` there, and add the What3Words key to that list.
  Starting this repo with clean history does not undo the exposure.
- **Set the new env values**: `NOVA_ADMIN_EMAILS`, `STAFF_NOTIFICATION_EMAILS`,
  `LETTR_API_KEY`, `GOOGLE_MAPS_API_KEY`.
- **Check the production migration.** The consolidated migration returns early
  when `spottings` exists, so it is a no-op there, but Nova 5 adds two Fortify
  migrations (two-factor columns on `users`, a `passkeys` table). Both are
  additive. Run `migrate --pretend` against a copy first.
- **Copy the uploads.** `public_html/uploads` and `public_html/storage` hold
  the real photos and are not in this repo.
- **A map on the home page.** The old site used Google Maps with the key
  pasted into the template. The data is passed to the page but not yet
  plotted; Leaflet with OpenStreetMap would remove the Google dependency
  entirely.
- **Browser tests.** `pest-plugin-browser` is installed but unused.
- **The woodland page makes two image requests per member** (tree and sign), so
  a ten-member group is twenty requests. They carry a five-minute cache header;
  inlining the SVG would remove them if it ever matters.
