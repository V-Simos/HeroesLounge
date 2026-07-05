# NEXT SESSION — start here

**How to resume:** `@docs/superpowers/NEXT-SESSION.md Continue`

_Last updated: 2026-07-05, mid-execution of Phase 2 Wave 1._

---

## Where things stand

**Phase 1: ✅ SHIPPED.** Open PR **#1** on the fork (`ui-rework` → `main`, not merged — user's call). Theme `themes/heroeslounge-next` is live/active.

**Phase 2 Wave 1 (public competitive viewing): 🚧 IN EXECUTION.**
- **Mode: subagent-driven** (fresh implementer per task → spec-compliance review → code-quality review → fixes → next task). **Branch: `ui-rework-phase-2`** (off `ui-rework`; both decisions made with the user this session).
- **Source of truth = `docs/superpowers/PROGRESS.md`** (Phase-2 Wave-1 task table + per-task notes). Read it first.
- Plan: `docs/superpowers/plans/2026-07-04-phase-2-wave-1-viewing.md`.
- **Done + fully reviewed:** Task 0 (bracket spike — de-risked; playoff fixtures now seeded), Task 1 (shared match card), Task 2 (season overview, closed state; reg-open participation deferred).
- **Task 3 (division page): IMPLEMENTED + spec-review PASSED, but code-quality review NOT yet run.** 4 commits landed (`edbc0bd`,`1c4f677`,`8e7bdd1`,`5ab67d6`), verified live on `/season-30/division-1`.
- **Not started:** Tasks 4–9 (playoff brackets → match detail → calendar → team page → season archive → finishing pass).

## The immediate next action — RESUME TASK 3'S CODE-QUALITY REVIEW

Task 3 is mid-review-gate. Do this, in order:
1. **Dispatch the Task-3 code-quality reviewer** (`superpowers:code-reviewer`, BASE `cbca1ea` → HEAD `5ab67d6`), focusing on: the 6 partials' organization, the timeline per-type switch maintainability, CSS quality/placement, N+1 traps, AND a **recommendation on the timeline spoiler-leak** (see PROGRESS "Notes from Task 3" open item #1 — `Match.Played` scores render unmasked while spoilers off; decide whether to wrap them in `.score-masked` for consistency vs faithful replication).
2. Apply fixes (batch: the spoiler-leak decision + pages.css reduced-motion ordering + the "No active teams yet" wording — all in PROGRESS Notes from Task 3), re-verify live, commit.
3. Mark Task 3 done in PROGRESS; then continue the subagent-driven loop at **Task 4 (playoff brackets)** — which *productionizes the Task-0 spike* (kept `partials/PlayoffOverview/default.htm` is the proven foundation; port the spoiler callback from the old theme's `showHideSpoilersPlayoffView`/`showHideSpoilersSeasonPlayoff` — see PROGRESS Notes from Task 0).

## Session gotchas carried forward (trust these)
- **Git index.lock race:** an IDE/`git fsmonitor--daemon` intermittently grabs `.git/index.lock`, failing commits with "index.lock: File exists". Commit with **`git -c core.fsmonitor=false`** (and `rm -f .git/index.lock` only if no real git op is running). Do NOT kill the fsmonitor daemon — it's legitimate.
- **DB column names:** matches use **`div_id`** (not `division_id`), table `rikki_heroeslounge_match`; team↔division pivot `rikki_heroeslounge_team_division` also uses `div_id`; timeline table is singular `rikki_heroeslounge_timeline`.
- **Playoff fixtures now exist** (seeded via the dev plugin in Task 0): se8 `season-30-playoffs` (id 1) + de8 `community-cup` (id 2), both on Season 30. `fixtures:seed --force` is idempotent.
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
Full resume steps + credentials: `dev/README.md` and PROGRESS.md § "Resuming a session". Frontend login is by **email** (`alphacap@dev.local` / `dev12345`); dashboard/team pages need the theme-switch login dance (see PROGRESS.md). Real team DB dump still pending — fixture verification only.

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
