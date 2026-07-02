# Heroes Lounge UI/UX Rework — Design Spec

**Date:** 2026-07-03
**Status:** Approved by user (brainstorming session)
**Repo:** fork of Fabian-Sommer/HeroesLounge at V-Simos/HeroesLounge

## Goal

Completely rework the UI/UX of heroeslounge.gg (the amateur Heroes of the Storm
league) so it can relaunch as a modernized live site with its real data and
features. The rework must remain deployable on the existing October CMS
infrastructure and stay cleanly rebasable on upstream.

**Non-goals:** no backend/logic changes, no data migration, no new features
beyond presentation. League logic (matchmaking, Discord integration, replay
parsing, seasons/divisions) is untouched.

## Approach

New October CMS theme, same engine. Chosen over a headless rebuild (would
require building an API surface for ~40 components and reworking auth) and a
full platform rewrite (months of work, high risk). A theme replacement achieves
a complete visual rework at the lowest risk and stays deployable on current
infra.

## Architecture

- **New theme** at `themes/heroeslounge-next/`, built alongside the existing
  `themes/HeroesLounge-Theme/` (untouched). Rollback = switch active theme.
- **Plugins are not modified.** All 40 frontend components get restyled markup
  via October's theme-level component partial overrides
  (`themes/heroeslounge-next/partials/<component>/default.htm`). This keeps the
  fork rebasable on upstream.
- **URLs must not change.** Page file names / URL patterns mirror the existing
  theme exactly (they are linked from Discord history, bookmarks, blog posts).
- **Layouts:** collapse the current 10 near-duplicate layouts into:
  - `default.htm` — sticky nav + footer, content between (covers plain,
    plain-wide, sidebar variants; sidebars become in-page grid columns).
  - `focused.htm` — minimal chrome for auth/registration/password flows.
  - (add a third only if a real page proves incompatible; do not pre-build).
- **Assets:**
  - `assets/css/tokens.css` (design tokens), `base.css` (reset, typography,
    layout primitives), `components.css` (design-system components),
    `pages.css` (page-specific). No build step — plain CSS, `@import`ed or
    linked in order.
  - `assets/js/lounge.js` — small vanilla JS: countdown, tabs, mobile nav,
    ticker pause, and October `data-request` AJAX glue. jQuery remains only
    where October's AJAX framework requires it. **Bootstrap and its jQuery
    plugins are dropped entirely.**
  - Fonts self-hosted as woff2 in the theme (GDPR + reliability; no Google
    Fonts CDN): Chakra Petch (500/600/700), Barlow (400/500/600),
    JetBrains Mono (500/600).
  - Icons: inline SVG (lucide set), pasted per-partial or via a Twig partial
    helper — no icon-font (Font Awesome dropped).

## Design system

Source of truth: user-provided reference design
(`heroes-lounge-redesign.jsx`, mirrored in
`docs/superpowers/specs/assets/reference-design.html`), revised for contrast /
type scale / symmetry per dashboard v2
(`docs/superpowers/specs/assets/dashboard-v2.html`).

### Tokens (`tokens.css`, CSS custom properties)

| Token | Value | Use |
|---|---|---|
| `--void` | `#070A16` | page background |
| `--deep` | `#0D1226` | alternating section background |
| `--panel` | `#151C3D` | card/panel background |
| `--panel2` | `#1B2350` | panel hover / gradient end |
| `--line` | `rgba(136,156,215,0.28)` | borders, dividers |
| `--storm` | `#4FB3F2` | primary accent: interactive, links, key data |
| `--arc` | `#9D8CF2` | secondary accent: meta, tags, casters |
| `--gold` | `#F2BE55` | winners, captains, prizes only |
| `--red` | `#F26379` | live, destructive, action-needed only |
| `--green` | `#57D49A` | positive stats, scheduled/confirmed |
| `--ink` | `#F4F7FF` | primary text |
| `--mute` | `#AAB6DA` | secondary text (AA on panel) |
| `--ch` | 14px chamfer polygon | panel corners |

Type roles: Chakra Petch (display/headings/team names), Barlow (body),
JetBrains Mono (labels, eyebrows, tabular data). Base font-size 17px; table
and list rows 15.5px; panel header labels 12px mono uppercase.

### Core components (`components.css`)

- `.btn-solid` — storm-blue gradient (`#4FB3F2 → #2E86DD`), **dark text
  `#041020`** (measured 7.8:1 → 4.8:1; deliberate decision, option A),
  chamfered. `.btn-ghost` — translucent storm outline, ink text.
- `.panel` / `.chamfer` — chamfered cards; unified `.p-head` header bar
  (52px, mono uppercase, storm).
- Row system: standings rows, match rows, result rows, notification rows all
  share a 56px min-height rhythm and 20px horizontal padding.
- `.tabs`/`.tab` — chamfered mono tabs (division switcher etc.), active = solid
  gradient with dark text.
- Team badge — hex-shield shape; uses the team's uploaded `logo`/`smallLogo`
  attachment when present, else a generated shield: tag initials on a
  hue-derived gradient (hue hashed from team name).
- `.match` card — div/date/VOD header, badge-name vs name-badge grid, mono
  score with winner in storm.
- Standings `.table` — real columns (see below), leader in gold with trophy,
  own team highlighted (storm tint + left bar), inactive teams at 50% opacity.
- Cast/next-match panel — gradient-border chamfered panel with countdown.
- `.ticker` — results marquee, pauses on hover, static-scroll fallback under
  `prefers-reduced-motion`.
- `.pill` status chips — fixed min-width, mono uppercase (Scheduled / Propose
  time / Cast requested / Live).
- Form controls — dark chamfered inputs, selects, date-time picker restyle
  (scheduling and roster management are half the site's interactions).
- `.eyebrow` + heading section-header pattern; alternating `--void`/`--deep`
  section bands.

### Rules

- Accent discipline: storm = interactive; arc = meta; gold = winners/captains/
  prizes; red = live/destructive/urgent. Never decorative.
- Mobile-first; single-column stacking under 900px; burger nav under 720px.
- `prefers-reduced-motion` honored for ticker, pulse, hover transforms.
- Text contrast: AA minimum everywhere (v2 tokens verified).

## Page designs

### Homepage — logged out (marketing)

Structure per reference design, wired to real data:

1. Sticky nav: League / Events / Guides / Calendar / Blog + Sign in + "Join
   Season N" CTA.
2. Hero: eyebrow `EU · SEASON {season.title} · ROUND {season.current_round}`,
   headline, lede, Join + Watch-live CTAs, computed stat chips (division
   count etc. — no hardcoded numbers).
3. Next-cast panel: next upcoming match having an approved caster
   (`match_caster.approved = 1`) and/or linked Twitch channel; countdown to
   `wbp`; falls back to next scheduled match, else hides.
4. Results ticker + "Latest results" match-card grid: latest played matches
   (`is_played`, ordered by date) across divisions.
5. Standings widget: division tabs, `getDivisionTableStandings()` top 5,
   link to full table.
6. Cups & events: from blog/event content (matches current site's tournament
   posts).
7. How-it-works (4 static panels) and latest blog posts.
8. Footer CTA + footer (league/guides/community links, socials, Patreon).

### Homepage — logged in (dashboard)

Dual homepage confirmed: same URL, component-driven swap (as today's
`user/welcome` pattern). Layout per `dashboard-v2.html`:

- Welcome strip: eyebrow (region/season/round), "Welcome back, {username}",
  team chips (captain flagged gold, from `sloth_team.is_captain`).
- Row 1 (full width): "Your next match" cast-style panel — teams, countdown to
  `wbp`, caster status, Reschedule (existing ScheduleMatch component flow) +
  Match page actions.
- Row 2 (symmetric pair): your division standings (tabbed per team; own row
  highlighted) | notifications (action-needed items in red, from
  NotificationHelper data).
- Row 3 (symmetric pair): recent results in your divisions | your matches next
  14 days with status pills (existing UpcomingMatches logic).

### Season / division pages

- Existing five per-division URLs kept (standings, schedule, general, crew,
  ruleset). Each gets a shared **division header** partial: division title +
  logo, season, region, MMR bound, round progress — plus a chamfered tab bar
  linking the five sibling pages so they read as one hub.
- Standings table columns (from `getDivisionTableStandings()`): `#`, active
  status, team, played (`match_count`), wins (`win_count`), map wins, map
  score. Playoff group tables additionally show points (3/win, 1/tie) via the
  existing `showScore` mode.
- Season overview page: grid of division panels, each with top-3 mini-table +
  "full table" link. Season archive list restyled.
- Playoffs: bracket rendering in the design language for the existing playoff
  types (single elim, double elim, groups). Bracket data from
  `playoff_position` encoding; mobile shows a stacked per-round list instead
  of a 2-D bracket.

### Match page

Header: teams + badges + final score; meta row (division/round or playoff
position, scheduled time in viewer's timezone, casters with approval state,
Twitch/VOD links). Per-game panels: map, winner, duration, bans (hero
portraits from existing assets), participants table (hero, K/D/A, talents)
from `gameParticipations`. Reuses the existing ViewMatch component data
unchanged.

### Remaining pages

Same design language via the shared component library: team page, player
(sloth) profile, blog list/post, calendar, caster schedule, statistics pages,
applications, all management forms (create team, manage roster, schedule
match), FAQ, search, contact, timezone, maintenance, RSS (unstyled).

## Phasing

1. **Phase 1:** design system (tokens/base/components) + layouts + homepage
   (both states) + blog pages.
2. **Phase 2:** season/division/standings pages + match page + playoff
   brackets.
3. **Phase 3:** team & player profiles + forms/management screens
   (scheduling, roster, applications, team creation, account).
4. **Phase 4:** long tail — calendar, search, FAQ, caster schedule,
   statistics pages.

The new theme runs as October's preview theme during development; the live
site switches only after Phase 3 is complete. Every page keeps its URL.

## Error handling & empty states

- Every data-driven section defines an empty state (no upcoming cast, no
  active season, empty division, unplayed match) — designed, not blank.
- October AJAX (`data-request`) error flashes get a styled toast/inline error
  component replacing the current unstyled behavior.
- Maintenance page included in Phase 1 (it's the failure face of the site).

## Testing & verification

- No PHP changes → no backend test impact. Verification is visual/functional:
  each phase is checked against the local Vagrant instance with the anonymized
  production DB dump (per README) for logged-out and logged-in states.
- Manual checklist per page: mobile (360px), tablet (768px), desktop (1200px);
  keyboard focus visible; reduced-motion honored; AA contrast spot-checks.
- October AJAX flows (scheduling, roster forms) exercised end-to-end on the
  Vagrant instance before a phase is called done.

## References

- Style North Star: `docs/superpowers/specs/assets/reference-design.html`
  (static render of the user's `heroes-lounge-redesign.jsx`).
- Approved dashboard layout + v2 tokens:
  `docs/superpowers/specs/assets/dashboard-v2.html`.
- Decisions log: dark-esports direction; dual homepage; hand-rolled CSS (no
  build step); button treatment A (dark text on storm blue).
