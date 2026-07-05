# NEXT SESSION — start here

**How to resume:** `@docs/superpowers/NEXT-SESSION.md Continue`

_Last updated: 2026-07-05, end of the planning session._

---

## Where things stand

**Phase 1 (public site design system + homepage + dashboard + blog + maintenance): ✅ SHIPPED.**
- All 11 tasks built + spec-reviewed + quality-reviewed + a final whole-theme review (subagent-driven).
- Open PR **#1** on the fork: https://github.com/V-Simos/HeroesLounge/pull/1 (`ui-rework` → `main`, same-repo, NOT upstream). Not merged yet — user's call.
- Theme `themes/heroeslounge-next` is the live/active theme on the dev site.

**Phase 2 (public competitive viewing + static content re-skin): 📋 PLANNED, not started.**
- Scope decided with the user; **account/auth + team management deferred to Phase 3**; statistics/contact/search dropped; ARAM parked (see Open decisions).
- Spec (approved): `docs/superpowers/specs/2026-07-04-phase-2-public-reskin-design.md`
- **Wave 1 plan (approved, ready to execute):** `docs/superpowers/plans/2026-07-04-phase-2-wave-1-viewing.md` — 10 tasks: bracket spike → match-card → season → division → playoff brackets → match detail → calendar → team page → season archive → finishing pass.
- Wave 2 (static content: rules/guides/FAQ/legal/hall-of-fame/Division-S) = a **separate later plan**, not yet written.

## The immediate next action

**Execute Wave 1** using the **`superpowers:subagent-driven-development`** skill (same process as Phase 1: fresh implementer per task → spec-compliance review → code-quality review → fixes re-reviewed → next task; final whole-wave review at the end).

Two things to settle with the user first (both were pending when this session ended):
1. **Execution mode** — subagent-driven (recommended) vs inline. Not yet chosen.
2. **Branch** — recommended: create `ui-rework-phase-2` off `ui-rework` so Wave 1 doesn't pile onto PR #1. Not yet created.

Then start at **Task 0 (bracket spike)** — it de-risks the highest-uncertainty piece (playoff brackets) before the rest of the wave.

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
