# NEXT SESSION — start here

**How to resume:** `@docs/superpowers/NEXT-SESSION.md Continue`

_Last updated: 2026-07-11, mid-execution of Phase 2 Wave 1 (Tasks 0–8 done; Task 9 — Wave 1 finishing pass — next)._

---

## Where things stand

**Phase 1: ✅ SHIPPED.** Open PR **#1** on the fork (`ui-rework` → `main`, not merged — user's call). Theme `themes/heroeslounge-next` is live/active.

**Phase 2 Wave 1 (public competitive viewing): 🚧 IN EXECUTION.**
- **Mode: subagent-driven** (fresh implementer per task → spec + code-quality/adversarial-multi-lens review → fixes → next task). **Branch: `ui-rework-phase-2`** (off `ui-rework`).
- **Source of truth = `docs/superpowers/PROGRESS.md`** (Phase-2 Wave-1 task table + per-task notes). Read it first.
- Plan: `docs/superpowers/plans/2026-07-04-phase-2-wave-1-viewing.md`.
- **Done + fully reviewed: Tasks 0–8.** 0 (bracket spike), 1 (shared match card), 2 (season overview, closed state), 3 (division page + rework), 4 (playoff brackets), 5 (match detail), 6 (calendar), 7 (team page — incl. the critical `divisionTable` override-casing fix), **8 (season archive — just landed: `b8906e0`; adversarial multi-lens review 0 findings; native `<details>/<summary>` re-skin reusing the Task-2 shared `season/overview.htm`)**.
- **NEXT: Task 9 (Wave 1 finishing pass).** Then Wave 1 is complete. Wave 2 (static content) = a separate later plan.

## The immediate next action — TASK 9 (Wave 1 finishing pass)

The **last Wave-1 task** — a cross-cutting sweep (mirrors Phase-1 Task 11) across **all** Wave-1 pages (season overview, division, playoff brackets, match detail, calendar, team, season archive). Plan §"Task 9". This is NOT new page-building; it's a QA/polish + regression pass, then a PROGRESS.md wrap-up. The plan's 8 steps:
1. **Responsive sweep** at 360 / 768 / 1200px: division 2-col→1-col, bracket scroll wrapper, calendar rows, team banner, match-detail game tabs, standings `.tr` name column at 360px.
2. **Keyboard / focus:** tab through nav, all `.tabs`, `<details>` (archive), bracket node links, spoiler switch, caster buttons; confirm every new focusable `.chamfer`/`clip-path` element is in the focus-affordance list with a visible inset ring.
3. **prefers-reduced-motion:** spoiler-reveal + bracket + any new animation static; confirm the reduced-motion block stayed LAST in components.css.
4. **Timezone correctness:** calendar / division-upcoming / match-detail times reflect viewer TZ (change browser TZ, confirm a time AND a calendar date-group boundary shift); `SetTimezone` still on the layout.
5. **Console + log hygiene** across every Wave-1 page (browser console + `storage/logs` clean of theme Twig errors; the known logo-404 + Division.php INFO noise are pre-existing dev-data artifacts, not defects).
6. **Cross-link resolution:** every new nav/cross-link emits a non-empty `href`; Wave-1 targets 200; deferred/dropped targets (`/user/view/:id`, `/team/create`, `/team/match/:slug`) are literal frozen URLs that 404 under the new theme (expected until Phase 3) — no `href=""` anywhere.
7. **Update PROGRESS.md + report Wave 1 done** — record all fixture-blind items pending a complete DB dump (bracket types not in the dump, per-game statistics + pre-game rosters [`gameparticipation`=0 rows], team hero-pick/winrate stats, real replay files, latin-ext roster glyphs, team logos) and deferred sub-items (match-detail sortable tables, team Statistics AJAX season-change, calendar caster-request AJAX-fragment skin, season reg-open participation override, the theme-wide `[data-tabs]` a11y sweep). Note Wave 2 is its own later plan; the ARAM item stays parked.
8. **Commit** — `feat(theme-next): wave 1 finishing pass`.

**Note on the DB clock caveat:** the real dump's matches are all past-dated vs the container clock, so time-windowed views (calendar / division-upcoming) render naturally empty — for TZ + populated-render checks, temporarily force a few matches forward as done in Task 6 (see PROGRESS "Notes from Task 6"), and RESTORE the DB values afterward (data-only, no code).

Wave 2 (static content) = a separate later plan.

## Session gotchas carried forward (trust these)
- **Component-override dirs must be ALL-LOWERCASE (Task-7 learning).** October's `ComponentPartial::loadOverrideCached` probes `partials/strtolower(alias)/default.htm` BEFORE `partials/<exact-alias>/default.htm`. A CamelCase override dir only resolves when the invoking alias is byte-identical; any lowercase RUNTIME alias added via `addComponent()` (e.g. `divisionTable` from ViewTeam + PlayoffOverview) then silently falls back to the plugin's Bootstrap partial on case-sensitive prod Linux. **Dev's Docker-Desktop bind mount is case-INSENSITIVE even inside the Linux container, so this is INVISIBLE in dev** — prove casing with `git ls-files`, NEVER a live render. Fixed in Task 7 (`1421a61`) by renaming `partials/DivisionTable/` → `partials/divisiontable/` (one lowercase dir serves every alias casing). Name all new override dirs lowercase.
- **Git index.lock race:** an IDE/`git fsmonitor--daemon` intermittently grabs `.git/index.lock`, failing commits with "index.lock: File exists". Commit with **`git -c core.fsmonitor=false`** (and `rm -f .git/index.lock` only if no real git op is running). Do NOT kill the fsmonitor daemon — it's legitimate.
- **DB column names:** matches use **`div_id`** (not `division_id`), table `rikki_heroeslounge_match`; team↔division pivot `rikki_heroeslounge_team_division` also uses `div_id`; timeline table is singular `rikki_heroeslounge_timeline`.
- **Real May-2024 DB dump LANDED (2026-07-08)** — site runs on real data (active season `eu-season-23`), rich in playoffs/matches. `fixtures:seed` is **SUPERSEDED** (and incompatible with the uncommitted indikator-shim edits) — do NOT reseed. Import record, gaps, and the **Task-5 `gameparticipation` schema fix that MUST be re-applied after any re-import/`down -v`**: `docs/superpowers/DB-DUMP-IMPORT.md`. Caveat: dump matches are **past-dated** vs the container clock, so time-windowed views (calendar/upcoming) render empty unless matches are temporarily forced forward (see PROGRESS "Notes from Task 6").
- Every commit uses the `Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>` trailer (implementer work) / `Claude Opus 4.8 (1M context)` (my PROGRESS commits) per repo convention.

## Binding constraints (do NOT re-litigate)

- **Pure frontend re-skin.** Every page replicates its old-theme components, `[session]` config, data bindings, and **frozen URL** — only Twig markup + CSS + presentation JS change.
- **Plugins are FROZEN** (`plugins/rikki/*`, rainlab, the `indikator` shim) and so is `themes/HeroesLounge-Theme/`. Read/copy from them; never edit them; replicate plugin bugs, don't fix them.
- Established patterns/gotchas live in `docs/superpowers/PROGRESS.md` (onRender pattern, DivisionTable override reuse, alias-casing traps, spoiler/`heroeslounge.css` clobber, chamfer inset focus-ring, reduced-motion end-block, literal-URL rule). The Wave 1 plan's "Crash course / conventions" section restates the load-bearing ones.

## Environment resume

Docker containers don't auto-start after reboot. From repo root:
```
docker compose -f dev/docker-compose.yml up -d
```
Site: http://localhost:8090 (should show the new theme). If it shows the OLD theme:
```
docker compose -f dev/docker-compose.yml exec -T web php artisan theme:use heroeslounge-next
```
Full resume steps + credentials: `dev/README.md` and PROGRESS.md § "Resuming a session". Frontend login is by **email** (`alphacap@dev.local` / `dev12345`); dashboard/team pages need the theme-switch login dance (see PROGRESS.md). Real May-2024 DB dump is imported (active season `eu-season-23`) — see the DB-dump note above (matches past-dated; re-apply the gameparticipation schema fix after any re-import).

## Open decisions (for the user)

- **ARAM league** — still running? If concluded → drop `/aram-league-ruleset` + `/guides/aram-signup-guide` and remove the ARAM nav link. If active → refresh + port in Wave 2. Until answered, both stay on the old theme via literal URL (zero work). Non-blocking for Wave 1.
- **PR #1** — merge to fork `main` now, or keep open for review.

## Don't waste time on (already resolved)

- **Line endings:** a Task-11 concern claimed the repo was all CRLF. VERIFIED FALSE at byte level (`git ls-files --eol` + `xxd`): all rework blobs are LF, matching upstream. The `git cat-file | grep -c $'\r'` check that raised it is a false positive. No remediation needed.

## Map of the key docs

- `docs/superpowers/PROGRESS.md` — execution tracker (Phase 1 done; Phase 2 Wave 1 table added) + hard-won gotchas.
- `docs/superpowers/specs/2026-07-04-phase-2-public-reskin-design.md` — Phase 2 scope/constraints.
- `docs/superpowers/plans/2026-07-04-phase-2-wave-1-viewing.md` — the plan to execute now.
- `docs/superpowers/specs/2026-07-03-ui-ux-rework-design.md` + `plans/2026-07-03-ui-rework-phase-1.md` — Phase 1 (done; reference for style/patterns).
- `docs/superpowers/specs/assets/{reference-design,dashboard-v2}.html` — design North Star (v2 tokens authoritative).
