# Heroes Lounge UI Rework — Phase 4 Implementation Plan (static content wave)

> **For agentic workers:** implement task-by-task; each task ends with a live
> verification against the Docker site and a commit. Steps use checkbox
> (`- [ ]`) syntax for tracking only — `docs/superpowers/PROGRESS.md` is the
> single source of truth for status (the boxes here are NOT maintained).

**Goal:** Port every remaining *public, read-only static* surface of the old
theme into `heroeslounge-next`, so the footer/nav links that still fall
through to a themed not-found (`/faq`, `/general/ruleset`, `/general/staff`,
`/privacy-statement`) resolve, and the rules / schedule / hall-of-fame /
Division-S pages come back under their frozen URLs.

**Binding spec:** Phase 2 spec §3.2 “Wave 2: Static content”
(`docs/superpowers/specs/2026-07-04-phase-2-public-reskin-design.md`). This
plan is the Wave-2 plan that spec deferred; the scope reconciliation below
records what Phase 3 already absorbed and what changed since.

**Architecture:** Pure frontend re-skin. Content bodies are RainLab.Pages
static pages copied **byte-verbatim** from the frozen theme (only the
`layout = ` line changes, exactly as Phase 3 Task 7 did for the guides).
Because staff edit these bodies in the backend editor using the old theme's
Bootstrap-4 + Froala class vocabulary, the new theme does **not** rewrite the
markup; it ships a scoped **legacy content vocabulary** (CSS in `pages.css`
under `.static-content`, plus small progressive-enhancement behaviors in
`lounge.js`) that renders that vocabulary in the new design system —
accordions (`data-toggle="collapse"`), tabs (`data-toggle="tab"`), cards,
list-groups, alerts, grids, crew cards, editor tables, and the inline
`style=""` colours the editor baked in. Three CMS pages (`/faq`,
`/general/ruleset`, `/general/staff`) are real theme pages; `/general/ruleset`
re-skins the frozen `Bans` component through a lowercase override.

**Tech stack:** October CMS v1 (INI front-matter + Twig), RainLab.Pages
static pages + static menus (theme-owned YAML under `meta/`), hand-rolled CSS
(tokens in `assets/css/`), vanilla JS (`lounge.js`), lucide inline SVGs.

**Authoritative references:**
- Phase 2 spec (binding scope): `docs/superpowers/specs/2026-07-04-phase-2-public-reskin-design.md` §3.2, §3.4, §3.5
- Tracker + gotchas: `docs/superpowers/PROGRESS.md` (lowercase override rule, CSS placement invariant, literal-URL rule, one-`<h1>` rule + Phase-3 exception)
- Phase 3 plan (static layout + guides precedent): `docs/superpowers/plans/2026-07-24-phase-3-user-auth.md` Task 7
- Frozen sources: `themes/HeroesLounge-Theme/content/static-pages/*.htm`, `themes/HeroesLounge-Theme/pages/{faq,general/ruleset,general/staff}.htm`, `themes/HeroesLounge-Theme/meta/{static-pages.yaml,menus/*.yaml}`, `plugins/rikki/loungeviews/components/bans/default.htm`, `themes/HeroesLounge-Theme/assets/css/custom.css` (crew-card rules)

---

## Scope reconciliation (spec §3.2 → this plan)

| Spec §3.2 item | Frozen file(s) | Frozen URL | Status |
|---|---|---|---|
| Guides (index + 6 guides) | `guides*.htm` | `/guides/*` | ✅ Phase 3 Task 7 — untouched here except the shared layout upgrade |
| Events archive | `events_archive.yaml` | `/events/archive` | ✅ Phase 3 Task 6 |
| Help: FAQ | `pages/faq.htm` + `faqpage.htm` | `/faq` | **T2** |
| Rules: general ruleset | `pages/general/ruleset.htm` + `general-rules.htm` + `[Bans]` | `/general/ruleset` | **T4** |
| Rules: playoff rules | `playoff-rules.htm` | `/general/playoff-rules` | **T1** |
| Rules: tournament ruleset | `tournament-ruleset.htm` | `/method-mayhem-hots-ruleset` | **T2** (free-rider — same accordion vocabulary; orphaned URL kept alive) |
| Legal: privacy statement | `privacy-statement.htm` | `/privacy-statement` | **T1** (footer links it) |
| Records: hall of fame | `general-hall-fame.htm` | `/general/hall-of-fame` | **T2** |
| Ops: seeding rules | `general-test.htm` | `/general/seeding-rules` | **T1** |
| Ops: season schedule | `schedule.htm` | `/general/schedule` | **T1** |
| Division-S: crew | `division-s-crew.htm` | `/division-s-crew` | **T3** |
| Division-S: ruleset (+ playoffs) | `division-s-ruleset.htm` (+ `…-division-s-playoffs.htm`) | `/division-s-ruleset` (+ `/division-s-playoffs`) | **T2** (parent keeps its frozen `is_hidden = 1`) |
| Division-S: qualifier standings, standings, schedule | `division-s-{qualifier-standings,standings,schedule}.htm` | `/division-s-*` | **T1** (tables) / **T2** (schedule accordion) |
| Staff (“Volunteers” footer link) | `pages/general/staff.htm` + `general-staff.htm` | `/general/staff` | **T3** |
| `general` landing | `general.htm` | `/general` | **T1** (parent of the `general` subtree; public in the frozen theme) |
| Offmeta Maps ruleset | `ruleset.htm` | `/offmeta-maps-ruleset` | **T2** (free-rider) |
| ARAM league ruleset | `aram-league-ruleset.htm` | `/aram-league-ruleset` | **T2** (free-rider; ARAM retire/refresh stays an open content decision — porting keeps the frozen page reachable meanwhile, same call Phase 3 made for the ARAM signup guide) |

**Not ported (spec §3.4 “drop”, re-confirmed):** `testriggingpage`,
`method-mayhem-overview`, `method-mayhem-point-standings`, `heroes-cup-emim`
(concluded events), `division-s-overview` (`/divisionsoverview`, hidden), the
2019-era `pages/divisionS/*` CMS pages, `/general/rules` + `/general/staffpage`
+ `/faqpage` (frozen `is_hidden = 1` bodies that only render through
`{% content %}` — the files ARE copied for that purpose, but are NOT registered
in the manifest, so their hidden URLs stay unrouted exactly as the frozen
theme hid them from guests), `/contact` (500s on a missing partial), `/search`,
`/ext-div/:id`, `/general/nacasterstatistics`.

**Deferred to Phase 5 (interactive wave — separate plan):** team
create/manage/match, applications, `/statistics*`, `/general/casterstatistics`,
`/timezone`, `/rssfeed.xml`, and any search decision.

**Design additions this wave (presentational, theme-owned):**
1. **Section subnav** on static pages. The old site reached these pages only
   through nav dropdowns that the Phase-1 mockup nav does not have, so the
   ported pages would be orphaned. The static layout attaches three
   `[staticMenu]` components against theme-owned menu YAML (`guides`,
   `general`, `division_s`) and renders the matching section's links as an
   aside. Static-page references in the YAML derive titles/URLs from the
   manifest (no duplicated content); the two General CMS pages are added as
   URL items.
2. **Legacy content vocabulary** (see Architecture) instead of content
   rewrites. Inline editor colours (`#2e93cd` brand blue, `rgb(239,239,239)`
   zebra grey, `rgb(209,213,216)` header grey) are *remapped* to tokens with
   scoped `[style*=…]` selectors so contrast holds on the dark theme.
3. **`{% placeholder banner %}`** in the static layout for full-bleed page
   banners (`/general/staff`).

---

## Crash course / conventions (Phase-4 deltas on top of the standing rules)

All standing rules apply unchanged: pure re-skin; `plugins/rikki/*` and the
old theme FROZEN; frozen URLs; component-override dirs ALL-LOWERCASE;
components.css additions go BEFORE its focus-affordance list + reduced-motion
end-block; pages.css keeps per-section local reduced-motion blocks; literal
URLs for links into unported pages; one `<h1>` per page except recorded
frozen-content exceptions; commit with `git -c core.fsmonitor=false`; trailer
`Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>`.

- **Content fidelity contract (Phase 3 Task 7, restated):** every
  `content/static-pages/*.htm` body is byte-identical to the frozen file after
  normalising ONLY the `layout = "…"` line to `layout = "static"`. Never edit
  markup, ids, classes, inline styles, absolute `https://heroeslounge.gg/…`
  asset URLs, or the frozen `is_hidden`/`navigation_hidden` values.
  `dev/verify-static-content.ps1` enforces this.
- **Manifest (`meta/static-pages.yaml`)** is the routing source of truth for
  RainLab.Pages: a body file that is not listed does not route. Nesting must
  mirror the frozen manifest for the pages we port (URLs are absolute in each
  body, so nesting only affects menus/parents, but keep it faithful).
- **Frozen-content heading quirks are recorded, not fixed.** Bodies that own
  a title `<h1>` are listed in the layout's `contentOwnsH1` switch so the
  layout adds none. Bodies with *section* `<h1>`s (`schedule.htm` ×5,
  `playoff-rules.htm` has a title h1 + a section h1) render as-is — same
  exception class as the First Game guide. They are styled down visually via
  `.static-content.has-layout-title > h1`.
- **CMS-page component fidelity:** the frozen `[ssbuttonsnb]` share-button
  component on `faq.htm` / `general/staff.htm` is NOT ported (marketplace
  plugin absent — same call as `pages/blog/post.htm`; leave the same code
  comment). `[Bans]` IS ported (the plugin is in-repo, frozen) via
  `partials/bans/default.htm`.
- **Legacy vocabulary is scoped to `.static-content`** (never global): the
  Bootstrap class names must not leak into the design system. The
  `.collapse` rule must exclude `.navbar-collapse` (the crew pages' tab bar
  container carries both classes and is always visible).
- **Tables:** `lounge.js` wraps every `.static-content table` in
  `div.table-scroll` (overflow-x) — editor tables are hand-sized and would
  otherwise force page-level horizontal overflow at 360px.
- **Images in legacy content lose the blog chamfer** (`clip-path: none`) —
  bodies mix 16px flag icons and logos, and a 14px chamfer destroys small
  images.
- **No Font Awesome in the new theme:** the crew cards' `<i class="fa fa-twitch">`
  / `fa-twitter` render through scoped `::before` masks (inline SVG data
  URIs) so the social links keep a visible glyph. Their missing accessible
  names are content debt (recorded), not a theme defect.

**File map (everything Phase 4 creates/modifies):**

```
themes/heroeslounge-next/
  layouts/static.htm                 (subnav + banner placeholder + h1 ownership)  T1
  partials/site/subnav.htm           (section links aside)                          T1
  partials/bans/default.htm          (lowercase Bans override)                      T4
  pages/faq.htm                      (/faq)                                         T2
  pages/general/ruleset.htm          (/general/ruleset, [Bans])                     T4
  pages/general/staff.htm            (/general/staff, banner)                       T3
  content/static-pages/*.htm         (verbatim bodies, per task)                    T1–T4
  meta/static-pages.yaml             (full public manifest)                         T1–T4
  meta/menus/{guides,general,division_s}.yaml                                       T1
  assets/img/staff/dreamhack2018.jpg (ported banner, downscaled)                    T3
  assets/css/pages.css               (static layout grid, subnav, legacy vocabulary)
  assets/js/lounge.js                (legacy collapse/tab/table-scroll/crew focus)
dev/verify-static-content.ps1        (byte-identity + contracts)                    T5
dev/README.md, docs/superpowers/{PROGRESS,NEXT-SESSION,KNOWN-ISSUES}.md            T5
```

---

## Task 1 — Static layout upgrade + prose/table pages

**Pages:** `/privacy-statement`, `/general` (landing), `/general/seeding-rules`,
`/general/schedule`, `/general/playoff-rules`, `/division-s-standings`,
`/division-s-qualifier-standings`.

- [ ] **Step 1: Menus + manifest.** Create `meta/menus/guides.yaml` and
  `meta/menus/general.yaml` (frozen copies; `general` additionally gains two
  `type: url` items — `Ruleset → /general/ruleset`, `Crew → /general/staff` —
  so the General subnav covers the two CMS pages; code-comment the addition)
  and a new `meta/menus/division_s.yaml` (static-page references: crew,
  standings, qualifier standings, schedule, playoffs ruleset). Extend
  `meta/static-pages.yaml` with `privacy-statement`, the `general` subtree
  (`playoff-rules`, `general-test`, `schedule`, `general-hall-fame`) and the
  five Division-S entries mirroring the frozen nesting (`division-s-ruleset`
  → `division-s-ruleset-division-s-playoffs`).
- [ ] **Step 2: Bodies.** Copy this task's seven bodies verbatim
  (`layout = "static"` only).
- [ ] **Step 3: Layout.** `layouts/static.htm`: attach
  `[staticMenu sectionGuides] code="guides"`, `[staticMenu sectionGeneral]
  code="general"`, `[staticMenu sectionDivisionS] code="division_s"`; add
  `{% placeholder banner %}` above the section; pick the section from
  `this.page.url` prefix (`/guides`, `/general`, `/division-s`) and render
  `{% partial 'site/subnav' %}` in a two-column `.static-grid` (aside 240px,
  content); extend `contentOwnsH1` with `/general/playoff-rules`,
  `/division-s-ruleset/division-s-playoffs`, `/general/staff`; add
  `has-layout-title` to the article when the layout supplied the h1.
- [ ] **Step 4: CSS + JS (foundation).** `pages.css`: `.static-grid`,
  `.subnav` (Chakra Petch links, active state = storm left rule, mobile =
  horizontal scroll row), measure-on-text-only (`.static-content > p/ul/ol/
  h2–h5/blockquote { max-width: 72ch }`), editor tables (`table-scroll`,
  cell padding/borders, inline-colour remaps: `2e93cd` → `--panel2` bg +
  `--ink`, `209, 213, 216` → `--panel2` + 600 weight, `239, 239, 239` →
  `--panel`, `a[style*="color"]` → storm), `img { clip-path: none }`,
  `.fr-dib` block images. `lounge.js`: table wrapping.
- [ ] **Step 5: Verify live.** All seven URLs + `/guides` (regression) 200;
  subnav shows the right section with the active link; exactly one `<h1>` on
  every page except the recorded `schedule` (layout h1 + 5 section h1) and
  `playoff-rules` (own h1 + 1 section h1) quirks; no page-level horizontal
  overflow at 360px (tables scroll internally); every editor table cell
  readable (no light-grey cells with light text); `storage/logs` clean.
- [ ] **Step 6: Commit** `feat(theme-next): static layout subnav + rules/schedule/standings pages`.

## Task 2 — Accordion pages (FAQ, hall of fame, rulesets, Division-S schedule)

**Pages:** `/faq`, `/general/hall-of-fame`, `/division-s-ruleset`
(hidden, faithful), `/division-s-ruleset/division-s-playoffs`,
`/division-s-schedule`, `/method-mayhem-hots-ruleset`, `/offmeta-maps-ruleset`,
`/aram-league-ruleset`.

- [ ] **Step 1: Bodies + manifest + page.** Copy the eight bodies verbatim;
  register `ruleset`, `tournament-ruleset`, `aram-league-ruleset` as
  top-level manifest entries (frozen positions). Create `pages/faq.htm`:
  `title = "FAQ"`, `url = "/faq"`, `layout = "static"`, `is_hidden = 0`, body
  `{% content 'static-pages/faqpage' %}` (+ the ssbuttons non-port comment).
- [ ] **Step 2: Vocabulary — cards/accordions.** `pages.css` (scoped):
  `.card`, `.card-header` (Chakra Petch; inline `background-color` remapped),
  `.card-body`, `.card-text`, `.list-group(-flush)`, `.list-group-item`,
  `.alert(-info)`, `.row/.col`, `.card-deck`, `.card-columns`, `.collapse:not
  (.show):not(.navbar-collapse) { display:none }`, accordion trigger
  `a[data-toggle^="collapse"]` as a full-width row with a rotating chevron
  (`[aria-expanded="true"]`), inline-styled hall-of-fame pill links remapped,
  utility classes (`text-center`, `text-white`, `text-muted`, `font-weight-bold`,
  `font-italic`, `mb-0/mb-2/m-1/m-3/mt-1/mr-1/p-3`, `lead`, `text-truncate`).
  Local reduced-motion block neutralises the chevron transition.
- [ ] **Step 3: Behavior.** `lounge.js` “legacy collapse”: click on
  `.static-content [data-toggle^="collapse"]` toggles `.show` on the `href`/
  `data-target` element, mirrors `aria-expanded` on the trigger (initialised
  from the target's `.show` state), honours `data-parent` exclusivity
  (tolerating a missing parent, as Bootstrap does), and opens a `.collapse`
  addressed by `location.hash` on load. No jQuery.
- [ ] **Step 4: Verify live.** All eight URLs (the hidden ruleset → guest
  404, matching the frozen theme); FAQ: 35 accordions closed by default,
  click opens/closes, sibling exclusivity within each of the four accordion
  groups, keyboard Enter on a focused trigger works, `aria-expanded` mirrors
  state; hall of fame: nested Europe → Season N collapses; Division-S
  schedule: `collapse show` groups start open; console + logs clean.
- [ ] **Step 5: Commit** `feat(theme-next): FAQ + accordion static pages (legacy collapse vocabulary)`.

## Task 3 — Crew pages (`/general/staff`, `/division-s-crew`)

- [ ] **Step 1: Assets + page.** Downscale the frozen 2.5 MB
  `assets/img/staff/dreamhack2018.jpg` to ≤1920px / ~q80 into the new theme
  (presentational asset optimisation, comment it). Create
  `pages/general/staff.htm`: `title = "Staff"`, `url = "/general/staff"`,
  `layout = "static"`, `is_hidden = 0`; `{% put banner %}` a full-bleed
  `.static-banner` (image + gradient scrim + the page `<h1>Staff</h1>`), then
  `{% content 'static-pages/general-staff' %}`. Copy `general-staff.htm` and
  `division-s-crew.htm` verbatim; register `division-s-crew`.
- [ ] **Step 2: Vocabulary — tabs + crew cards.** CSS: `.nav-tabs` /
  `.nav-link` (re-use the `.tabs`/`.tab` look), `.tab-pane` hidden unless
  `.active`, `.navbar` reset, responsive `.col-xl-3/.col-lg-4/.col-md-6/
  .col-12` grid, `.blogPostWrapper` (chamfer, relative, h3 overlay title —
  ported from `custom.css`), `.blogImage` (cover, zoom on hover),
  `.hover-fadein` (opacity 0 → 1 on hover **and** `:focus-within`; inline
  `#333` scrim remapped to a void-tinted overlay), `.btn-group-vertical`,
  `.fa-twitch`/`.fa-twitter` glyph masks. Local reduced-motion block
  neutralises the zoom/fade.
- [ ] **Step 3: Behavior.** `lounge.js` “legacy tabs”: click on
  `.static-content [data-toggle="tab"]` activates its `href` pane within the
  nearest `.tab-content` and toggles `.active` on `.nav-link`/`.nav-item`
  siblings + `aria-selected`; crew `.blogPostWrapper`s get `tabindex="0"`
  so the bio overlay is keyboard-reachable.
- [ ] **Step 4: Verify live.** Both URLs 200; staff page: banner + one
  `<h1>`, four tabs switch, 78 crew cards in a responsive grid (4/3/2/1
  columns), hover and keyboard focus reveal a bio, social glyphs visible;
  Division-S crew: single tab, 19 cards; images load from the absolute
  production URLs (dev-blind if offline — record); logs clean.
- [ ] **Step 5: Commit** `feat(theme-next): staff + Division-S crew pages (legacy tab/crew vocabulary)`.

## Task 4 — `/general/ruleset` (Bans override + rules body)

- [ ] **Step 1: Page + override.** `pages/general/ruleset.htm`: `title =
  "Ruleset"`, `url = "/general/ruleset"`, `layout = "static"`, `is_hidden =
  0`, `[Bans]`; body `{% component 'Bans' %}` then `{% content
  'static-pages/general-rules' %}`. Marker-prove `partials/bans/default.htm`
  resolves, then re-skin the frozen partial: heading, note, three lists
  (heroes / bugs / talents — bindings `__SELF__.heroes|literals|talents`,
  `ban.hero.title`, `ban.talent.title`, `ban.literal`, the `round_start` /
  `round_length` bracket text verbatim), the five rule paragraphs verbatim.
  Empty lists render “None currently.” (sanctioned empty-state).
- [ ] **Step 2: Body vocabulary.** `general-rules.htm` uses `.card-deck`,
  `.card-columns`, `.row > .col` and inline `#2e93cd` card headers — covered
  by Task 2's rules; add anything missing (`.card.text-white.bg-dark`).
- [ ] **Step 3: Verify live.** 200; Bans lists render from
  `rikki_heroeslounge_bans` (check row counts via SQL; empty → empty-state);
  0 Bootstrap remnants outside `.static-content`; subnav General section
  active on “Ruleset”; logs clean.
- [ ] **Step 4: Commit** `feat(theme-next): general ruleset page (Bans override)`.

## Task 5 — Finishing pass

- [ ] **Step 1: Static contracts.** `dev/verify-static-content.ps1`: every
  ported body byte-identical to its frozen source after the layout
  normalisation; every manifest entry has a body file and vice-versa for the
  ported set; page front-matter contracts (`/faq`, `/general/ruleset` +
  `[Bans]`, `/general/staff`); lowercase `partials/bans/`; `lounge.js`
  contains the collapse/tab/table-scroll hooks; `pages.css` static block
  precedes its local reduced-motion block; components.css reduced-motion
  block still last; no `href=""`/`#` in theme-authored markup; `node --check
  lounge.js`; `git diff --check`.
- [ ] **Step 2: Live sweep.** Every ported URL at 360/768/1200: 200, no
  page-level horizontal overflow, heading count as recorded, console clean
  (bar the documented dev-data artifacts), `storage/logs` free of
  ERROR/Twig lines. Keyboard: subnav links, accordion triggers, tab links,
  crew cards all focusable with a visible ring (subnav/trigger links are
  not clipped → global ring; crew cards are chamfered → add to the
  focus-affordance list in components.css).
- [ ] **Step 3: Docs.** PROGRESS.md (Phase 4 table + notes + carry-forward
  content debts: staff photos referenced from the old theme's asset URLs,
  FAQ duplicate title, multi-`<h1>` bodies, empty social-link names, ARAM
  decision), NEXT-SESSION.md (Phase 4 done → Phase 5 plan next),
  KNOWN-ISSUES.md (row 8 resolved-by-port), dev/README.md (URLs).
- [ ] **Step 4: Commit** `docs(progress): Phase 4 static content complete`.

---

## Verification philosophy

No theme PHP → no unit tests. Every task verifies live against
`http://localhost:8090` on the imported July-2026 dump. A task is not done
while its page 500s, logs Twig errors, renders unstyled Bootstrap, shows a
blank where the frozen page showed content, or emits an empty href. The
byte-identity check is the fidelity contract; the vocabulary CSS/JS is the
only place the re-skin is allowed to act on legacy content.
