# Phase 3 — /user auth/account/profile + Events/Guides nav fixes (design)

**Date:** 2026-07-24 · **Branch:** `ui-rework-phase-2` (continues at the merged
Wave-1 tip) · **Status:** designed autonomously per the standing subagent-driven
workflow; decisions below follow the approved Phase-1/Phase-2 specs and do not
re-litigate them.

## Goal

Close the highest-impact frontend gap in `heroeslounge-next`: the entire `/user`
auth/account/profile system (Sign in / Join Season / avatar / notifications nav
targets are all dead), plus make the two dead nav links (Events, Guides) resolve.

## Scope

**In (six deliverables):**

1. `/user/:code?` — account page (`pages/user/account.htm` + `SlothAccount`
   override set). Guest: sign-in + register. Authed: activation check + tabbed
   account management.
2. `/user/view/:id` — public profile (`pages/user/view.htm` + `Profile`
   override + new `slothStatistics` override; reuses the existing Task-7
   `roundMatches`/`timeLine` overrides).
3. `/user/forgotpassword/:code?` — password restore/reset
   (`pages/user/forgotpassword.htm` + `SlothResetPassword` override set).
4. `/user/casterschedule` — caster's personal schedule
   (`pages/user/casterschedule.htm` + `CasterSchedule` override).
5. **Guides fix:** port the RainLab.Pages **guides subtree** (landing
   `/guides` + 6 sub-guides) into the new theme with a new `static` layout.
   Nav `/guides` + footer `/guides/signup-guide`, `/guides/captains-guide`
   then resolve.
6. **Events fix:** the nav already points at `/blog/category/events`
   (editorial decision from Phase 2, stands) and that URL **works in dev**
   (the `fixtures:live-data` seed created the Indikator `events` category —
   verified live 200 this session; the earlier "category missing" readings
   were queries against the WRONG plugin's tables: this site's blog is
   **Indikator.Content**, not RainLab.Blog). Remaining work: port the legacy
   `/events/archive` page (frozen-URL mandate) + record the **prod cutover
   content-op**: create the `events` blog category (slug `events`) in prod, or
   the nav link 404s there.

**Out (unchanged Wave-2/later scope — do not creep):** `/application*` pages
(the account Applications tab links there via literal URL; 404 until ported),
team create/manage/match, faq/contact/search/statistics, `/general/*`,
`/divisionS/*`, privacy/staff/ruleset/hall-of-fame static pages, notifications
pipeline (disabled upstream — Session::put commented out; the Notifications tab
renders only if session data exists, i.e. never, which is faithful), RSS,
timezone page.

## Approaches considered

- **A (chosen): established Wave-1 re-skin pattern.** Theme pages replicate the
  old front-matter verbatim; plugin components stay frozen; markup is re-skinned
  via component-partial overrides in ALL-LOWERCASE dirs. Zero plugin edits, all
  AJAX/field/URL contracts byte-preserved. This is the pattern proven across
  Tasks 1–9 (incl. the `gameStatistic`/`divisiontable` override precedents).
- **B (rejected): hand-rolled auth pages posting to RainLab handlers directly.**
  Violates the frozen-plugin + faithful re-skin mandates; duplicates validation
  and breaks activation/redirect flows.
- **C for Guides (rejected variants):** (i) placeholder landing only — leaves
  footer sub-guide links dead and buys nothing (the sub-guides are the useful
  content for Join Season); (ii) drop the nav link until Wave 2 — user asked to
  fix it, and guides directly support the Phase-3 signup flow. Chosen: port the
  guides subtree now (it is self-contained: 7 content files + a pruned
  `static-pages.yaml` + one layout), leave the REST of the static pages to
  Wave 2.

## Old-theme inventory (verified this session, first-hand)

### 1. Account `/user/:code?` — `SlothAccount` (extends RainLab `Account`)

- Page front-matter: `title=Account`, `layout=plain`, `[SlothAccount]
  redirect="user/account" paramCode="code" forceSecure=1`. Thin body:
  `{% component 'SlothAccount' %}`.
- Component (`plugins/rikki/heroeslounge/components/SlothAccount.php`, 505 l):
  init() addJs's `selectFile.js`, loads `roles` + `regions`; when logged in
  adds sub-component `ViewApps` as runtime alias **`viewApps`** and exposes
  `sloth`, `seasons`, `appsCount`. AJAX handlers: `onSignin`, `onRegister`,
  `onUpdateGeneral`, `onUpdateAvatar`, `onUpdateBanner`, `onUpdateDescription`,
  `onUpdateLinks`, `onSyncDiscord`, `onUpdateGame`, `onSubscribeNewsletter`,
  `onUnsubscribeNewsletter`, plus inherited `onSendActivationEmail`.
- Partials (`components/slothaccount/`):
  - `default.htm` (15 l): guest → two-column Sign in / Register; authed →
    `activation_check` + `update`. Uses `{% partial __SELF__ ~ '::name' %}`.
  - `signin.htm` (25 l): `data-request="onSignin" data-request-flash`; fields
    `login`, `password`; `{{ loginAttributeLabel }}` (renders "Email" — login
    is by EMAIL on this install); link `{{ 'user/forgotpassword'|page }}`.
  - `register.htm` (78 l): ONE form `data-request="onRegister"`; the visible
    "Register" button opens a Bootstrap **modal** (body = old-theme partial
    `ruleSet/default`, 24 lines of welcome prose — NOT the actual rules);
    the modal's "I'm in - Register!" is the real submit. Fields: `username`,
    `email` + `email_confirmation`, `battle_tag`, `discord_tag`, `region_id`
    (select from `__SELF__.regions`, placeholder option value 0), `password`
    + `password_confirmation`, `newsletter_subscription` checkbox. Discord
    warning alert with invite link.
  - `activation_check.htm` (9 l): `if not user.is_activated` → verify notice +
    `data-request="onSendActivationEmail"` link.
  - `update.htm` (305 l): Bootstrap navbar+nav-tabs driving 6 `tab-pane`s —
    General (form_open onUpdateGeneral: `username` via form_value, `password`,
    `password_confirmation`; separate newsletter sub/unsub forms), Media
    (avatar + banner upload forms, `files:true`, `.fileselect` markup, ids
    `avatarUploadError`/`bannerUploadError`, preview `<img>`s), Social
    (onUpdateDescription textarea `short_description` maxlength 255; a plain
    `<form class="form-inline">` whose submit button carries
    `data-request="{{__SELF__}}::onUpdateLinks"` — fields `facebook_url`,
    `twitter_url`, `twitch_url`, `youtube_url`, `website_url`, `discord_tag`
    [disabled + `onSyncDiscord` sync button when `discord_id` set],
    `battle_tag` [always disabled], country via old-theme partial
    `country-state/default` (`form_select_country` helper from
    RainLab.Location), `region_id` EU/NA select), Game (onUpdateGame:
    `role_id` select from `__SELF__.roles`, hidden `tab=game`, NA-only
    `server_preference` select), Applications (`blockquote` link to
    `{{ 'application/create'|page }}` + `{% component 'viewApps' %}`; tab label
    shows `appsCount`), Notifications (conditional on
    `this.session.get('notifications')`; loops alert rows).
  - `deactivate_link.htm` — **DEAD: referenced nowhere in the repo**
    (grep-verified). NOT ported.
- `selectFile.js` contract (frozen plugin asset, auto-loaded): file input
  inside `.fileselect` + sibling `:text` gets the chosen filename; PNG/100MB
  client checks write into `#{fieldName}UploadError`. The re-skinned Media tab
  must keep `.fileselect`, the readonly text input, the `avatar`/`banner`
  input names + `accept="image/png"`, and both error-div ids.
- `ViewApps` (`components/ViewApps.php` + `viewapps/default.htm`, ~2 KB):
  applications list — needs a small override (`partials/viewapps/`).
- **forceSecure gotcha (verified in RainLab source):** `Account::onRun()` →
  `redirectForceSecure()` 302s any non-AJAX plain-http GET to `https://…`.
  Dev serves plain http → the account page will NOT render in dev with
  `forceSecure=1`. AJAX handlers are exempt (`Request::ajax()`)。 DECISION:
  keep `forceSecure = 1` verbatim (prod parity); dev live-verification flips
  it to 0 locally, restores before commit; document in dev/README.

### 2. Profile `/user/view/:id` — `Profile` (loungeviews, 81 l)

- Page: `layout=plain-with-sidebar`, `[Profile] maxTimelineEntries=5`.
- init(): addCss ssbuttons (absent marketplace plugin — known 404, harmless);
  loads `sloth` by route `id`; adds sub-components `TimelineEntries` →
  **`timeLine`**, `SlothStatistics` → **`slothStatistics`** (sloth_id), and
  `RoundMatches` → **`roundMatches`** (type='sloth', id, showLogo+showName).
- Partial (132 l): ENTIRE page gated `{% if user %}` (guests get "You must be
  logged in…" — faithful, keep). Jumbotron banner (sloth.banner |
  `bg_CCC.png` fallback) + 110px avatar (user.avatar | `profile-icon.png`
  fallback) + `sloth.title` h1 + social links (twitter/facebook/twitch/
  youtube/website). Info card: role (SVG icon — already ported to new-theme
  `assets/img/roles/` in Task 5), battle_tag (deep-links HeroesProfile when
  `heroesprofile_id`), discord_tag, **MMR row always renders**
  (`heroesprofile_mmr`), country, birthday, description (striptags). Teams
  card (logo + `team/view` link). Matches: `{% component 'roundMatches' %}`.
  Statistics + timeline: `slothStatistics` + `timeLine id=sloth.id`.
  Trailing `{% put scripts %}` unmasks `.result/.f100/.score` spoilers —
  old-markup-specific; the re-skin expresses the same intent (profile match
  history is spoiler-REVEALED) through the shared card's `revealScore`
  contract instead of a DOM patch.
- Task-7 overrides `partials/roundMatches/` + `partials/timeLine/` already
  exist and resolve for these exact aliases; `timeLine`'s shared
  `division/timeline-entries.htm` was built anticipating this page. NEW
  override needed: `partials/slothstatistics/` (re-skin like Task-7
  `teamStatistics`: static tables, NO DataTables; hero picks/winrate blind —
  gameparticipation = 0 rows).

### 3. Forgot password `/user/forgotpassword/:code?` — `SlothResetPassword`

- Extends RainLab `ResetPassword`; onRun redirects logged-in users home;
  handlers `onRestorePassword` (emails `rainlab.user::mail.restore`) and
  `onResetPassword`. Partials: `default.htm` (route on `__SELF__.code`),
  `restore.htm` (email form; `data-request-update` swaps in the `reset`
  partial), `reset.htm` (code prefilled + new password; swaps in `complete`),
  `complete.htm` (1 line). Keep the `#partialUserResetForm` update-target id
  and partial names byte-exact.

### 4. Caster schedule `/user/casterschedule` — `CasterSchedule`

- Page gates `{% if user %}` around the component (guest text fallback).
- init() (only when user): adds THREE `UpcomingMatches` instances under
  runtime aliases **`upcomingMatchesPending` / `upcomingMatchesAccepted` /
  `upcomingMatchesDenied`** (type='caster', casterFilter pending/accepted/
  denied ⇒ `match_caster.approved` 2/1/0, window today→+50d).
- Partial (18 l): `if user and can('cast_matches')` → three h3 sections
  rendering the three components; else "You must be a caster…".
- **Override strategy:** the three runtime aliases match NEITHER the existing
  `partials/upcomingMatches/` dir nor any lowercase dir → the plugin's
  Bootstrap partial would leak (exact Task-7 casing lesson). Chosen: override
  `partials/casterschedule/default.htm` and render the lists with the
  established onRender pattern (`{% do upcomingMatchesPending.onRender() %}` +
  shared themed match rows), NOT `{% component %}`; no stub override dirs.

### 5. Guides (RainLab.Pages static pages)

- Old theme `content/static-pages/`: `guides.htm` (15 l landing, url
  `/guides`) + 6 children (`signup-guide` 193 l, `captains-guide` 203 l,
  `scheduling-and-reporting-matches` 87 l, `guide-scheduling-and-playing-
  your-frist-game` [sic — frozen URL] 127 l, `uploading-replays` 40 l,
  `aram-signup-guide` 54 l). Tree defined in `meta/static-pages.yaml`
  (31 l total; guides subtree cleanly separable).
- Port: copy the 7 content files + a PRUNED `meta/static-pages.yaml`
  containing ONLY the guides subtree into `themes/heroeslounge-next`; new
  `layouts/static.htm` = default chrome + `[staticPage]` + `.wrap` prose
  container around `{% page %}` + `<h1>` from the viewBag title; each ported
  file's `layout` field edited `plain`/`plain-with-sidebar` → `static`
  (content files are ported theme content, NOT frozen plugin code — editing
  their layout ref is sanctioned; body HTML stays verbatim, styled by the
  theme's prose CSS from Task 10).
- ARAM open decision pending → `aram-signup-guide` IS ported (frozen URL;
  deleting later is a file delete). `aram-league-ruleset` is NOT in the
  guides subtree — stays Wave 2.
- The old sidebar (blog widgets) is NOT ported — Wave-1 precedent; guides
  render single-column prose.

### 6. Events

- Nav Events href stays `{{ 'blog/category'|page({slug:'events'}, false) }}`.
  Dev: WORKS (446 Indikator posts, category id 28 `events` seeded
  2026-07-23; live 200 verified this session). Prod: category must be
  created at cutover (content-op — add to hardening list).
- Port legacy `/events/archive`: copy `meta/menus/events_archive.yaml`
  verbatim (incl. its typo'd URLs — frozen data) + new
  `pages/events/archive.htm` re-skinning the staticMenu accordion as native
  `<details>/<summary>` groups (season-archive Task-8 pattern).

## Cross-cutting decisions

- **Layouts:** all four /user pages + events archive use `layout = "default"`
  (new theme has no plain/plain-with-sidebar; Wave-1 precedent, e.g. Task 4).
  Guides use the new `static` layout.
- **Override dirs ALL-LOWERCASE** (Task-7 rule): `slothaccount/`,
  `slothresetpassword/`, `casterschedule/`, `profile/`, `viewapps/`,
  `slothstatistics/`. Named sub-partials (`::signin` etc.) override in the
  same dirs; the earliest task (forgot password) proves the named-partial +
  `data-request-update` override mechanics before the big account page.
- **Bootstrap → theme vocabulary:** nav-tabs → lounge.js `[data-tabs]`;
  modal → native `<dialog>` (with a few lines of page-scoped JS; the register
  submit button stays type=submit INSIDE the form); collapse → `<details>`;
  alerts → themed notice panels; `wow`/animate classes dropped.
- **All AJAX/data contracts byte-preserved:** handler names, field names,
  `data-request-flash`, `data-request-update` targets, form_open/form_value
  helpers, `.fileselect` + error-div ids, `{{ __SELF__ }}::handler` prefixes,
  disabled battle_tag/discord_tag semantics, hidden `tab=game` input.
- **Literal-URL rule:** `application/create|page` would resolve against the
  active theme (no such page) → emit literal `/application` with the standard
  deferred-literal comment. `user/forgotpassword|page` KEEPS the |page filter
  (the page ships in this phase). Same-phase cross-links may use |page.
- **Frozen quirks replicated, deviations code-commented** (Wave-1 policy):
  e.g. register's stray `</option selected>` typo is NOT replicated (invalid
  HTML with zero behavior — fixing is the established "objectively better,
  code-commented" class), the MMR row still renders when null (faithful).

## Verification strategy (dev)

- Sign-in/account/profile: real-dump user id 41 (DOF captain, password
  `dev12345` via tinker — Task-7 precedent). forceSecure temporarily 0 during
  Playwright passes (restored before commit).
- Register/newsletter/Discord-sync: **dev-blind** (no Discord/MailChimp
  creds) — markup + client validation + handler wiring verified; full flows
  documented as verify-blind.
- Forgot password: restore form live; the emailed code can be minted via
  tinker to exercise reset + complete.
- Avatar/banner upload: live PNG upload via Playwright.
- Caster schedule: needs a `cast_matches` user — find one in the dump + set
  password via tinker; if none exists, gate + empty states verify, lists are
  data-blind.
- Events/guides: plain HTTP + visual passes; `/blog/category/events` 200.
- Standard hygiene every task: 0 ERROR/exception log lines, clean console
  (bar known dev-data artifacts), 360/768/1200 responsive, one `<h1>`.

## Task breakdown (for the plan)

1. Forgot password page (smallest; proves named-partial overrides + AJAX
   partial swap).
2. Account — guest half (signin, register, ruleset partial port, dialog).
3. Account — authed half (tabs, 6 forms, media uploads, viewapps override,
   activation check).
4. Profile page (+ slothstatistics override).
5. Caster schedule.
6. Events archive port + Events link verify.
7. Guides port (static layout + content + pruned meta).
8. Phase-3 finishing pass (responsive/a11y/log sweep, nav/footer link audit,
   PROGRESS/NEXT-SESSION updates).
