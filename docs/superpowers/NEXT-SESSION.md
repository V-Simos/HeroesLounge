# NEXT SESSION — start here

**How to resume:** `@docs/superpowers/NEXT-SESSION.md Continue`

_Last updated: 2026-09-16. Phase 4 (static content wave) is complete on
`ui-rework`. No Phase-4 implementation work remains._

---

## Where things stand

- **Phase 1: ✅ SHIPPED to the open fork PR #1.**
- **Phase 2 Wave 1: ✅ COMPLETE** (public competitive viewing + live-data seed).
- **Phase 3: ✅ COMPLETE** (auth/account/profile, caster schedule, Events
  archive, Guides).
- **Phase 4: ✅ COMPLETE** (every remaining public static surface: FAQ,
  rulesets, schedule, hall of fame, privacy, staff/crew, Division-S). Source
  of truth: `docs/superpowers/PROGRESS.md` → “Task status (Phase 4)”.
- **Current branch:** `ui-rework` (local, ahead of `origin/ui-rework`).
- **Next action:** write the **Phase 5 plan — interactive wave** (team
  create/manage/match, applications, statistics, caster statistics, plus a
  decision on contact/search/timezone/RSS), then implement it task by task.
  Do not improvise it from this file; the Phase 4 plan is the format
  template (`docs/superpowers/plans/2026-09-16-phase-4-static-content.md`).

## What Phase 5 has to cover (frozen old-theme pages still unported)

| Frozen page | URL | Component(s) | Notes |
|---|---|---|---|
| `team/create.htm` | `/team/create` | `CreateTeam` | today caught by the `/:slug/:divslug` division route (graceful themed 200) |
| `team/manage.htm` | `/team/manage/:slug` | `ManageTeam` | captain-only; `ViewApps::onSendAccept()` authorization finding applies |
| `team/manageMatch.htm` | `/team/match/:slug` | `ManageMatches` | reschedule / report flows; linked from dashboard + team page literals |
| `application/create.htm` | `/application` | `CreateApp` | linked from the account Applications tab |
| `application/view.htm` | `/application/view/:id` | `ViewApplication` | plugin `Redirect::refresh()` quirk noted in the Phase 2 spec |
| `statistics/.htm` | `/statistics` | `Rikki\LoungeStatistics\Components\Statistics` | orphaned (no inbound links) — decide port vs drop |
| `statistics/hero.htm` | `/statistics/hero/:slug/:season?` | `HeroDetails` | data-blind (`gameparticipation` = 0 rows) |
| `general/casterstatistics.htm` | `/general/casterstatistics` | `CasterStatistics season=23` | caster-gated in the old nav |
| `search.htm` | `/search` | `searchResults` (OFFLINE.SiteSearch, present in the dev image) | spec said drop; no entry point in the new nav — decide |
| `contact.htm` | `/contact` | — | 500s on a missing partial in the old theme; spec says drop |
| `timezone.htm`, `rssfeedxml.htm` | `/timezone`, `/rssfeed.xml` | raw PHP / `RssFeed` | utility endpoints, port verbatim if kept |

## Verified Phase-4 routes

`/faq`, `/privacy-statement`, `/general`, `/general/ruleset`,
`/general/staff`, `/general/playoff-rules`, `/general/seeding-rules`,
`/general/schedule`, `/general/hall-of-fame`, `/division-s-crew`,
`/division-s-standings`, `/division-s-qualifier-standings`,
`/division-s-schedule`, `/division-s-ruleset/division-s-playoffs`,
`/method-mayhem-hots-ruleset`, `/offmeta-maps-ruleset`,
`/aram-league-ruleset` (all 200); `/division-s-ruleset` is a guest 404 by
frozen `is_hidden = 1`. Phase-3 routes (`/user*`, `/events/archive`,
`/guides*`) unchanged.

## Environment resume

Docker containers do not auto-start after reboot (Docker Desktop itself may
need launching first). From the repository root:

```powershell
docker compose -f dev/docker-compose.yml up -d
```

Site: http://localhost:8090. If it shows the old theme:

```powershell
docker compose -f dev/docker-compose.yml exec -T web php artisan theme:use heroeslounge-next
```

After editing `meta/static-pages.yaml` or `meta/menus/*.yaml`, clear the
October cache or RainLab.Pages keeps serving the cached menu/manifest:

```powershell
docker compose -f dev/docker-compose.yml exec -T web php artisan cache:clear
```

The July-2026 production dump is imported and the additive live-data seed is
present. Do **not** run destructive `fixtures:seed` against this database.
Re-seed only the scoped live data when needed:

```powershell
docker compose -f dev/docker-compose.yml exec -T web php artisan fixtures:live-data --force
```

Imported-dump frontend verification account:

- User 41 / sloth 25
- Username `Hapcher`
- Email `Hapcher5166@fakegmail.com`
- Local-only password `dev12345`
- Current local login setting: username

The committed account page must retain `forceSecure = 1`. Local HTTP still
renders because the frozen SlothAccount component drops the parent redirect
response; preserve that behavior rather than changing plugin code or leaving a
temporary property flip.

Full environment details: `dev/README.md`. Static contracts for all phases:
`dev/verify-*.ps1` (all pass as of this update).

## Binding constraints

- Pure frontend re-skin. Preserve frozen URLs, component configuration, data
  bindings, handlers, and behavior.
- Plugins under `plugins/rikki/*` and the old theme
  `themes/HeroesLounge-Theme/` remain frozen.
- Static content bodies (`content/static-pages/*.htm`) are byte-verbatim
  copies of the frozen files except the `layout` line; the theme renders
  their legacy Bootstrap/Froala vocabulary via the scoped `.static-content`
  rules in `pages.css` + the legacy collapse/tab/table behaviors in
  `lounge.js`. Never “fix” the bodies; extend the vocabulary instead.
- Two final-review security findings remain behind frozen handlers:
  `UpcomingMatches` caster apply/retract lacks caller/permission/identity
  authorization, and `ViewApps::onSendAccept()` permits any team member rather
  than enforcing captain authority. Do not treat theme markup as a security
  boundary. Editing either plugin requires separate explicit authorization —
  Phase 5's team-manage/applications work runs straight into this.
- Override directories must be all lowercase. October probes the lowercase
  alias first, while Docker Desktop can hide casing mistakes.
- Keep `components.css`'s reduced-motion block last and preserve inset
  `:focus-visible` treatment on clipped/chamfered controls.
- Commit with `git -c core.fsmonitor=false`; the IDE's git integration can
  otherwise race on `.git/index.lock` (retry once if it does).
- Every implementation commit uses:
  `Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>`.

## Carry-forward production work

- Resolve the two frozen-plugin authorization findings before exposing the
  caster apply/retract or application-accept controls in production. If
  plugin work is not authorized, withhold the affected controls.
- Create or confirm the Indikator.Content category with slug `events` before
  production cutover so `/blog/category/events` resolves with content.
- Keep the old theme's `assets/img/staff/` directory served after cutover
  (or migrate the crew content): the staff/crew bodies embed absolute
  production URLs into it.
- Apply the documented `gameparticipation` schema fix after any dump re-import.
  Per-game/player statistics remain data-blind because the imported table has
  zero rows.
- The frozen team-page and timeline queries retain known N+1/memory risks.
  See PROGRESS.md → “Pre-production hardening”; do not fix them in theme work.
- Decide CSS cache busting and anonymous-page caching before cutover.

## Open decisions

- **ARAM league:** retire (remove `/aram-league-ruleset`,
  `/guides/aram-signup-guide`, the ARAM archive links) or refresh — content
  decision; both pages are ported frozen meanwhile.
- **Editorial content migration:** demote the frozen section `<h1>`s
  (`schedule`, `playoff-rules`, First Game guide), drop the FAQ body's
  duplicate heading, give the crew cards' social links accessible names.
- **Search:** OFFLINE.SiteSearch is installed in the dev image and the old
  sidebar had a search box; the new nav has none. Port `/search` in Phase 5
  or drop it for good.
- **PR #1 / branch integration:** merge now or keep open for review;
  `ui-rework` has not been pushed since Phase 4.

## Don't waste time on

- Re-running Phase 2 Wave 1, Phase 3, or Phase 4 implementation; all complete.
- Fixture-era login accounts on the imported dump; use `Hapcher`.
- Treating missing team-upload files, media-library 404s, or empty
  `gameparticipation` as theme regressions.
- Fixing frozen-plugin performance or redirect behavior in a theme-only task.
- Re-litigating line endings; rework blobs are LF and the CRLF warning is a
  working-tree conversion warning, not corruption.

## Key document map

- `docs/superpowers/PROGRESS.md` — execution tracker and hard-won contracts.
- `docs/superpowers/KNOWN-ISSUES.md` — current issues plus historical audit.
- `docs/superpowers/DB-DUMP-IMPORT.md` — imported-dump and schema-fix record.
- `dev/README.md` — environment, credentials, seeds, and useful URLs.
- `docs/superpowers/plans/2026-09-16-phase-4-static-content.md` — completed
  Phase 4 plan (format template for Phase 5).
- `docs/superpowers/plans/2026-07-24-phase-3-user-auth.md` — completed Phase 3
  plan.
- `docs/superpowers/plans/2026-07-04-phase-2-wave-1-viewing.md` — completed
  Phase 2 Wave 1 plan.
