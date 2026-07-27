# NEXT SESSION — start here

**How to resume:** `@docs/superpowers/NEXT-SESSION.md Continue`

_Last updated: 2026-07-28. Phase 2 Wave 1 and Phase 3 are complete on
`ui-rework-phase-2`. No Phase-3 implementation work remains._

---

## Where things stand

- **Phase 1: ✅ SHIPPED to the open fork PR #1.**
- **Phase 2 Wave 1: ✅ COMPLETE and fully reviewed.** Public competitive
  viewing surfaces, including the live-data seed, are merged into the working
  branch history.
- **Phase 3: ✅ COMPLETE.** Auth/account/profile, caster schedule, Events
  archive/nav behavior, and seven Guides surfaces have passed the finishing
  static/server/browser sweep. Source of truth:
  `docs/superpowers/PROGRESS.md` → “Task status (Phase 3)”.
- **Current branch:** `ui-rework-phase-2`.
- **Next action:** use `finishing-a-development-branch` to decide whether to
  merge/open a PR, then write a separate plan for the remaining static and
  interactive surfaces. Do not improvise a new wave directly from this file.

Phase 3 sources:

- Spec: `docs/superpowers/specs/2026-07-24-phase-3-user-auth-design.md`
- Plan: `docs/superpowers/plans/2026-07-24-phase-3-user-auth.md`
- Finishing report:
  `.superpowers/sdd/2026-07-24-phase-3-user-auth/task-8-report.md`

## Verified Phase-3 routes

- `/user/forgotpassword`
- `/user`
- `/user/view/25`
- `/user/casterschedule`
- `/events/archive`
- `/guides`
- `/guides/signup-guide`
- `/guides/captains-guide`
- `/guides/scheduling-and-reporting-matches`
- `/guides/scheduling-and-playing-your-first-game`
- `/guides/uploading-replays`
- `/guides/aram-signup-guide`

The First Game guide's frozen source contains six `<h1>` elements. This is
the explicitly approved sole Phase-3 one-`<h1>` exception under Task 7's
byte-verbatim fidelity requirement, plus recorded content-migration/a11y debt;
do not silently rewrite it or add the plan typo's `/guide/.../frist` alias.

## Environment resume

Docker containers do not auto-start after reboot. From the repository root:

```powershell
docker compose -f dev/docker-compose.yml up -d
```

Site: http://localhost:8090. If it shows the old theme:

```powershell
docker compose -f dev/docker-compose.yml exec -T web php artisan theme:use heroeslounge-next
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

Full environment details: `dev/README.md`.

## Binding constraints

- Pure frontend re-skin. Preserve frozen URLs, component configuration, data
  bindings, handlers, and behavior.
- Plugins under `plugins/rikki/*` and the old theme
  `themes/HeroesLounge-Theme/` remain frozen.
- Two final-review security findings remain behind frozen handlers:
  `UpcomingMatches` caster apply/retract lacks caller/permission/identity
  authorization, and `ViewApps::onSendAccept()` permits any team member rather
  than enforcing captain authority. Do not treat theme markup as a security
  boundary. Editing either plugin requires separate explicit authorization.
- Override directories must be all lowercase. October probes the lowercase
  alias first, while Docker Desktop can hide casing mistakes.
- Keep `components.css`'s reduced-motion block last and preserve inset
  `:focus-visible` treatment on clipped/chamfered controls.
- Commit with `git -c core.fsmonitor=false`; the IDE/fsmonitor can otherwise
  race on `.git/index.lock`.
- Every implementation commit uses:
  `Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>`.

## Carry-forward production work

- Resolve the two frozen-plugin authorization findings before exposing the
  caster apply/retract or application-accept controls in production. The
  authorized backend change must derive/validate the acting user, enforce the
  relevant permission or captain role, validate object ownership, and include
  request-level negative tests. If plugin work is not authorized, withhold the
  affected controls instead.
- Create or confirm the Indikator.Content category with slug `events` before
  production cutover so `/blog/category/events` resolves with content.
- Apply the documented `gameparticipation` schema fix after any dump re-import.
  Per-game/player statistics remain data-blind because the imported table has
  zero rows.
- The frozen team-page and timeline queries retain known N+1/memory risks.
  See PROGRESS.md → “Pre-production hardening”; do not fix them in theme work.
- Decide CSS cache busting and anonymous-page caching before cutover.

## Open decisions

- **ARAM league:** if concluded, remove/retire the ARAM guide and nav target in
  a separately approved content migration; if active, refresh it in the next
  static-content plan. Phase 3 preserves the frozen page meanwhile.
- **First Game heading hierarchy:** editorial/content owner must approve
  semantic demotion of the six source `<h1>` elements.
- **PR #1 / branch integration:** merge now or keep open for review.
- **Next wave scope:** remaining FAQ/contact/rulesets/privacy/staff/static
  content versus team create/manage/match/statistics/search interactive work.

## Don't waste time on

- Re-running Phase 2 Wave 1 or Phase 3 implementation; both are complete.
- Fixture-era login accounts on the imported dump; use `Hapcher`.
- Treating missing team-upload files or empty `gameparticipation` as theme
  regressions.
- Fixing frozen-plugin performance or redirect behavior in a theme-only task.
- Re-litigating line endings; rework blobs are LF and the CRLF warning is a
  working-tree conversion warning, not corruption.

## Key document map

- `docs/superpowers/PROGRESS.md` — execution tracker and hard-won contracts.
- `docs/superpowers/KNOWN-ISSUES.md` — current issues plus historical audit.
- `docs/superpowers/DB-DUMP-IMPORT.md` — imported-dump and schema-fix record.
- `dev/README.md` — environment, credentials, seeds, and useful URLs.
- `docs/superpowers/plans/2026-07-04-phase-2-wave-1-viewing.md` — completed
  Phase 2 Wave 1 plan.
- `docs/superpowers/plans/2026-07-24-phase-3-user-auth.md` — completed Phase 3
  plan.
