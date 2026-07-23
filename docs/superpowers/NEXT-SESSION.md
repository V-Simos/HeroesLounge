# NEXT SESSION — start here

**How to resume:** `@docs/superpowers/NEXT-SESSION.md Continue`

_Last updated: 2026-07-24. **Live-data seed DONE — the dev site now shows live data** (`fixtures:live-data`, verified by HTTP sweep). Phase 2 Wave 1 COMPLETE (Tasks 0–9). Next: `finishing-a-development-branch` decision (merge/PR) + Wave 2 planning._

---

## Live data (NEW 2026-07-24)

The dev site now renders **populated** live pages on top of the imported dump:
division rounds + standings (`/eu-season-30/division-1`), calendar
(`/calendar`), NMMR3 (`/NMMR3/3NMMRO`), team pages, homepage widgets
(next-match hero, results ticker, match grid), and blog/events
(`/blog/category/events`). Re-seed anytime with:

```
docker compose -f dev/docker-compose.yml exec -T web php artisan fixtures:live-data --force
```

(~30 min, **additive, safe** — cleans only its own output; see `dev/README.md`
§ "Live-data seed" and PROGRESS.md § "Live-data seed" for the gotchas.)
Per-game/player **stats remain blind** (`gameparticipation` = 0 rows, dump
limitation).

## Where things stand

**Phase 1: ✅ SHIPPED.** Open PR **#1** on the fork (`ui-rework` → `main`, not merged — user's call). Theme `themes/heroeslounge-next` is live/active.

**Phase 2 Wave 1 (public competitive viewing): ✅ COMPLETE.**
- **Mode: subagent-driven** (fresh implementer per task → spec + code-quality/adversarial-multi-lens review → fixes → next task). **Branch: `ui-rework-phase-2`** (off `ui-rework`).
- **Source of truth = `docs/superpowers/PROGRESS.md`** (Phase-2 Wave-1 task table + per-task notes). Read it first.
- Plan: `docs/superpowers/plans/2026-07-04-phase-2-wave-1-viewing.md`.
- **Done + fully reviewed: Tasks 0–9.** 0 (bracket spike), 1 (shared match card), 2 (season overview), 3 (division page + rework), 4 (playoff brackets), 5 (match detail), 6 (calendar), 7 (team page — incl. the critical `divisionTable` override-casing fix), 8 (season archive), **9 (finishing pass — cross-cutting QA/regression sweep at 360/768/1200px; 0 re-skin defects; every candidate finding faithful-to-frozen / dev-artifact / expected-deferred; docs-only commit)**.
- **NEXT: no Wave-1 work remains.** Decide `finishing-a-development-branch` (merge `ui-rework-phase-2` → `ui-rework`, or open a PR). Then Wave 2 (static content) = a separate later plan to be written.

## The immediate next action — Wave 1 sign-off + what's next

Wave 1 is **done and merged**. State + what remains:
1. **Branch integration — ✅ Wave 1 MERGED into `ui-rework`** (fast-forward, LOCAL only; both `ui-rework` and `ui-rework-phase-2` are at the same tip, **NOT pushed**). Remaining user call: push and open/refresh a PR (PR #1) when ready.
2. **Wave 2 (static content)** — a separate later plan, not yet written (rules/FAQ/guides/staff/ARAM etc.). Blocked on the **ARAM league decision** (see "Open decisions" below) and on writing the plan. Continue Wave-2 work on `ui-rework-phase-2` (or a fresh branch off `ui-rework`).

**Carry-forward from the Task 9 sweep (all documented in PROGRESS "Notes from Task 9" — not blockers):**
- **Real-dump URLs (fixture-era `/season-30` is GONE):** use `/eu-season-23` (season, closed), `/eu-season-23/division-1`, `/tournament/nut-cup` (de16), `/eu-season-23/playoff/Division%201%20Cup` (se16 in-season), `/tournament/group-stage-eu-aram-2` (group+knockout), `/tournament/nexus-rumble-v` (reg_open), `/match/view/1061` + `/match/view/21642`, `/calendar`, `/team/view/AO` (light) or `/team/view/DOF` (heavy ~72s), `/season/archive`.
- **`/team/create` shadows into the division route** `/:slug/:divslug` (renders themed "Unknown division" 200) until Phase 3 ports `pages/team/create.htm` — expected, graceful, reclaimed then.
- **PERF worst offender = team page** (`/team/view/DOF`, 218 matches, ~72s frozen N+1) — add to the plugin-side pre-production hardening list.
- **DB-mutation TZ test recipe** (if re-checking calendar TZ): force a couple matches to boundary-crossing UTC times AND `winner_id=NULL` (the `type=all` filter is `winner_id IS NULL`, not is_played), then RESTORE (data-only). All values were restored this session.

## Session gotchas carried forward (trust these)
- **Component-override dirs must be ALL-LOWERCASE (Task-7 learning).** October's `ComponentPartial::loadOverrideCached` probes `partials/strtolower(alias)/default.htm` BEFORE `partials/<exact-alias>/default.htm`. A CamelCase override dir only resolves when the invoking alias is byte-identical; any lowercase RUNTIME alias added via `addComponent()` (e.g. `divisionTable` from ViewTeam + PlayoffOverview) then silently falls back to the plugin's Bootstrap partial on case-sensitive prod Linux. **Dev's Docker-Desktop bind mount is case-INSENSITIVE even inside the Linux container, so this is INVISIBLE in dev** — prove casing with `git ls-files`, NEVER a live render. Fixed in Task 7 (`1421a61`) by renaming `partials/DivisionTable/` → `partials/divisiontable/` (one lowercase dir serves every alias casing). Name all new override dirs lowercase.
- **Git index.lock race:** an IDE/`git fsmonitor--daemon` intermittently grabs `.git/index.lock`, failing commits with "index.lock: File exists". Commit with **`git -c core.fsmonitor=false`** (and `rm -f .git/index.lock` only if no real git op is running). Do NOT kill the fsmonitor daemon — it's legitimate.
- **DB column names:** matches use **`div_id`** (not `division_id`), table `rikki_heroeslounge_match`; team↔division pivot `rikki_heroeslounge_team_division` also uses `div_id`; timeline table is singular `rikki_heroeslounge_timeline`.
- **Real DB dump: the July-2026 dump (`hl_test_data_dump_07_2026.sql`) is now the imported one (since 2026-07-21)** — active seasons `eu-season-30` + `NMMR3`; the May-2024 dump/`eu-season-23`-active era is over (eu-season-23 remains browsable as a closed season). `fixtures:seed` is **SUPERSEDED** — do NOT reseed with it. Import record + the **`gameparticipation` schema fix that MUST be re-applied after any re-import/`down -v`**: `docs/superpowers/DB-DUMP-IMPORT.md` + the `importing-prod-dump-into-dev` notes. Dump matches are past-dated, but — **since 2026-07-24** — `fixtures:live-data` seeds current/future-dated matches, so calendar/upcoming are populated (see "Live data" above).
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
Full resume steps + credentials: `dev/README.md` and PROGRESS.md § "Resuming a session". NOTE the fixture-era frontend logins (`alphacap@dev.local`) are GONE with the real dump; dashboard/team-manage verification needs a real-dump user with a password set via tinker (see PROGRESS.md Task-7 notes). The July-2026 dump is imported (active seasons `eu-season-30`/`NMMR3`, live-data seeded) — see the DB-dump note above (re-apply the gameparticipation schema fix after any re-import).

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
