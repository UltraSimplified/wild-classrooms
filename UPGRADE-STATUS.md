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

## Before this goes live: repair the text encoding

**The production database will blank the new site until this is run.**

Content pasted from Word over the years was stored as Windows-1252 in UTF-8
columns, so a curly apostrophe is a lone `0x92` byte rather than the three
bytes UTF-8 needs. "Old Man’s Beard" is the obvious one. There are 91 such
values: 52 in challenges, 27 in spottings, 9 in posts, 2 in units, 1 in groups.

The old Blade site rendered them as a replacement character, so it looked like
a typo. Inertia cannot survive them: `json_encode` refuses invalid UTF-8, the
page object comes through empty, and the Vue app fails to mount — a blank
page, not a broken character.

One of those values is not prose at all. `spottings.picture` on row 671 holds
`/uploads/1605045557-Old Man\x92s Beard.jpg`, while the file on the live server
is named with a proper UTF-8 apostrophe — so that photo has been broken on the
live site for years and the repair fixes it. The command reads its columns from
the schema rather than a list, which is how that one was found: a list only
covers the columns someone thought of.

```bash
php artisan app:repair-text-encoding --dry-run   # report
php artisan app:repair-text-encoding             # apply
```

Safe to run more than once, and it only touches values that are already
invalid.

**Whether you need it depends on how the data arrives.** A phpMyAdmin export
of production (`wildsvp_classes.sql.gz`, 2026-11-20) turned out to be clean:
it had converted every `0x92` to a proper UTF-8 apostrophe on the way out, and
checking for mojibake (`Ã`/`Â` sequences) across every text column found none,
so it had not damaged the values that were already correct. Importing that
dump needs no repair afterwards — verified, the command reports nothing to do.

So:

- **Restoring from a dump** — nothing to run. The export fixes it.
- **Pointing the new app at the existing production database** — run it first,
  or every page is blank.

Either way the characters are wrong in the live database today, so the repair
is still worth running there.

## The local database

`nwc_data` on `BLANC` is a working copy of production, imported 2026-11-20.
The dump's rows were loaded into the schema this repo's migration creates,
rather than letting the dump build its own tables — which is a real check on
that migration, since all 18 tables' columns were accepted with nothing
dropped and every row count matches production (207 users, 91 groups, 266
units, 961 spottings, 61 challenges, 44 likes).

Two of the dump's tables were deliberately left out: `migrations`, so Laravel
keeps the state the new consolidated migration created rather than
production's old 62-row list, and `password_resets`, which modern Laravel
replaced with `password_reset_tokens` and which only ever holds expiring
tokens.

Note that production runs **MyISAM**; the migrated schema is InnoDB, so the
copy has real foreign keys where production has none.

## Still to do

- **Rotate the live secrets** in the old repository's history. See
  `SECRETS-ROTATION.md` there, and add the What3Words key to that list.
  Starting this repo with clean history does not undo the exposure. The Google
  Maps key is the one entry that can be **revoked outright** rather than
  replaced: since the move to Leaflet nothing in this application asks for it,
  so there is no working value to put back.
- **Set the new env values**: `NOVA_ADMIN_EMAILS`, `STAFF_NOTIFICATION_EMAILS`,
  `LETTR_API_KEY`.
- **Set `APP_URL` to the real hostname.** The public disk derives its URL from
  it (`rtrim(env('APP_URL', 'http://localhost'), '/').'/storage'`), and Nova's
  `Image` fields render *absolute* URLs from that disk. Leave it unset and
  every thumbnail in Nova is requested from `http://localhost/storage/...`,
  which on an https page makes the browser ask to reach the local network. The
  site's own pages do not show this, because `MediaUrl` returns root-relative
  paths — so it appears in Nova first. Check it on the server with:

  ```bash
  php artisan tinker --execute='echo Storage::disk("public")->url("x.jpg");'
  ```

  After changing it, `php artisan config:clear` (and `config:cache` again if
  the config is cached).
- **Check the production migration.** The consolidated migration returns early
  when `spottings` exists, so it is a no-op there, but Nova 5 adds two Fortify
  migrations (two-factor columns on `users`, a `passkeys` table). Both are
  additive. Run `migrate --pretend` against a copy first.
- **Copy the uploads.** `public_html/uploads` and `public_html/storage` hold
  the real photos and are not in this repo.
- **Clear the view cache before building the CSS.** `resources/css/app.css`
  carries Laravel's default `@source '../../storage/framework/views/*.php'`,
  which aims Tailwind at the *compiled view cache* rather than at a source
  directory. What sits in that cache depends on which pages happen to have
  been rendered, so the generated stylesheet moves with it: measured here, 431
  rules with Nova's login page cached against 395 with the cache cleared — 36
  of Nova's own utilities quietly absorbed into the site's CSS. It is additive
  rather than breaking, but it means two builds of the same commit can differ.
  Clear it first in deploy and in CI:

  ```bash
  php artisan view:clear && npm run build
  ```
- **A map on the home page.** The species pages now plot their spottings with
  Leaflet on OpenStreetMap, so the dependency and a reusable component
  (`resources/js/Components/SpottingMap.vue`) are already here. `HomeController`
  still sends `mapGroups`, and `Home.vue` types it as `{id, title, slug}` —
  dropping the latitude and longitude it is given. Widening that type and
  reusing SpottingMap is the whole job.
- **Decide what the journal should show.** It lists `Last seen`, `Location`,
  `Spotted by` and `Notes` for every record, which is what the live site has
  always published — so this restores parity rather than exposing anything new.
  It is still a children's site publishing first names alongside places, and
  `soved/laravel-gdpr` is installed, so it is worth a deliberate decision
  rather than an inherited one.
- **Browser tests.** `pest-plugin-browser` is installed but unused.
- **The woodland page makes two image requests per member** (tree and sign), so
  a ten-member group is twenty requests. They carry a five-minute cache header;
  inlining the SVG would remove them if it ever matters.
