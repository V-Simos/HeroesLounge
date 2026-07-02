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

Then open http://localhost:8090/ — the old HeroesLounge theme should render with
fixture blog posts, an active "Season 30" with three divisions, standings,
played and upcoming matches.

`fixtures:seed` is **idempotent**: every run truncates all fixture-owned tables
(teams, seasons, matches, users/sloths, blog, ...) and reseeds from scratch, so
re-running is always safe. For the same reason, never run it against a database
whose contents you care about.

## Logins

- **Backend**: http://localhost:8090/backend — login `admin`, password `admin`
  (October's default seeded administrator).
- **Frontend**: any fixture user, password `dev12345`. Log in on
  http://localhost:8090/user with the username, e.g.:
  - `AlphaCap` — captain of *Alpha Sloths* (Division 1)
  - `DoubleDuty` — member of *Alpha Sloths* AND captain of *The B Team*
  - `CasterCarl` — approved caster on an upcoming match
  - `PendingPete` — pending (unapproved) caster on an upcoming match

  `fixtures:seed` prints the full account list when it finishes.

## Useful URLs

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

| Plugin | Version |
|---|---|
| RainLab.User | v1.7.2 |
| RainLab.Blog | v1.6.3 |
| RainLab.Pages | v1.5.12 |
| RainLab.Translate | v1.12.0 |
| RainLab.GoogleAnalytics | v1.3.2 |
| OFFLINE.SiteSearch | v1.7.9 |
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
  featured, dates spread over the last 16 days, authored by the backend
  admin).

## Swapping in the real team dump later

1. Obtain `hl.sql` (see repo README) and marketplace/Project ID access.
2. Stop seeding fixtures. Import the dump:
   ```powershell
   docker compose -f dev/docker-compose.yml exec -T db mysql -uoctober -poctober heroeslounge < hl.sql
   ```
3. Install the real marketplace plugins (at minimum Indikator.Content) via the
   backend's *Settings → Updates → Attach Project*, then remove the
   `dev/docker/plugins/indikator` mount from `dev/docker-compose.yml`.
4. `docker compose -f dev/docker-compose.yml exec web php artisan october:up`
   to apply any pending migrations against the dump.
5. Frontend logins from the dump use password `1234` (per the anonymised-dump
   convention in the repo README).

## Housekeeping

```powershell
docker compose -f dev/docker-compose.yml down          # stop
docker compose -f dev/docker-compose.yml down -v       # stop + wipe database
docker compose -f dev/docker-compose.yml exec web bash # shell in the web container
```
