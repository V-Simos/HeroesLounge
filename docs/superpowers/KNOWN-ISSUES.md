# What's Not Working — Audit (2026-07-21)

**Scope:** live audit of the running dev environment after the fresh
`hl_test_data_dump_07_2026.sql` was (supposedly) loaded. Answers "what has no
data / what is broken right now, and is it the DB or the frontend?"

## 2026-07-28 update — Phase 3 complete

Phase 3 ports and verifies the account/auth/profile, caster schedule, Events
archive, and seven Guides surfaces. Severity rows **1, 2, and 3** are now
**RESOLVED-BY-PORT**:

- `/user`, `/user/forgotpassword`, `/user/view/:id`, and
  `/user/casterschedule` render the new theme; authenticated account update,
  profile, password-reset, and caster flows were exercised against the real
  components.
- `/events/archive` is ported, while the nav's `/blog/category/events` target
  works in this seeded environment. Production still needs the documented
  content operation to create/confirm the Indikator.Content `events` category.
- `/guides` and its six linked guide pages are ported under their frozen URLs.
  The First Game source intentionally remains byte-faithful and contains six
  `<h1>` elements. This is the explicitly approved sole Phase-3 one-`<h1>`
  exception under Task 7 fidelity; semantic demotion remains tracked as
  content-migration/a11y debt, not silently changed by the re-skin.

The original 2026-07-21 audit remains below as historical evidence. Resolution
notes in §3.1–§3.3 supersede its old “not ported” conclusions.

### Phase 3 final-review security findings — plugin authorization required

The final review found two pre-existing server-side authorization gaps behind
newly ported controls. Both handlers live under frozen `plugins/rikki/*`; this
theme-only phase did not authorize plugin edits, so the server handlers remain
unchanged. Making the theme-owned controls keyboard-operable does not change
their AJAX wiring or resolve the authorization risk:

- `UpcomingMatches::onCastRequest()` / `onCastRetract()` accept client-controlled
  `match_id` and `caster_id` values without authenticating the caller, checking
  `cast_matches`, or deriving the caster from the signed-in user. A forged
  request can therefore alter another caster's assignments.
- `ViewApps::onSendAccept()` accepts an arbitrary application when the caller
  is merely a member of the target team. The rendered list is captain-filtered,
  but the mutation handler does not enforce captain authority.

Before production exposure, separately authorize plugin-side remediation and
add request-level authorization tests. If plugin remediation is not approved,
the affected caster apply/retract and application-accept controls must be
disabled or withheld; a theme-only markup change is not a security fix.

## 2026-07-24 update — live-data seed (`fixtures:live-data`)

The dev-only `fixtures:live-data` command (commits 29f23d7..cc8b60c) now
generates live data on top of the dump. Verified by a full HTTP sweep
(13 URLs, all 200, 0 log errors):

- Severity rows **2, 4, 5, 7** (§3.2 events-404, §4.1 empty calendar, §4.2
  divisions without fixtures, §4.4 sparse blog) are **RESOLVED-BY-DATA** after
  running the seed: 117 matches under the active seasons populate standings,
  rounds, calendar, match/team pages and homepage widgets; the `events` blog
  category + `dev-` posts make `/blog/category/events` a working page.
- Row **6** / §4.3 (per-game/player statistics) **remains blind** —
  `gameparticipation` = 0 rows is a dump limitation the seeder does not fake.
- **Audit correction:** the dump actually has **439 blog posts (425 published,
  newest 2026-05-10) and 27 categories** — the "1 post / only Uncategorized"
  claim below was wrong. The real gaps were the missing `events` category and
  the absence of recent content.

**Environment audited**
- Active theme: **`heroeslounge-next`** (the new theme). Confirmed via DB param
  `cms::theme.active = "heroeslounge-next"` — this **overrides** the
  `CMS_ACTIVE_THEME: HeroesLounge-Theme` env var in `dev/docker-compose.yml`.
- Database: `heroeslounge` (MySQL 5.7 container). Container clock: **2026-07-21**.
- Method: live HTTP probes inside the web container + direct MySQL queries +
  theme file inventory. **`storage/logs/system.log` has 0 ERROR / exception /
  Fatal lines** — nothing is crashing; every symptom below is either *empty data*
  or an *un-ported page* returning a graceful themed not-found.

---

## Bottom line

There are **two independent root causes**, and neither is a code bug:

1. **The fresh 2026 dump imported fine, but it contains almost no *live* data.**
   The newest match in the whole dump is **2024-04-28**; the two 2026 "active"
   seasons have teams but **zero generated match fixtures**; and the
   `gameparticipation` table is **empty (0 rows)**. So all the forward-looking /
   per-game views are legitimately empty *given the data*. A newer dump does **not**
   fix this — it needs generated fixtures + a dump that includes game stats.

2. **Most of the site isn't ported to the new theme yet.** `heroeslounge-next`
   only ships the Wave-1 pages (home, blog, calendar, season/division/playoff,
   match, team). Everything else — **crucially the entire `/user` auth/account
   system**, plus guides/faq/contact/statistics/search/events — has no page in the
   active theme, so those URLs fall through to a themed "Not found". This is the
   planned Wave 2 / Phase 3 work, not a regression.

> ⚠️ **Important expectation-check:** loading the "fresh" 2026 dump did **not**
> make the empty pages populate, and it never could — see §2. If the goal was
> "get the site showing current data", the dump alone is not enough.

---

## Severity table

| # | What | DB or Frontend? | Severity | Root cause |
|---|------|-----------------|----------|-----------|
| 1 | **Sign in / Join Season / account / profile** | Frontend | ✅ Resolved by port | §3.1 |
| 2 | **Events** nav + archive | Frontend + production content | ✅ Resolved by port in dev | §3.2 |
| 3 | **Guides** nav + seven guide pages | Frontend | ✅ Resolved by port | §3.3 |
| 4 | Calendar renders but is **empty** | DB (no upcoming matches exist) | 🟡 Data | §4.1 |
| 5 | Active season divisions show **teams but no matches/rounds** | DB (no fixtures generated) | 🟡 Data | §4.2 |
| 6 | All **per-game / player statistics** blank | DB (`gameparticipation` = 0 rows) | 🟡 Data | §4.3 |
| 7 | **Blog** nearly empty (1 post, only "Uncategorized") | DB (content) | 🟡 Data | §4.4 |
| 8 | FAQ / Contact / Statistics / Search / Division-S / remaining static pages | Frontend (not ported) | 🟠 Med | §3.4 |
| 9 | Team **create / manage / match** pages absent | Frontend (not ported) | 🟠 Med | §3.4 |
| 10 | First Game guide source contains six `<h1>` headings | Content migration / a11y | 🟡 Debt | §3.5 |

---

## 1. The fresh dump — what it actually contains

The import **is** live (this is genuinely the July-2026 data, not the old
May-2024 dump): the active season is now **`eu-season-30` "[EU] Season 30"** and
**`NMMR3` "Nexus MM Rumble 3"** (created 2026-05/06), whereas the old dump's
active season was `eu-season-23`. Dump header: *"Dump completed on 2026-07-12"*,
MySQL 8.0.36, source DB `hl_main`, collation `utf8mb4_0900_ai_ci`.

But the data is **historically frozen**:

| Fact | Value |
|---|---|
| Latest match `wbp` (when-being-played) | **2024-04-28** |
| Latest match `schedule_date` | **2024-04-26** |
| Matches dated 2025 or 2026 | **0** (dump has only ~80 total 2025/2026 date literals — all season/playoff timestamps, not fixtures) |
| Unplayed matches (`winner_id IS NULL`) | 624 — but **all past-dated**, 0 in the future |
| Active seasons (`is_active=1`) | 2 (Season 30 → 5 divisions; NMMR3 → 2 divisions) |
| Matches under those active seasons | **0** |
| `gameparticipation` rows | **0** (across 49,395 `games`) |
| Published blog posts | **1**; blog categories: only "Uncategorized" *(wrong — see 2026-07-24 update)* |

**Conclusion:** the dump is clean and current, but it's a snapshot of a league
whose match activity ends in April 2024 and whose 2026 seasons are set up
(divisions + teams) but have **no fixtures generated yet**. Everything "empty"
below follows directly from this.

---

## 2. Where things live (so "DB vs frontend" is unambiguous)

- **Matches / seasons / divisions / playoffs / teams** → database. Present but
  historically frozen (§1).
- **Guides** → October **static pages** (`content/static-pages/guides*.htm`) —
  *theme files*, only in the old `HeroesLounge-Theme`. Never in the DB.
- **Events (archive)** → an October **static menu** (`staticMenuEventsArchive`,
  code `events_archive`) in `meta/menus/` — *theme files*, only in the old theme.
- **User accounts** (RainLab.User) → database, but the **pages** that render/log
  in/manage them (`pages/user/*`) exist only in the old theme.

The new theme has **no `content/`, no `meta/menus/`, and no `pages/user`** dirs,
so all of the above are simply absent from the active site.

---

## 3. Broken things reachable from the UI (frontend / not-ported)

### 3.1 ✅ Authentication & account — resolved by Phase 3

**Current state (2026-07-28):** the new theme now owns `/user`,
`/user/forgotpassword/:code?`, `/user/view/:id`, and
`/user/casterschedule`. Guest, authenticated, reset, profile, and caster
states were verified in Task 8. The text login field deliberately uses
`autocomplete="username"` to match the current local RainLab.User setting.

**Historical 2026-07-21 finding follows:**

`partials/site/nav.htm` links **Sign in**, **Join Season**, the **avatar**, and
the **notifications bell** all to **`/user`**. The new theme has no `pages/user/*`,
so:

| URL | Result |
|---|---|
| `/user` | 200 but themed **"Not found"** (caught by the `/:slug` season route) |
| `/user/account` | 200 but **"Unknown division"** (caught by `/:slug/:divslug`) |
| `/user/view/1` | **404** "Not found" |

Effect: **you cannot sign in, register, join a season, view profiles, or see
notifications.** Highest-impact gap. (Planned Phase-3 work.)

### 3.2 ✅ Events nav and archive — resolved by Phase 3

**Current state (2026-07-28):** `/events/archive` is ported and
`/blog/category/events` returns 200 against the live-data seed. Production
cutover must still create or confirm the `events` category; that is a content
operation, not a theme-code gap.

**Historical 2026-07-21 finding follows:**

Nav "Events" points at **`/blog/category/events`** (not the old
`/events/archive`). There is **no `events` blog category** in the fresh dump
(only "Uncategorized"), so it returns **404 "Category not found"**. Two problems
stacked: the target category doesn't exist *and* the old events archive isn't
ported.

### 3.3 ✅ Guides nav — resolved by Phase 3

**Current state (2026-07-28):** `/guides` and all six linked guide pages render
from the new theme under their frozen canonical URLs.

**Historical 2026-07-21 finding follows:**

Nav "Guides" points at **`/guides`** (a hardcoded old-theme literal — the nav
comments say so). The guides landing + sub-guides are RainLab static pages that
live only in the old theme → **200 themed "Not found"**.

### 3.4 🟠 Other un-ported pages (fall through to themed not-found)
Old-theme routes with **no page in `heroeslounge-next`** (all currently render a
graceful themed not-found / division fallback):

- `/faq`, `/contact`, `/search`
- `/statistics`, `/statistics/hero/:slug/:season?`
- `/application`, `/application/view/:id` (season sign-up applications)
- `/team/create`, `/team/manage/:slug`, `/team/match/:slug` (only `/team/view` is ported)
- `/divisionS/*` (crew / general / ruleset / schedule / standings)
- `/general/*` (staff, caster statistics, NA caster statistics, ruleset)
- `/ext-div/:id` (extended division table), `/rssfeed.xml`, `/timezone`
- RainLab static pages outside the seven Phase-3 guide entries (rulesets,
  privacy statement, hall of fame, staff, Division-S / Method-Mayhem content,
  etc.).

These are planned remaining-wave scope per `PROGRESS.md`, not regressions.

### 3.5 🟡 First Game guide heading structure — content migration debt

The frozen source for
`/guides/scheduling-and-playing-your-first-game` contains six `<h1>` section
headings. Phase 3 intentionally preserves the source byte-for-byte except for
its layout front matter, and Task 8 explicitly accepts it as the sole Phase-3
one-`<h1>` exception. A future authorized content migration should decide the
intended heading hierarchy and demote section headings; the frontend re-skin
must not make that editorial change silently.

---

## 4. Empty-but-correct (data-driven, not bugs)

### 4.1 Calendar (`/calendar`) — renders, list empty *(resolved-by-data — see 2026-07-24 update)*
`UpcomingMatches type=all` lists **future, unplayed** matches. The dump has 0
matches after 2024-04-28 and the 2026 active seasons have no matches at all →
nothing to show. Page/markup is fine; there is simply no upcoming-match data.

### 4.2 Active-season division pages — teams but no matches *(resolved-by-data — see 2026-07-24 update)*
`/eu-season-30` lists its 5 divisions correctly; `/eu-season-30/division-1`
renders standings with **12 team rows** but every round says **"No matches this
round."** and the sidebar says **"Nothing scheduled."** — because the season has
**0 generated fixtures**. Ported page working as designed against empty data.

### 4.3 Per-game & player statistics — blank everywhere
`gameparticipation` = **0 rows**, so match "Statistics" tabs, team statistics
(hero pick/winrate), and player stats all render their empty state. This is a
**known limitation of the dump** (same as prior tasks noted for the 2024 dump);
it will stay blind until a dump that includes `gameparticipation` is used.

### 4.4 Blog — nearly empty
Only **1 published post** and only the "Uncategorized" category exist, so the
blog list and the homepage blog strip are sparse. Content/DB issue, not markup.
*(This count was wrong — see 2026-07-24 update: 439 posts / 425 published /
27 categories; the real gaps were the missing `events` category and no recent
content.)*

### 4.5 Team logos 404 (cosmetic, known)
Team logo uploads aren't in the dump → shield images 404 and the plugin's
ResizeSensor re-requests them. Long-documented **dev-data artifact**, not a bug.

---

## 5. What IS working (for balance)

Ported Wave-1 pages render correctly against the fresh data (all HTTP 200):
`/` (home), `/blog`, `/calendar` (chrome), `/eu-season-30` (season overview),
`/eu-season-30/division-1` (division), `/season/archive` (27+ archived seasons),
`/match/view/:id`, `/team/view/:slug`, `/tournament/:slug` (playoff brackets).
No 500s, no exceptions in the logs.

---

## 6. Recommended next actions

**If the goal is to *demo* a live-looking site now (data):**
- Generate fixtures for an active season (Season 30) so divisions/standings/
  calendar populate — the app's own match-generation path, or seed matches with
  `wbp`/`schedule_date` in the future (> 2026-07-21) and `winner_id NULL` for the
  calendar, some played for standings.
- Add a few blog posts + an `events` blog category (or repoint the Events nav
  link) so Blog/Events aren't empty.
- Note: per-game statistics can't be demoed without a dump containing
  `gameparticipation`.

**If the goal is to close the remaining *frontend* gaps (porting):**
- Plan the remaining static content: FAQ, contact, rulesets, privacy, hall of
  fame, staff, and Division-S / general surfaces.
- Team create/manage/match, statistics, search, division-S/general sections:
  schedule into a later interactive wave.

**Housekeeping:** `dev/docker-compose.yml` still sets
`CMS_ACTIVE_THEME: HeroesLounge-Theme` while the DB forces `heroeslounge-next`.
Harmless (DB wins) but misleading — worth aligning so a fresh volume boots into
the theme actually under development.
