# HeroesLounge dev environment

Self-sufficient Docker environment for local development: October CMS v1.1
(PHP 7.4, Laravel 6) + MySQL 5.7, with this repo's plugins/theme mounted in and
deterministic fixture data — no marketplace Project ID, no DB dump needed.

The site runs on **http://localhost:8090/** (8080 is occupied on this machine).

## Bring-up

From the repo root (Docker Desktop must be running):

```powershell
docker compose -f dev/docker-compose.yml up -d --build
# wait until migrations finish (INIT_OCTOBER runs `october:up` on start):
docker compose -f dev/docker-compose.yml logs -f web   # until "Initializing October CMS..." finishes / apache starts
# seed fixture data:
docker compose -f dev/docker-compose.yml exec web php artisan fixtures:seed
```

`fixtures:seed` asks for confirmation before truncating (it wipes ~20 tables).
For scripted / non-interactive use (e.g. `exec -T`), pass `--force`:

```powershell
docker compose -f dev/docker-compose.yml exec -T web php artisan fixtures:seed --force
```

Then open http://localhost:8090/ — the old HeroesLounge theme should render with
fixture blog posts, an active "Season 30" with three divisions, standings,
played and upcoming matches.

`fixtures:seed` is **idempotent**: every run truncates all fixture-owned tables
(teams, seasons, matches, users/sloths, blog, ...) and reseeds from scratch, so
re-running is always safe. For the same reason, never run it against a database
whose contents you care about.

### Live-data seed (`fixtures:live-data`)

For a database running the **imported production dump** (where `fixtures:seed`
must never run — see the warning below), use the dev-only live-data seeder
instead:

```powershell
docker compose -f dev/docker-compose.yml exec -T web php artisan fixtures:live-data --force
```

- **Additive and narrowly scoped**: it only creates matches under the two
  active seasons (`eu-season-30` divisions, `NMMR3`) plus blog content with
  `dev-` slugs (an `events` category + posts). Everything else in the dump is
  left untouched.
- **Re-runnable**: each run first cleans **only its own previous output**, and
  also purges dump-orphaned match-keyed rows (rows in timeline/pivot/game
  tables referencing matches that no longer exist — see PROGRESS.md for the
  id-reuse trap these caused).
- Flags: `--force` (skip confirmation, needed with `exec -T`), `--skip-blog`
  (matches only).
- **Takes ~30 minutes**: the frozen plugin recomputes division standings on
  every match save; this is expected, let it finish.

> ⚠️ Do **not** confuse this with `fixtures:seed`, which **truncates ~20
> tables** and must never be run against the imported dump.

## Logins

- **Backend**: http://localhost:8090/backend — login `admin`, password
  `dev12345` (`fixtures:seed` resets it; before the first seed, October
  generates a random admin password — it is printed in the `web` container
  log). A second backend account `editor` / `dev12345` is created by
  `fixtures:seed` (used as the alternating blog-post author).
- **Frontend**: any fixture user, password `dev12345`. Log in on
  http://localhost:8090/user with the user's **email** (`<username
  lowercased>@dev.local` — the login form rejects bare usernames), e.g.:
  - `AlphaCap` (`alphacap@dev.local`) — captain of *Alpha Sloths* (Division 1)
  - `DoubleDuty` — member of *Alpha Sloths* AND captain of *The B Team*
  - `CasterCarl` — approved caster on an upcoming match
  - `PendingPete` — pending (unapproved) caster on an upcoming match

  `fixtures:seed` prints the full account list when it finishes.
- **Imported July-2026 dump**: use user 41, username `Hapcher`, email
  `Hapcher5166@fakegmail.com`, password `dev12345` (sloth 25). The current local
  RainLab.User setting is `login_attribute=username`, so enter `Hapcher`; if an
  environment is configured for email login instead, use the email. This is a
  local verification convenience only and must not be copied to production.

## Useful URLs

- `http://localhost:8090/user` — account sign-in and registration. Its new-theme
  page intentionally keeps `forceSecure = 1`. The frozen SlothAccount component
  drops the parent component's redirect response, so the page still renders on
  local HTTP; do not change the committed property for verification.
- `http://localhost:8090/` — home (blog posts from fixtures)
- `http://localhost:8090/season-30` — season overview
- `http://localhost:8090/season-30/division-1` — division page (standings,
  round matches, upcoming matches)
- `http://localhost:8090/calendar` — match calendar
- `http://localhost:8090/blog` — blog list

## What's in the image / what's mounted

Mounted from the repo (live-editable, the whole point of the env):

- `plugins/rikki` → `/var/www/html/plugins/rikki`
- `plugins/dev` → `/var/www/html/plugins/dev` (dev-only fixtures plugin)
- `themes/HeroesLounge-Theme` → `/var/www/html/themes/HeroesLounge-Theme`
- `dev/docker/plugins/indikator` → `/var/www/html/plugins/indikator` (see below)

Baked into the image (`dev/docker/Dockerfile`), pinned to the last
October-v1-compatible releases, installed from GitHub:

(keep this table in sync with `dev/docker/Dockerfile`)

| Plugin | Version |
|---|---|
| RainLab.User | v1.7.2 |
| RainLab.Blog | v1.6.3 |
| RainLab.Pages | v1.5.12 |
| RainLab.Translate | v1.12.0 |
| RainLab.GoogleAnalytics | v1.3.2 (provides the `googleTracker` component declared by every theme layout) |
| RainLab.Location | v1.2.5 (provides `form_select_country()` used by the account page) |
| OFFLINE.SiteSearch | v1.7.9 |
| ToughDeveloper.ImageResizer | v1.4.0 (provides the `\| resize` Twig filter; shows a placeholder for missing logos) |
| ShahiemSeymor.Roles | master (commit-pinned; provides the `can()` / `hasRole()` Twig functions) |
| AnandPatel.WysiwygEditors | master (commit-pinned, no tags) |
| Zainab.SimpleContact | master (commit-pinned, no tags) |

### Compatibility shims (dev-only, documented deviations)

- **Indikator.Content** — the rikki plugins hard-require this marketplace
  plugin (the theme's blog runs through it), but it is *not obtainable*
  without a marketplace account: its GitHub repo (`gergo85/oc-content`) was
  deleted and it isn't on Packagist. `dev/docker/plugins/indikator/content` is
  a minimal reimplementation of exactly the surface the theme + rikki plugins
  use (Blog/Category models, `blogList` / `blogPage` / `blogCategories` /
  `tagsList` components). When real marketplace access arrives, delete the
  shim mount and install the real plugin.
- **`ssbuttonsnb` / `ssbuttonsssb` / `SideNav` components** — referenced by the
  theme; their source plugins have no public source. `plugins/dev/fixtures`
  registers no-op stand-ins so the pages render (without share buttons).
- **AuthCode secret classes** — production keeps API-secret classes
  (`Rikki\Heroeslounge\classes\Discord\AuthCode` etc.) outside the repo.
  `plugins/dev/fixtures` loads empty-string stubs so code paths that touch
  Discord/HeroesProfile/Mailchimp degrade to no-ops instead of fataling.
- **`mmr_bound` column** — production has it on divisions, but no repo
  migration creates it (schema drift). `plugins/dev/fixtures` adds it via its
  own migration.
- **`_()` Twig function** — the theme calls `_('key', 'ns::lang.section')` as
  a function (RainLab.Translate only ships the `|_` filter); the plugin that
  provided the function form is unknown/unobtainable, so `plugins/dev/fixtures`
  registers a compatible implementation.

### Known pre-existing theme gaps (not environment bugs)

- `/contact` 500s: the theme references a `sections/contact` partial that does
  not exist in this repo.
- `/timezone` 500s without a `?time=...` query parameter (raw `$_GET` access
  in the page's PHP section); it is only ever called with the parameter.

## Fixture data summary

- Regions EU + NA; maps (id 1 = "Free Win", as the code expects).
- One **active** season "Season 30" (EU, `current_round = 3`) with 3 divisions
  (`mmr_bound` 2800/2400/2000).
- 10 teams incl. ugly cases: no logos anywhere, one disbanded
  (*Disbanded Legends*), an absurdly long name, unicode/quotes/angle brackets
  in a name, a one-member team, an NA team in an EU season.
- 14 users+sloths (password `dev12345`), one user on two teams and captain of
  one.
- `team_division` pivots: win/match counts come from real played matches
  (through the production model events), plus a bye, an inactive entry and a
  free win.
- 6 played matches **with games** (maps, winners, per-game scores; one a
  double "Free Win" map game) and 6 upcoming matches within 14 days: `wbp`
  set, `wbp` NULL, one with an approved caster + linked Twitch channel, one
  with a pending caster.
- Blog: categories `events` + `announcements`, 5 posts (3 in `events`, one
  featured, dates spread over the last 16 days, authors alternating between
  the backend `admin` and the seeded `editor` user).

## Swapping in the real team dump later

1. Obtain `hl.sql` (see repo README) and marketplace/Project ID access.
2. Stop seeding fixtures. Import the dump:
   ```powershell
   docker compose -f dev/docker-compose.yml exec -T db mysql -uoctober -poctober heroeslounge < hl.sql
   ```
3. Install the real marketplace plugins (at minimum Indikator.Content) via the
   backend's *Settings → Updates → Attach Project*, then remove the
   `dev/docker/plugins/indikator` mount from `dev/docker-compose.yml`.
   - [ ] verify events gold-tag rendering (`prize` tag → `.event-gold`) — the
     dev shim has no tags, so home/events.htm's gold branch is unexercised
     until the real plugin is in.
   - [ ] revisit `heroeslounge-next/pages/blog/tag_posts.htm` — `/blog/tag/:slug`
     renders a graceful "not available" fallback because the shim has no tags
     (the OLD theme's page was an inert stub too: no components, empty body).
     Decide with real tag data whether a functional tag archive is worth porting.
   - [ ] re-verify post-not-found semantics on `/blog/post/:slug` — the real
     Indikator blogPage may honor its `redirectPage` property (redirect to
     /blog) for a missing post BEFORE `pages/blog/post.htm`'s `onEnd()` 404
     runs; the dev shim never redirects, so today the page 404s. Decide 404
     vs redirect after the swap.
4. `docker compose -f dev/docker-compose.yml exec web php artisan october:up`
   to apply any pending migrations against the dump.
5. Raw dump frontend accounts follow the anonymised-dump password convention
   documented in the repository README. In this standing dev database, user 41
   has intentionally been set to `dev12345` for repeatable verification; see
   **Logins** above.

## Housekeeping

```powershell
docker compose -f dev/docker-compose.yml down          # stop
docker compose -f dev/docker-compose.yml down -v       # stop + wipe database
docker compose -f dev/docker-compose.yml exec web bash # shell in the web container
```
