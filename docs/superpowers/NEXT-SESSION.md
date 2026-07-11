# NEXT SESSION — start here

**How to resume:** `@docs/superpowers/NEXT-SESSION.md Continue`

_Last updated: 2026-07-11, mid-execution of Phase 2 Wave 1 (Tasks 0–7 done; Task 8 next)._

---

## Where things stand

**Phase 1: ✅ SHIPPED.** Open PR **#1** on the fork (`ui-rework` → `main`, not merged — user's call). Theme `themes/heroeslounge-next` is live/active.

**Phase 2 Wave 1 (public competitive viewing): 🚧 IN EXECUTION.**
- **Mode: subagent-driven** (fresh implementer per task → spec + code-quality/adversarial-multi-lens review → fixes → next task). **Branch: `ui-rework-phase-2`** (off `ui-rework`).
- **Source of truth = `docs/superpowers/PROGRESS.md`** (Phase-2 Wave-1 task table + per-task notes). Read it first.
- Plan: `docs/superpowers/plans/2026-07-04-phase-2-wave-1-viewing.md`.
- **Done + fully reviewed: Tasks 0–7.** 0 (bracket spike), 1 (shared match card), 2 (season overview, closed state), 3 (division page + rework), 4 (playoff brackets), 5 (match detail), 6 (calendar), **7 (team page — just landed: `dca6be1`/`fba5068`/`92b9510` build + `1421a61` review fixes, incl. the critical `divisionTable` override-casing fix)**.
- **NEXT: Task 8 (season archive `/season/archive`).** Then 9 (Wave 1 finishing pass). Wave 2 (static content) = a separate later plan.

## The immediate next action — TASK 8 (season archive `/season/archive`)

The **lowest-complexity remaining Wave-1 task** ("free-rider"). Plan §"Task 8". First read PROGRESS.md "Notes from Task 2" — Task 8 REUSES the shared `partials/season/overview.htm` (divisions/playoffs link lists, param-driven on `season` only) built there. Key points from the plan:
1. **NO components — pure page `onStart()` data binding.** Front-matter query VERBATIM from the old page: `Season::where('type',1)->with('divisions','playoffs')->where('is_active',0)->orderBy('created_at','desc')->get()->groupBy('region_id')` (use the correct namespace `\Rikki\Heroeslounge\Models\Season`).
2. **Replace the Bootstrap accordion with semantic `<details>/<summary>` + minimal CSS — NO new JS.** Per region `<h2>{{ season.region.title }}</h2>`; per season `<details><summary>{{ season.title }}</summary>` + `{% partial 'season/overview' season=season %}`.
3. **region_id `groupBy` is insertion-order (non-deterministic)** — sort regions explicitly if order matters.
4. If a `summary` gets a `clip-path`/`.chamfer`, add it to the focus-affordance list. Empty DB → muted "No archived seasons." (fixture DB may have few/none — note it). Empty division/playoff sections are already hidden by the `season/overview` guards.

Then Task 9 (Wave 1 finishing pass — responsive/keyboard/reduced-motion/tz/console sweep across all Wave-1 pages). Wave 2 (static content) = a separate later plan.

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
