# NEXT SESSION — start here

**How to resume:** `@docs/superpowers/NEXT-SESSION.md Continue`

_Last updated: 2026-07-11, mid-execution of Phase 2 Wave 1 (Tasks 0–6 done; Task 7 next)._

---

## Where things stand

**Phase 1: ✅ SHIPPED.** Open PR **#1** on the fork (`ui-rework` → `main`, not merged — user's call). Theme `themes/heroeslounge-next` is live/active.

**Phase 2 Wave 1 (public competitive viewing): 🚧 IN EXECUTION.**
- **Mode: subagent-driven** (fresh implementer per task → spec + code-quality/adversarial-multi-lens review → fixes → next task). **Branch: `ui-rework-phase-2`** (off `ui-rework`).
- **Source of truth = `docs/superpowers/PROGRESS.md`** (Phase-2 Wave-1 task table + per-task notes). Read it first.
- Plan: `docs/superpowers/plans/2026-07-04-phase-2-wave-1-viewing.md`.
- **Done + fully reviewed: Tasks 0–6.** 0 (bracket spike — de-risked), 1 (shared match card), 2 (season overview, closed state; reg-open participation deferred), 3 (division page + design rework), 4 (playoff brackets 4a+4b), 5 (match detail 5a+5b), **6 (calendar — just landed: `3a5800f` page, `f7ebaf0` review nits, `f17473c` progress)**.
- **NEXT: Task 7 (team page `/team/view/:slug`).** Then 8 (season archive), 9 (Wave 1 finishing pass). Wave 2 (static content) = a separate later plan.

## The immediate next action — TASK 7 (team page `/team/view/:slug`)

The **highest-risk remaining Wave-1 task.** Plan §"Task 7". First read PROGRESS.md "Notes from Task 6" (UpcomingMatches wiring the team page reuses) + Notes from Tasks 2/3/5 (ViewTeam reuses the same onRender/component-override + onEnd-404 patterns). Key traps from the plan:
1. **camelCase alias casing trap (the primary risk).** `[ViewTeam]` `addComponent()`s SIX camelCase children rendered via `{% component 'alias' %}`: `recentResults`, **`divisionTable` (lowercase — ≠ the existing `partials/DivisionTable/` CAPITAL dir!)**, `upcomingMatches`, `roundMatches`, `timeLine`, `teamStatistics`. Each needs a theme override at `partials/<exact-alias>/default.htm` (case-sensitive FS) or it silently falls back to Bootstrap. Prove each resolves with a temp marker before styling. The lowercase `divisionTable` override can delegate to the existing `DivisionTable` override body.
2. **Guest-vs-auth roster split — PRESERVE.** Page declares NO `[session]` (inherits `security="all"` from layout). Guests see names+roles only; logged-in users see full player cards (battle_tag/discord/heroesprofile). Reuse the calendar/upcoming lesson: the old guest upcoming empty-state derefs `user.username` and BREAKS for guests — ship a clean guest-safe empty state.
3. **Statistics tab = a DataTables mini-app (scope-cut candidate).** Restyle tables WITHOUT DataTables; ship a static current-season table first and DEFER the AJAX season-change + sortable (note the deferral) — exactly as Task 5b dropped DataTables.
4. **Missing icons** (globe/website, discord, battlenet, captain crown) → add to `site/icon.htm` (same additive move as calendar's mic/calendar-plus/calendar-x).
5. `[ViewTeam]` resolves `.team` in `init()` + registers its children; add an `onEnd()` 404 mirroring `blog/post.htm` (as match/view did). Spoiler toggle via `hlToggleSpoilers` + `.score-masked` (as Tasks 3/4/5). Banner wants a full-bleed container (old `plain-fluid`).

Recommended phasing (per plan Step 9): banner + roster + sidebar → matches + timeline → statistics; multi-lens review after, then Task 8.

## Session gotchas carried forward (trust these)
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
