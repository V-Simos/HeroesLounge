# Heroes Lounge UI Rework — Phase 1 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the new `heroeslounge-next` October CMS theme through Phase 1: design system, layouts, homepage (logged-out marketing + logged-in dashboard), and blog pages.

**Architecture:** A brand-new theme alongside the untouched `HeroesLounge-Theme`. Plugins are never modified; plugin component markup is restyled later via theme-level partial overrides. Hand-rolled CSS design system (no build step), vanilla JS, self-hosted fonts.

**Tech Stack:** October CMS themes (INI front-matter + Twig), plain CSS with custom properties, vanilla JS, RainLab.Blog components, existing `rikki.*` plugin components.

**Authoritative references (committed in this repo):**
- Spec: `docs/superpowers/specs/2026-07-03-ui-ux-rework-design.md`
- Visual North Star (logged-out homepage): `docs/superpowers/specs/assets/reference-design.html`
- Approved tokens + dashboard layout: `docs/superpowers/specs/assets/dashboard-v2.html`

**IMPORTANT — design fidelity rule:** wherever this plan says "port from `<asset>.html`", copy that file's CSS/markup structure as literally as possible, only replacing mock data with Twig bindings. Do not restyle, "improve", or substitute values. The v2 tokens in `dashboard-v2.html` supersede the older values in `reference-design.html` (brighter ink/accents, 17px base) — when porting sections from `reference-design.html`, apply the v2 token names and the larger type scale.

**October CMS crash course for this codebase (read once):**
- A page/layout/partial `.htm` file = INI front-matter (components + properties), then `==`, then Twig.
- `[ComponentName]` in front-matter attaches a component; `{% component 'Name' %}` renders its default partial; component page variables are accessible in Twig (e.g. `blogList.posts`).
- `user` is available when `[session]` is on the layout (RainLab.User). The player profile is `user.sloth` (relations: `teams`, each team `active_divisions`).
- `{{ 'path/page' | page({params}) }}` builds URLs, `{{ 'x' | theme }}` builds theme asset URLs, `{{ 'key' | _ }}` translates.
- **CRITICAL — linking to pages this theme doesn't have yet:** the `| page` filter resolves against the ACTIVE theme. Pages that only exist in the old theme (season overview, calendar, match/team pages, privacy — everything Phase 2–4) resolve to `href=""` under this theme. For any link whose target page is not yet ported, hardcode the literal URL path (e.g. `/calendar`, `/match/view/123`-style patterns copied from the old page's `url =` front-matter) — the spec freezes all URLs, so literals are stable. Same for `guides/signup-guide`: it's a RainLab.Pages static page whose content lives in the old theme's `content/static-pages/` — a literal `/guides/signup-guide` URL is the only Phase 1 option. Switch literals back to `| page` filters as pages get ported in later phases.
- Existing examples to imitate: `themes/HeroesLounge-Theme/pages/home.htm`, `partials/user/standings.htm` (DivisionTable wiring), `partials/user/matches.htm` (UpcomingMatches wiring), `partials/sections/posts.htm` (blogList wiring).

---

### Task 0: Verify the dev environment

**Files:** none

- [ ] **Step 1: Check the local October instance**

Run: `curl -s -o /dev/null -w "%{http_code}" http://localhost:8080/hl/`
Expected: `200` (or `302`).

- [ ] **Step 2: If it is NOT running**

Try `vagrant up` in the repo root (see `README.md` for the full Vagrant setup: VirtualBox + DB dump + install scripts). If the environment cannot be brought up (no DB dump available, no VirtualBox), **STOP and ask the user** — all verification steps in this plan depend on a running instance with real data. Do not proceed on a "probably fine" basis.

- [ ] **Step 3: Confirm backend access**

Open `http://localhost:8080/hl/backend` and confirm login credentials work (ask the user for them if unknown). Needed later to switch the frontend theme.

> **RESOLVED 2026-07-03:** Task 0 failed on this machine — no Vagrant/VirtualBox, no DB dump or Project ID (user will obtain team access later), and port 8080 is occupied by an unrelated EnterpriseDB server. User chose a self-sufficient Docker environment instead → Task 0.5. All later verification URLs in this plan become `http://localhost:8090` and "log in with password 1234" becomes "log in with a seeded fixture user". When the real dump arrives later, it replaces the fixture DB and visual checks are re-run.

---

### Task 0.5: Docker dev environment + fixture data

**Files:**
- Create: `dev/docker-compose.yml`, `dev/README.md`, and whatever init scripts/Dockerfile the setup needs (all under `dev/`)
- Create: `plugins/dev/fixtures/` — a tiny dev-only October plugin exposing `php artisan fixtures:seed` (the rikki plugins stay untouched; this is a new, clearly-marked dev plugin)
- Modify: `.gitignore` (whitelist `/dev/` and `/plugins/dev/`)

**Approach:**
- `web` service: October CMS **v1** on PHP 7.x — prefer the community image `aspendigital/octobercms` (PHP 7.4 tag) which ships October v1 preinstalled; mount/copy this repo's `plugins/rikki` and `themes/` into it. Fall back to `php:7.4-apache` + composer `october/october` v1.1.x if the image doesn't work out.
- `db` service: `mysql:5.7`. Site on **port 8090** (8080 is taken on the host).
- Install open-source RainLab plugins from GitHub (no marketplace/Project ID): `rainlab/user-plugin`, `rainlab/blog-plugin`, `rainlab/pages-plugin`, `rainlab/translate-plugin` — pin tags compatible with October v1 / the rikki plugins' era.
- `php artisan october:up` runs all migrations including the rikki plugins' — the schema comes from the repo itself, so it is authoritative.
- Fixture seeder (via the dev plugin, using the real models so relations/pivots are correct): regions EU+NA; one **active** season (title "Season 30", `current_round` set, region EU) with 3 divisions (varied `mmr_bound`); ~10 teams incl. deliberately ugly cases (no logo, one disbanded, long names); users+sloths with known password `dev12345`, one user on two teams and captain of one; `team_division` pivots with varied `win_count/match_count/bye/free_win_count/active`; played matches **with games** (maps, winners, scores) and upcoming matches in the next 14 days covering: `wbp` set, `wbp` null, one with an approved caster + linked Twitch channel, one with a pending caster; blog category `events` + a handful of posts (some in `events`, varied authors/dates).
- `dev/README.md`: one-command bring-up (`docker compose -f dev/docker-compose.yml up -d` then seed), how to log into frontend/backend, how to later swap in the real team dump.

**Done when:** `http://localhost:8090/` serves the site with the OLD theme rendering fixture data correctly (proves environment + data before any new-theme work), backend reachable, `fixtures:seed` idempotent (safe to re-run).

---

### Task 1: Theme skeleton

**Files:**
- Modify: `.gitignore` (whitelist the new theme dir)
- Create: `themes/heroeslounge-next/theme.yaml`
- Create: `themes/heroeslounge-next/version.yaml`
- Create: `themes/heroeslounge-next/layouts/default.htm` (placeholder)
- Create: `themes/heroeslounge-next/pages/home.htm` (placeholder)

- [ ] **Step 1: Whitelist theme in .gitignore**

`.gitignore` is a whitelist. After the line `!/themes/HeroesLounge-Theme/` add:

```
!/themes/heroeslounge-next/
```

- [ ] **Step 2: Create theme.yaml**

```yaml
name: 'Heroes Lounge Next'
description: 'Reworked Heroes Lounge theme — dark esports design system'
author: 'Heroes Lounge Webdevs'
code: heroeslounge-next
require:
    - RainLab.Blog
    - RainLab.Pages
    - RainLab.Translate
```

(Deliberately fewer requires than the old theme — add one back only when a page actually needs it.)

- [ ] **Step 3: Create version.yaml**

```yaml
1.0.0: First version
```

- [ ] **Step 4: Create minimal layout and home page to prove the theme loads**

`layouts/default.htm`:

```
description = "Default layout"

[session]
security = "all"

[Navigation]
paramCode = ""

[SetTimezone]
==
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ this.page.title }} — Heroes Lounge</title>
</head>
<body>
    {% page %}
</body>
</html>
```

`pages/home.htm`:

```
title = "Home"
url = "/"
layout = "default"
==
<h1>heroeslounge-next lives</h1>
```

- [ ] **Step 5: Switch the local instance to the new theme**

Backend → Settings → Front-end theme → activate "Heroes Lounge Next". (Local only — never committed; the production theme switch is a Phase 3+ decision.)

- [ ] **Step 6: Verify**

Open `http://localhost:8080/hl/`. Expected: unstyled "heroeslounge-next lives". No 500 errors in `storage/logs`.

- [ ] **Step 7: Commit**

```bash
git add .gitignore themes/heroeslounge-next
git commit -m "feat(theme-next): theme skeleton"
```

---

### Task 2: Self-hosted fonts

**Files:**
- Create: `themes/heroeslounge-next/assets/fonts/*.woff2` (8 files)
- Create: `themes/heroeslounge-next/assets/css/fonts.css`

- [ ] **Step 1: Download woff2 files** (google-webfonts-helper API; latin subset)

```bash
cd themes/heroeslounge-next/assets/fonts
# Chakra Petch 500/600/700, Barlow 400/500/600, JetBrains Mono 500/600
curl -sL "https://gwfh.mranftl.com/api/fonts/chakra-petch?download=zip&subsets=latin&variants=500,600,700&formats=woff2" -o chakra.zip
curl -sL "https://gwfh.mranftl.com/api/fonts/barlow?download=zip&subsets=latin&variants=regular,500,600&formats=woff2" -o barlow.zip
curl -sL "https://gwfh.mranftl.com/api/fonts/jetbrains-mono?download=zip&subsets=latin&variants=500,600&formats=woff2" -o jbmono.zip
unzip -o "*.zip" && rm *.zip
```

If the API is unreachable, download the same variants manually from fonts.google.com and convert; do NOT ship a CDN `@import`.

- [ ] **Step 2: Write fonts.css** — one `@font-face` per file, `font-display: swap`, e.g.:

```css
@font-face {
  font-family: 'Chakra Petch';
  font-style: normal;
  font-weight: 500;
  font-display: swap;
  src: url('../fonts/chakra-petch-v11-latin-500.woff2') format('woff2');
}
/* …repeat for all 9 files (use the actual downloaded filenames) */
```

- [ ] **Step 3: Commit**

```bash
git add themes/heroeslounge-next/assets
git commit -m "feat(theme-next): self-hosted fonts"
```

---

### Task 3: Design tokens + base styles

**Files:**
- Create: `themes/heroeslounge-next/assets/css/tokens.css`
- Create: `themes/heroeslounge-next/assets/css/base.css`

- [ ] **Step 1: tokens.css** — port the `.hl { --… }` custom-property block from `dashboard-v2.html` **verbatim** (the v2 values: `--void:#070A16`, `--panel:#151C3D`, `--line:rgba(136,156,215,0.28)`, `--storm:#4FB3F2`, `--arc:#9D8CF2`, `--gold:#F2BE55`, `--red:#F26379`, `--green:#57D49A`, `--ink:#F4F7FF`, `--mute:#AAB6DA`, `--deep:#0D1226`, `--panel2:#1B2350`, `--ch` chamfer polygon), but declare them on `:root` instead of `.hl`.

- [ ] **Step 2: base.css** — from the same file's global rules: box-sizing reset, `body` (void background, ink color, Barlow, **17px base**, line-height 1.55, `overflow-x: clip`), link inheritance, `:focus-visible` outline, `.mono`, `.wrap` (max-width 1200px), `.chamfer`, heading font family, `.eyebrow`, section band pattern (`.sec`, `.sec-alt` from `reference-design.html` with v2 line/colors), and the `prefers-reduced-motion` block.

- [ ] **Step 3: Wire CSS into the layout** — in `layouts/default.htm` `<head>`:

```twig
<link rel="stylesheet" href="{{ 'assets/css/fonts.css' | theme }}">
<link rel="stylesheet" href="{{ 'assets/css/tokens.css' | theme }}">
<link rel="stylesheet" href="{{ 'assets/css/base.css' | theme }}">
<link rel="stylesheet" href="{{ 'assets/css/components.css' | theme }}">
<link rel="stylesheet" href="{{ 'assets/css/pages.css' | theme }}">
```

(Create empty `components.css` / `pages.css` now so the links don't 404.)

- [ ] **Step 4: Verify** — reload `/`; page background is `#070A16`, "heroeslounge-next lives" renders in Barlow at 17px (check computed styles in devtools).

- [ ] **Step 5: Commit** — `git commit -m "feat(theme-next): design tokens and base styles"`

---

### Task 4: Component library CSS

**Files:**
- Modify: `themes/heroeslounge-next/assets/css/components.css`

- [ ] **Step 1: Port component classes.** Sources and precedence:

From `dashboard-v2.html` (authoritative): `.btn` / `.btn-solid` / `.btn-ghost` (dark text `#041020` on storm gradient — decided, do not change), `.p` / `.p-head` (52px unified panel header), `.tr` row system (56px rhythm, `.th`, `.you`, `.first`, `.inact`), `.tteam` / `.rank` / `.num` (+ `.pos/.neg/.dim/.big`), `.mrow` / `.when` / `.who` / `.res`, `.pill` (+ `.ok/.warn/.cast`), `.note` (+ `.hot`), `.tabs` / `.tab`, `.badge-lg` team shield, `.teamchip`, `.cast-outer` / `.cast` / `.cast-teams` / `.cast-mid` / `.countdown` / `.starts` / `.cast-foot`, nav styles (`.nav`, `.nav-in`, `.logo`, `.links`, `.avatar`, `.bell`).

From `reference-design.html`, updated to v2 tokens/scale: `.ticker` family, `.match` card family (`.match-top`, `.divtag`, `.vod`, `.match-body`, `.side`, `.tname`, `.score`, `.s-win/.s-loss`), `.event` cards, `.how` grid, `.post` cards, `.chips`, `.hero` family, `.foot-cta`, `.footer` family, `.burger` + mobile `.links` behavior, `.table-foot`.

New (not in mockups) — form controls, one consistent set:

```css
.field { display: flex; flex-direction: column; gap: 7px; }
.field > label { font-family: 'JetBrains Mono', monospace; font-size: 11px;
  letter-spacing: .18em; text-transform: uppercase; color: var(--mute); }
.input, .select, .textarea {
  background: rgba(136,156,215,0.08); color: var(--ink);
  border: 1px solid var(--line); padding: 11px 14px; font: inherit;
  clip-path: polygon(8px 0, 100% 0, 100% calc(100% - 8px), calc(100% - 8px) 100%, 0 100%, 0 8px);
}
.input:focus, .select:focus, .textarea:focus { outline: 2px solid var(--storm); outline-offset: 2px; }
.field .error { color: var(--red); font-size: 13.5px; }
```

Also add a generated team-shield fallback: `.badge-hue-0` … `.badge-hue-11` (12 hue steps, the hsl gradient pattern from the mockups) — Twig will pick one by hashing the team name when no logo attachment exists.

- [ ] **Step 2: Verify** — temporarily drop a `.btn-solid` button and a `.p` panel into `home.htm`, reload, compare against `dashboard-v2.html` opened side-by-side in a browser. Remove the scratch markup after.

- [ ] **Step 3: Commit** — `git commit -m "feat(theme-next): component library css"`

---

### Task 5: lounge.js

**Files:**
- Create: `themes/heroeslounge-next/assets/js/lounge.js`

- [ ] **Step 1: Write the four behaviors** (vanilla, no jQuery; ~100 lines):

1. **Tabs:** click delegation on `[data-tabs]` containers; buttons carry `data-tab-target`, panels matched by id — toggles `.on` on buttons + `hidden` on panels (~10 lines).
2. **Countdown:** every element `[data-countdown="<ISO datetime>"]` ticks down as `xD HH:MM:SS`, switching to text `LIVE` at zero.
3. **Mobile nav:** `#burger` toggles `.open` on `#site-links`.
4. **AJAX error toast:** listen for October's global AJAX error (`window.addEventListener('ajax:request-error', …)` in October v1 jQuery framework it's `$(window).on('ajaxErrorMessage', …)`) — check which framework version `modules/system/assets/js/framework.js` exposes on this install and hook accordingly; show a `.toast` (styled div appended to body, red border, auto-dismiss 6s) instead of the default `alert()`.

- [ ] **Step 2: Wire into layout** before `</body>`: October's framework first (needed for `data-request` forms later), then ours:

```twig
{% framework extras %}
<script src="{{ 'assets/js/lounge.js' | theme }}"></script>
```

- [ ] **Step 3: Verify** — scratch-test in `home.htm`: a `[data-countdown]` span ticking, a two-button tab pair switching panels. Remove scratch markup.

- [ ] **Step 4: Commit** — `git commit -m "feat(theme-next): lounge.js behaviors"`

---

### Task 6: Real layouts + site chrome

**Files:**
- Modify: `themes/heroeslounge-next/layouts/default.htm`
- Create: `themes/heroeslounge-next/layouts/focused.htm`
- Create: `themes/heroeslounge-next/partials/site/nav.htm`
- Create: `themes/heroeslounge-next/partials/site/footer.htm`
- Create: `themes/heroeslounge-next/partials/site/icon.htm`

- [ ] **Step 1: icon partial** — `partials/site/icon.htm` takes `name` and renders the inline lucide SVG (`{% if name == 'swords' %}<svg …>…{% elseif name == 'radio' %}…{% endif %}`). Seed with the icons the mockups use: swords, menu, x, arrow-right, chevron-right, twitch, play, radio, calendar, calendar-clock, trophy, users, user-plus, message-square, heart, newspaper, bell, youtube, twitter, facebook. Copy path data from https://unpkg.com/lucide-static/icons/<name>.svg (24×24, stroke-width 2, `fill="none" stroke="currentColor"`).

- [ ] **Step 2: nav partial** — port the sticky nav from `dashboard-v2.html`: logo (swords icon + HEROES LOUNGE), links **League / Events / Guides / Calendar / Blog**. URL strategy per the crash-course note: `| page` for pages this theme has (`blog/list`), literal paths for everything not yet ported (`/calendar`, `/guides/signup-guide`). Exception — "League": the season page URL is parameterized (`/:slug`), so there's no stable literal; build it from the `Navigation` component already attached to the layout, which exposes the current seasons (see how the old plugin nav partial uses `current_eu_amateurseasons` to get the current season slug). Then:
  - logged out: "Sign in" link + `.btn-solid .btn-sm` "Join Season" → `/user`
  - logged in (`{% if user %}`): bell icon → notifications, avatar chip (user initials in mini hex-shield) + username → `/user`
  - burger button + mobile menu markup per mockup.

- [ ] **Step 3: footer partial** — port from `reference-design.html`: brand col (logo, blurb, socials: Twitch `twitch.tv/heroes_lounge`, YouTube, Twitter, Facebook — Facebook/Twitter/Patreon/Discord URLs are in `themes/HeroesLounge-Theme/partials/modules/footer.htm`; the YouTube URL is in `partials/sections/follow.htm`), LEAGUE / GUIDES / COMMUNITY link columns (literal URL paths for unported pages, per the crash-course note), base bar "© 2017–2026 HEROES LOUNGE · RUN BY VOLUNTEERS" + privacy link (literal `/privacy-statement`).

- [ ] **Step 4: default.htm layout** — full document: head (meta, title, CSS links, favicon from old theme's `partials/assets/favicons.htm`), `{% partial 'site/nav' %}`, `{% page %}`, `{% partial 'site/footer' %}`, scripts. Components: `[session] security="all"`, `[Navigation]`, `[SetTimezone]`, `[staticPage]`.

- [ ] **Step 5: focused.htm layout** — same head/scripts, no nav/footer; centered 480px chamfered panel wrapper around `{% page %}`; small logo on top linking home. Components: `[session]` only.

- [ ] **Step 6: Verify** — `/` shows styled nav + footer around placeholder content; nav collapses to burger at <720px; logged-in state (log in at `/user`, password `1234` per README) shows avatar + bell. **Check every nav and footer link renders a non-empty `href` and resolves (200) against the old site's URLs** — no `href=""` anywhere.

- [ ] **Step 7: Commit** — `git commit -m "feat(theme-next): layouts and site chrome"`

---

### Task 7: Homepage — logged-out, static sections

**Files:**
- Modify: `themes/heroeslounge-next/pages/home.htm`
- Create: `themes/heroeslounge-next/partials/home/hero.htm`
- Create: `themes/heroeslounge-next/partials/home/how.htm`
- Create: `themes/heroeslounge-next/partials/home/cta.htm`
- Modify: `themes/heroeslounge-next/assets/css/pages.css`

- [ ] **Step 1: home.htm skeleton** — keep the old theme's exact structure decision (`{% if user %}` dashboard partials `{% else %}` marketing partials) with URL `/`, layout `default`.

- [ ] **Step 2: hero partial** — port hero from `reference-design.html` (v2 scale): eyebrow `{{ season.region.title|upper }} · {{ season.title|upper }} · ROUND {{ season.current_round }}`. No old-theme partial exposes the active season directly (`sections/intro.htm`/`countdown.htm` are just Twitch-embed/flipclock snippets — don't bother consulting them); derive it in Twig from data already on the page: the cast-panel match's `division.season` (Task 8), i.e. render the eyebrow/chips from the same `UpcomingMatches` component attached to this page. The spec forbids hardcoded numbers — placeholder text is acceptable only between Task 7 and Task 8 commits, and Task 8's verify step must confirm the eyebrow and stat chips are live data before Phase 1 is called done. Headline "The Nexus, every night.", lede, Join + Watch-live CTAs, chips. Grid-line backdrop + radial glows per mockup CSS (goes to `pages.css`).

- [ ] **Step 3: how + cta partials** — port "How it works" (4 `.how` items, static copy from mockup) and the footer CTA section verbatim.

- [ ] **Step 4: Verify** — logged-out `/` renders hero/how/cta styled; compare side-by-side with `reference-design.html`.

- [ ] **Step 5: Commit** — `git commit -m "feat(theme-next): homepage static sections"`

---

### Task 8: Homepage — logged-out, data sections

**Files:**
- Create: `themes/heroeslounge-next/partials/home/cast.htm` (next-cast panel)
- Create: `themes/heroeslounge-next/partials/home/results.htm` (ticker + match cards)
- Create: `themes/heroeslounge-next/partials/home/standings.htm`
- Create: `themes/heroeslounge-next/partials/home/events.htm`
- Create: `themes/heroeslounge-next/partials/home/posts.htm`
- Create: `themes/heroeslounge-next/partials/team/shield.htm` (badge helper)

- [ ] **Step 1: team shield helper** — `partials/team/shield.htm` takes `team` + `size` (`lg`/`sm`); renders `team.smallLogo`/`team.logo` image clipped to the hex shape when present, else the generated shield: first letters of up to two words of `team.title`, hue class from a Twig hash (`{% set hue = team.title|length * 7 % 12 %}` is acceptable determinism).

- [ ] **Step 2: cast panel** — data source: `[UpcomingMatches]` with `type = "all"` (note: `casterFilter` only applies when `type = "caster"` with a specific caster id — it does NOT mean "matches with casters", so don't use it here). In Twig, pick the first upcoming match whose `match.casters` contains an approved pivot (`caster.pivot.approved`); **fall back to the next scheduled match (any)** if none has an approved caster — the spec requires this fallback; hide the section only when there are no upcoming matches at all. Render the gradient-border cast panel: two shields + names, `[data-countdown]` on `match.wbp`, footer `DIVISION … · ROUND … · BO3` + "Open stream" to the match's first Twitch channel (omit the stream link when the match has no channel).

- [ ] **Step 3: results** — consult `RecentResults` component (`plugins/rikki/loungeviews/components/RecentResults.php` + its default.htm) for the latest played matches source. Ticker: one `.ticker-track` with entries doubled in Twig (`{% for pass in 0..1 %}`). Below: `.match-grid` of 6 `.match` cards (shields, names win/loss styled, mono score, DIV tag, date, VOD link when `match.channels` non-empty). **Empty state:** hide ticker+grid if no played matches.

- [ ] **Step 4: standings widget + DivisionTable override** — attach `[DivisionTable]` on the partial like `user/standings.htm` does; division tabs via lounge.js tabs over the active season's divisions (source: same component/page vars the old `sections/…` or season overview page uses — consult `pages/season/view.htm`). **This step creates the theme's first component-partial override** (the component renders its own partial from the plugin otherwise — old Bootstrap markup): create the override under this theme's `partials/` in a directory named **exactly** as the component alias registered in `plugins/rikki/loungeviews/Plugin.php` `registerComponents()` (`DivisionTable`) — the VM filesystem is case-sensitive and a wrong-case directory is *silently ignored*, so prove the override took effect with a temporary marker string before styling. Copy the data logic from `plugins/rikki/loungeviews/components/divisiontable/default.htm`, keep every field binding, and make the one override serve both consumers: no `teamId` → top-5 slice (this widget); `teamId` set → window around the team with `.you` highlight (dashboard, Task 9). Table columns: `#`, team (+shield sm), P, W, Map W, Map ± — gold leader, `table-foot` link to the full division page. **Empty state:** hide when no active season.

- [ ] **Step 5: events** — `[blogList]` filtered to category slug `events` (3 posts, `.event` cards; gold variant when post has tag `prize`). If the `events` category doesn't exist in the DB, fall back to the 3 most recent posts categorized anything but hide gracefully when empty. Pin this against real DB content during verification and note what was found in the commit message.

- [ ] **Step 6: posts** — `[blogList]` 3 latest posts as `.post` cards (date, author via `post.findAuthor.login`, tags from categories) — wiring identical to old `sections/posts.htm`, markup from mockup.

- [ ] **Step 7: Verify against real data** — logged-out `/` full page: every section either shows real DB data or hides cleanly. Screenshot desktop + 360px mobile; compare with `reference-design.html`.

- [ ] **Step 8: Commit** — `git commit -m "feat(theme-next): homepage data sections"`

---

### Task 9: Dashboard (logged-in homepage)

**Files:**
- Create: `themes/heroeslounge-next/partials/dashboard/welcome.htm`
- Create: `themes/heroeslounge-next/partials/dashboard/next-match.htm`
- Create: `themes/heroeslounge-next/partials/dashboard/standings.htm`
- Create: `themes/heroeslounge-next/partials/dashboard/notifications.htm`
- Create: `themes/heroeslounge-next/partials/dashboard/matches.htm`
- Create: `themes/heroeslounge-next/partials/dashboard/results.htm`
- Modify: `themes/heroeslounge-next/pages/home.htm` (dashboard branch)

Layout = `dashboard-v2.html` exactly: welcome strip; full-width next-match; symmetric grid standings|notifications, results|matches.

- [ ] **Step 1: welcome** — eyebrow (region/season/round from user's first team's active division season), `Welcome back, {{ user.username }}.`, team chips from `user.sloth.teams` (`.cap` + gold CAPTAIN label when `team.pivot.is_captain`).

- [ ] **Step 2: next-match** — earliest upcoming match across user's teams (via `UpcomingMatches` per team, take the soonest; consult component to see if it exposes the matches list to Twig — old partial `user/matches.htm` shows the pattern). Cast panel style: shields, `[data-countdown]` on `wbp`, caster status line (`CASTER APPROVED` when any `casters` pivot approved), buttons: Reschedule → the team's match-management page, Match page → the match page (both are unported Phase 2–3 pages: use literal URL paths built from the old theme's `team/manageMatch.htm` and `match/view.htm` `url =` patterns, per the crash-course note). **Empty state:** panel body says "No upcoming matches — enjoy the off-season." with a ghost button to literal `/calendar`.

- [ ] **Step 3: standings** — port `user/standings.htm` wiring (loop `user.sloth.teams` → `team.active_divisions`, `{% component 'DivisionTable' id=div.id teamId=team.id %}`) but: tabs styled `.tab`, one visible panel. Note the old partial's `maxEntries` property is dead (not a real component property) — the real ones are `teamId` and `surroundingEntries` (default 4); don't cargo-cult `maxEntries`. This reuses the theme-level DivisionTable override created in Task 8 Step 4 (its `teamId` mode: window around the team, `.you` highlight) — extend that override here if anything is missing rather than creating a second one.

- [ ] **Step 4: notifications** — **known upstream limitation:** the notifications pipeline is currently dead in the codebase — `NotificationHelper` exists but its only call site (the `Session::put('notifications', …)` on `rainlab.user.login`) is commented out in `plugins/rikki/heroeslounge/Plugin.php` `boot()` (~lines 114–117), and the old partial reads a session key that is never written. Port the old partial's read logic as-is (so the panel lights up if upstream re-enables it), restyle as `.note` rows (`.hot` red icon for action-needed types, mono timestamps), and ship the designed empty state ("No notifications.") as the *expected* Phase 1 state. Do NOT try to fix the pipeline — that's a plugin change, out of scope. Omit "MARK ALL READ" (no backend flow exists).

- [ ] **Step 5: matches** — `UpcomingMatches` (14 days) per mockup: `.mrow` rows, `.when` mono datetime (already timezone-converted by SetTimezone), `.pill` states: `ok` "Scheduled" (`wbp` set), `warn` "Propose time" (no `wbp`), `cast` "Cast requested" (casters pending). **Empty state:** single muted row "Nothing scheduled in the next 14 days."

- [ ] **Step 6: results** — recent played matches in the user's divisions (`RecentResults` component, consult old `user/recentResults.htm`), `.mrow` with mono scores, winner in storm.

- [ ] **Step 7: Verify with real data** — log in (README: real username, password `1234`), check `/`: five data panels against DB truth (user with teams; also verify a team-less user gets sensible empty states); the notifications panel is *expected* to show its empty state (pipeline disabled upstream, see Step 4). 360px mobile stacking.

- [ ] **Step 8: Commit** — `git commit -m "feat(theme-next): logged-in dashboard"`

---

### Task 10: Blog pages

**Files:**
- Create: `themes/heroeslounge-next/pages/blog/list.htm` (url `/blog`)
- Create: `themes/heroeslounge-next/pages/blog/post.htm` (url `/blog/post/:slug`)
- Create: `themes/heroeslounge-next/pages/blog/category.htm` (url `/blog/category/:slug`)
- Create: `themes/heroeslounge-next/pages/blog/tag_posts.htm` (match old URL)
- Modify: `themes/heroeslounge-next/assets/css/pages.css`

- [ ] **Step 1: Copy URLs + component config exactly** from the old theme's four blog pages (front-matter: blogList/blogPost/blogCategories properties, pagination) — URLs must not change.

- [ ] **Step 2: list/category/tag markup** — `.post-grid` of `.post` cards + section header + chamfered pagination (restyle old `partials/blog/pagination.htm` pattern in new classes).

- [ ] **Step 3: post page** — article layout: eyebrow (date · author), Chakra Petch title, post content in a readable measure (`max-width: 72ch`), styled `content` typography in `pages.css` (h2/h3, blockquote with storm left bar, images chamfered, code in mono on panel background), tag row, related posts via old `blogRelated` partial pattern if trivially portable — otherwise skip (YAGNI).

- [ ] **Step 4: Verify** — `/blog`, a real post, a category page: readable, styled, pagination works, old URLs intact.

- [ ] **Step 5: Commit** — `git commit -m "feat(theme-next): blog pages"`

---

### Task 11: Maintenance page + finishing pass

**Files:**
- Create: `themes/heroeslounge-next/pages/maintenance.htm`
- Modify: various (fixes)

- [ ] **Step 1: maintenance.htm** — copy URL/settings from old theme's `maintenance.htm`; `focused` layout, big Chakra Petch "Down for maintenance", storm accent, Discord link.

- [ ] **Step 2: Responsive + a11y sweep** — every Phase 1 page at 360/768/1200px; keyboard-tab through nav, tabs, forms (focus visible everywhere); reduced-motion check (ticker static, no pulse); contrast spot-check muted text on panels (≥ AA).

- [ ] **Step 3: Twig/console hygiene** — `storage/logs` clean of theme errors; browser console clean.

- [ ] **Step 4: Commit** — `git commit -m "feat(theme-next): maintenance page + phase 1 polish"`

- [ ] **Step 5: Update plan checkboxes, then report Phase 1 done** — Phase 2 (season/division/match/brackets) gets its own plan per the spec.

---

## Verification philosophy

No theme PHP is written, so there are no unit tests to add. Every task's "verify" step is a real check against the running October instance (Docker, `localhost:8090`, fixture DB per Task 0.5 — the real team dump replaces it later) — not against static mockups. A task is not done while its page 500s, logs Twig errors, renders unstyled, or shows a blank section where an empty state should be. When a data binding can't be confirmed from code, confirm it in the browser against the DB before committing.
