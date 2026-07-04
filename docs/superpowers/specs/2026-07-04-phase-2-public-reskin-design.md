# Heroes Lounge UI Rework — Phase 2 Design: Public Site Re-skin

**Status:** approved in brainstorming 2026-07-04; pending spec review + user sign-off.
**Predecessors:** Phase 1 spec `docs/superpowers/specs/2026-07-03-ui-ux-rework-design.md`; Phase 1 plan `docs/superpowers/plans/2026-07-03-ui-rework-phase-1.md`; execution tracker `docs/superpowers/PROGRESS.md`.
**Theme under construction:** `themes/heroeslounge-next` (built in Phase 1).

---

## 1. Goal

Extend the `heroeslounge-next` theme to cover the **public, read-only** surface of the site — competitive viewing (seasons, divisions, playoff brackets, matches, calendar, team pages) and static content (rules, guides, legal, hall of fame, Division-S) — so the reworked design reaches the pages users actually browse, and the largest set of currently-404ing nav/footer links resolves under the new theme.

The **logged-in half** of the site (account/auth, team management, applications, caster tooling) is explicitly **out of scope for Phase 2** and deferred to Phase 3.

## 2. Binding constraint — pure re-skin (frontend only)

**Phase 2 changes presentation only.** This is the same discipline Phase 1 followed, restated as a hard rule because the user emphasized it:

- For every ported page, replicate the old theme's **component wiring** (`[Component]` front-matter + `{% component %}` renders), **`[session]` access configuration**, **data bindings**, and **frozen URL** — changing only the Twig **markup**, the **CSS**, and **presentation JavaScript**.
- **Nothing** under `plugins/` (rikki.*, rainlab.*, the `Indikator.Content` dev shim), no migrations, no backend logic, no access-control changes, no new endpoints.
- Plugin bugs are **replicated as-is, not fixed** (e.g. the `application/view` `Redirect::refresh()` quirk the scoping pass noted — irrelevant here anyway, as applications are Phase 3).
- The `themes/HeroesLounge-Theme` legacy theme is never modified; reading/copying from it is allowed.

Consequence for access-gated pages: we do **not** decide whether a page like `/team/view` is public — we port it with whatever `[session]` gating it already declares, restyled. RainLab.User sessions are shared across the active theme, so pages ported here still work for users who logged in via the (Phase-3-deferred) old-theme account pages.

## 3. Scope

### 3.1 INCLUDE — Wave 1: Public competitive viewing (read-only, dynamic)

All driven by existing `rikki.*` components via the Phase-1 `{% do Component.onRender() %}` pattern (or plain component attach where the component populates on `onRun`). URLs frozen; each is re-verified against the old page's `url =` front-matter at port time.

| Page | URL (verified from old theme) | Primary components | Complexity |
|---|---|---|---|
| Season overview | `/:slug` | SeasonOverview | medium |
| Division page | `/:slug/:divslug` | DivisionOverview, DivisionTable (existing override), RoundMatches, TimelineEntries | high |
| Playoff (in-season) | `/:season-slug/playoff/:playoff-title` | PlayoffOverview | high |
| Playoff (standalone) | `/tournament/:playoff-title` | PlayoffOverview | high |
| Match detail | `/match/view/:id` | ViewMatch | medium |
| Calendar | `/calendar` | UpcomingMatches (global) | medium |
| Team page | `/team/view/:slug` | Team + stats + roster + timeline (access preserved) | high |
| Season archive (free-rider) | `/season/archive` | season list | low |

### 3.2 INCLUDE — Wave 2: Static content (RainLab.Pages / Indikator shim)

Content pages via the `[staticPage]` mechanism already wired into the Phase-1 `default` layout. No components; the work is markup + `pages.css` prose/section styling (reuse the Phase-1 `.post-content` typography + the blog design language). Exact frozen URLs are captured from each page's front-matter at port time.

- **Rules:** general ruleset, playoff rules, tournament ruleset
- **Legal:** privacy statement
- **Guides:** guides index + signup guide + scheduling/reporting + uploading replays + captain's guide *(exact member set + URLs reconciled at port time — the content dir contains a likely-superseded "…playing-your-frist-game" duplicate to dedupe against)*
- **Records:** general hall of fame
- **Help:** FAQ (`/faq` — a live `pages/faq.htm` rendering the `faqpage.htm` content; the Phase-1 footer already links `/faq`, so it 404s until ported)
- **Ops:** seeding rules (file `general-test.htm` — misleading name), season schedule
- **Division-S:** crew, ruleset (+ playoffs), qualifier standings, schedule, standings *(the last two are hand-maintained collapsible/tabbed tables — the biggest static-content lift; reuse lounge.js tabs / a collapse behavior)*
- **Events:** `/events/archive` *(see Open Items — may need a theme-level static-menu definition; if that can't stay purely presentational, keep it on the old theme via a literal link)*

### 3.3 DEFER → Phase 3

Account/auth (`/user/:code?`, `/user/view/:id`, forgot-password), team create/manage/match, applications, caster tooling (`/user/casterschedule`, `/general/casterstatistics`), the RSS feed, and `/timezone` (an account-adjacent JS-invoked session-timezone endpoint, not a designed page; nothing in the new theme links it). These stay on the old theme, reached via the literal-URL rule, until Phase 3.

### 3.4 DROP — not ported

- **Retired utilities:** `/contact` (500s on a missing partial) → point users to Discord (already linked site-wide); `/search` (needs the absent `OFFLINE.SiteSearch` plugin) → dropped. Neither appears in the Phase-1 nav.
- **Statistics:** `/statistics`, `/statistics/hero/*` — orphaned (no inbound links); dropped for now.
- **Verifiably dead (scoping pass, grep-evidenced):** `/ext-div/:id` (hidden staff tool), `/general/nacasterstatistics` (hardcoded S17 dupe), the 2019-era **old `/divisionS/*` CMS pages** (`pages/divisionS/{crew,general,standings,ruleset,schedule}.htm` — distinct from, and superseded by, the live `content/static-pages/division-s-*` files ported in Wave 2), `/general/rules`, `/general/staffpage`, `/general`, `/divisionsoverview` (all hidden/superseded), `/testriggingpage` (dev artifact), Method Mayhem + Heroes Cup pages (concluded events).

### 3.5 Open items (non-blocking)

- **ARAM league:** `/aram-league-ruleset` + `/guides/aram-signup-guide` reference the concluded "Offseason 13-14". **User to confirm** whether ARAM is still running. Until confirmed, both stay on the old theme via literal URL (zero work). If running → refresh + port; if concluded → drop + remove the ARAM nav link.
- **`/events/archive` static menu:** depends on a RainLab.Pages `staticMenu` + a `meta/menus/*.yaml` definition. Porting the theme-level menu YAML is presentational/theme config (allowed); if in practice it can't be kept purely presentational, retain the old-theme page via a literal link.

## 4. Sequencing

**Wave 0 — Bracket spike (de-risk, throwaway).** Before committing Wave 1, build a disposable render of the playoff bracket against fixture data to prove the single/double-elimination layouts, position math, and the spoiler-toggle interaction. Highest-uncertainty item in the phase; retiring it early prevents a mid-wave stall. Not shipped.

**Wave 1 — Public viewing**, dependency-ordered:
1. **Match-card partial** (shared leaf; reused by division, playoff, calendar, match/view, team/view — build first)
2. `/:slug` season overview *(must precede division/playoff — their URLs resolve under the season slug)*
3. `/:slug/:divslug` division
4. playoff brackets (`/:season/playoff/:title` + `/tournament/:title`)
5. `/match/view/:id`
6. `/calendar`
7. `/team/view/:slug` (+ free-rider `/season/archive`)

**Wave 2 — Static content** runs alongside Wave 1 (no dependency beyond Phase-1 chrome): high-volume/low-each content pages, then the data-shaped Division-S tables.

**Finishing pass** (mirrors Phase-1 Task 11): responsive 360/768/1200, keyboard/focus, `prefers-reduced-motion`, timezone, console/log hygiene across all new pages.

## 5. Key dependencies & new shared units

**Hard ordering:** season/view before division/playoff; the **match-card partial** before the pages that render/link match cards (the `ViewMatch` detail page itself can land later — frozen literal URLs decouple a link's source from its target page existing, per the Phase-1 rule); nothing in Phase 2 depends on the deferred auth surface (that's the point of the viewing-first scope).

**New shared partials/behaviors** (added to the Phase-1 set of `team/shield`, `site/icon`, `site/initials`, the `DivisionTable` override, and lounge.js tabs/countdown/toast):

- **Match-card partial** — highest-reuse new unit; one canonical match presentation consumed everywhere.
- **Playoff-bracket partials** (single/double-elim variants) **+ spoiler-toggle** (new lounge.js behavior + cookie, neutralized under reduced-motion).
- **Timeline-entry partial** (TimelineEntries) — division, team page.
- **Roster / player-card partial** — team page.
- Reuse the `{% do Component.onRender() %}` data pattern, the literal-URL rule for any cross-link into a not-yet-ported (deferred/dropped) page, and the Phase-1 prose typography for static content.

## 6. Verification philosophy

Unchanged from Phase 1: no theme PHP, so every page's verification is a live check against the running October instance (Docker, `localhost:8090`, fixture DB) — real data or a clean empty state, no 500s, no Twig/console errors, responsive + a11y. The **real team DB dump is still pending** (Phase-1 blocker); when it lands, all Phase-1 and Phase-2 pages get a real-data re-verification pass (fixture-blind items are tracked in `PROGRESS.md`).

## 7. Process

Same as Phase 1: this spec → per-wave implementation plans (`writing-plans`) → subagent-driven execution with a spec-compliance review then a code-quality review per task, fixes re-reviewed, a final whole-phase review, then branch finish. Phase 2 work continues on a branch off the Phase-1 result.

## 8. Standing decisions carried from Phase 1 (do not re-litigate)

- New theme only; nothing under `plugins/` or `themes/HeroesLounge-Theme/` is modified.
- All existing site URLs are frozen; `| page` only for pages this theme owns, literal paths otherwise.
- Design North Star: `docs/superpowers/specs/assets/reference-design.html` + `dashboard-v2.html` (v2 tokens authoritative). No blog/season/match mockups exist — those pages reuse the established design system in its spirit, with the component data bound in.
- Hand-rolled CSS (no build), vanilla JS, self-hosted fonts, inline lucide icons, chamfer focus-ring convention (inset `-4px` on any clip-path'd focusable element).
