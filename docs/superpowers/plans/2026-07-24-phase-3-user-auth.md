# Heroes Lounge UI Rework — Phase 3 Implementation Plan (/user auth + Events/Guides fixes)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.
>
> **Execution tracker:** `docs/superpowers/PROGRESS.md` is the single source of truth for status (inline `- [ ]` boxes are NOT maintained). Update the tracker at every task/review transition.

**Goal:** Port the four `/user` pages (account/sign-in/register, public profile, forgot-password, caster schedule) into `heroeslounge-next`, and make the Events + Guides nav links resolve (port `/events/archive` + the guides static-page subtree).

**Architecture:** Pure frontend re-skin, Wave-1 pattern: theme pages replicate old front-matter verbatim; frozen plugin components are re-skinned via component-partial overrides in **ALL-LOWERCASE** dirs; data/AJAX contracts byte-preserved. Guides ride a new minimal `static` layout for RainLab.Pages content.

**Tech Stack:** October CMS v1 (INI front-matter + Twig), hand-rolled CSS (tokens in `assets/css/`), vanilla JS (`lounge.js` + page-scoped `{% put scripts %}`), lucide inline SVGs.

> **Task-8 acceptance amendment — 2026-07-28:** the one-`<h1>` rule has one
> approved exception:
> `/guides/scheduling-and-playing-your-first-game` preserves the frozen source
> body's six `<h1>` section headings under Task 7's byte-verbatim fidelity
> requirement. Do not rewrite them in the re-skin; semantic demotion requires a
> separately authorized content migration. The earlier temporary
> `forceSecure=0` instructions are also superseded by the verified frozen-child
> behavior below.

**Authoritative references:**
- Phase 3 spec (binding): `docs/superpowers/specs/2026-07-24-phase-3-user-auth-design.md`
- Tracker + gotchas: `docs/superpowers/PROGRESS.md` (esp. Task-7 override-casing rule, onRender pattern, Task-5/6 conventions)
- Wave-1 plan (voice/format + crash course): `docs/superpowers/plans/2026-07-04-phase-2-wave-1-viewing.md`

---

## Crash course / conventions (read once — Phase-3 deltas on top of the Wave-1 rules)

All Wave-1 binding rules apply unchanged (pure re-skin; plugins + old theme FROZEN; frozen URLs; CSS placement invariant — components.css additions go BEFORE the focus-affordance list + reduced-motion end-block, new focusable clipped elements join the affordance list; lounge.js tab contract `[data-tabs] > .tabs > .tab[data-tab-target]` + `hidden` panels; literal-URL rule for links into unported pages; one `<h1>` per page except the acceptance amendment above; commit trailer `Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>`; commit via `git -c core.fsmonitor=false`). Phase-3 specifics:

- **Override dirs are ALL-LOWERCASE** (Task-7 rule — strtolower probe runs FIRST and matches every alias casing; dev's bind mount is case-insensitive so a live render proves NOTHING about casing — verify with `git ls-files`): `partials/slothaccount/`, `partials/slothresetpassword/`, `partials/casterschedule/`, `partials/profile/`, `partials/viewapps/`, `partials/slothstatistics/`. (Existing CamelCase dirs from Task 7 — `roundMatches/`, `timeLine/` — stay as they are; do NOT rename.)
- **Named component sub-partials override in the same dir.** The frozen partial set uses `{% partial __SELF__ ~ '::signin' %}` etc. — the theme override for `SlothAccount::signin` lives at `partials/slothaccount/signin.htm`. **Task 1 proves this mechanism** (multi-partial override + AJAX `data-request-update` partial swap) on the smallest page before the account page depends on it. Prove every new override with a temporary marker string before styling.
- **AJAX/data contracts are byte-preserved:** handler names (`onSignin`, `onRegister`, `onUpdate*`, `onSyncDiscord`, `on(Un)SubscribeNewsletter`, `onSendActivationEmail`, `onRestorePassword`, `onResetPassword`), `data-request-flash`, `data-request-update` maps + target ids (`#partialUserResetForm`), field names (incl. `email_confirmation`, `password_confirmation`, `newsletter_subscription`, `battle_tag`, `discord_tag`, `region_id`, `role_id`, `server_preference`, hidden `tab=game`), `{{ form_open(...) }}`/`form_value`/`form_close` helpers, `{{ __SELF__ }}::handler` prefixes, disabled `battle_tag`/`discord_tag` semantics.
- **`selectFile.js` markup contract** (frozen asset, auto-loaded by SlothAccount): file `<input>` inside a `.fileselect` wrapper with a sibling text input (`:text`, readonly, receives the filename), input names exactly `avatar`/`banner`, `accept="image/png"`, error divs with ids `avatarUploadError`/`bannerUploadError`.
- **forceSecure=1 (account page only, corrected):** RainLab's parent account component creates a plain-http redirect response, but frozen `SlothAccount::onRun()` calls `parent::onRun()` without returning it. Local `/user` therefore renders while the committed value remains `forceSecure = 1`. Verify with that value unchanged; do not flip it or modify the frozen child.
- **Auth for verification (corrected):** this local RainLab.User setting uses `login_attribute=username`. Standing user 41 is username `Hapcher`, email `Hapcher5166@fakegmail.com`, password `dev12345`; use the rendered login label if another environment configures email instead.
- **No frontend sign-out exists on the old site** (grep-verified: zero logout references in the old theme AND all repo plugins). Faithful port = none added. To switch users in dev: clear cookies or tinker. Parked as a post-rework UX decision.
- **Dead code is not ported:** `slothaccount/deactivate_link.htm` is referenced nowhere — skip it.
- **Blog = Indikator.Content.** Any blog/category checks go against `indikator_content_*` tables (NOT `rainlab_blog_*`). The `events` category (id 28) exists in dev via the live-data seed.
- **Sanctioned-deviation policy (Wave-1):** faithful by default; objectively-better deviations allowed ONLY where Wave 1 already sanctioned the class (invalid-HTML fixes, `onerror` image fallbacks, null-guards + onEnd-404 pattern, one-h1 heading order, a11y labels) — each gets a code comment so a faithfulness pass won't revert it.

**File map (everything Phase 3 creates/modifies):**

```
themes/heroeslounge-next/
  pages/user/{forgotpassword,account,view,casterschedule}.htm   (T1,T2,T4,T5)
  pages/events/archive.htm                                      (T6)
  partials/slothresetpassword/{default,restore,reset,complete}.htm  (T1)
  partials/slothaccount/{default,signin,register,activation_check}.htm (T2)
  partials/slothaccount/update.htm                              (T3)
  partials/viewapps/default.htm                                 (T3)
  partials/site/ruleset.htm         (ported ruleSet prose)      (T2)
  partials/user/country-select.htm  (ported country-state)      (T3)
  partials/profile/default.htm                                  (T4)
  partials/slothstatistics/default.htm                          (T4)
  partials/casterschedule/default.htm                           (T5)
  layouts/static.htm                                            (T7)
  content/static-pages/guides*.htm  (7 files)                   (T7)
  meta/static-pages.yaml            (guides subtree ONLY)       (T7)
  meta/menus/events_archive.yaml    (verbatim copy)             (T6)
  assets/css/pages.css        (auth/account/profile/guides/events blocks)
  assets/css/components.css   (only if a truly shared class is needed)
dev/README.md                 (forceSecure dev note)            (T2)
docs/superpowers/PROGRESS.md  (every transition)
```

---

## Task 1 — Forgot password `/user/forgotpassword/:code?` (smallest; proves the override mechanics)

**Files:**
- Create: `themes/heroeslounge-next/pages/user/forgotpassword.htm`
- Create: `themes/heroeslounge-next/partials/slothresetpassword/{default,restore,reset,complete}.htm`
- Modify: `themes/heroeslounge-next/assets/css/pages.css` (new `.auth-*` block — shared by Tasks 1–3: narrow centered panel, form rows, notice box)
- Frozen sources to read first: `themes/HeroesLounge-Theme/pages/user/forgotpassword.htm`, `plugins/rikki/heroeslounge/components/slothresetpassword/*.htm`, `plugins/rikki/heroeslounge/components/SlothResetPassword.php`

- [ ] **Step 1: Read the frozen sources.** Copy down the EXACT `data-request`, `data-request-update` and wrapper-id contracts from `restore.htm`/`reset.htm` (the update map references component partial names — keep them verbatim; the AJAX response renders the THEME override once the override exists, same mechanism as `gameStatistic` in Task 5).
- [ ] **Step 2: Page + marker overrides.** Page front-matter VERBATIM from the old page except `layout = "default"` (sanctioned mapping): `title = "Forgot Password"`, `url = "/user/forgotpassword/:code?"`, `[SlothResetPassword] paramCode = "code"`. Body: `{% component 'SlothResetPassword' %}`. Create the four override partials containing only marker strings; verify each renders (default → restore on `/user/forgotpassword`; reset via a `/user/forgotpassword/FAKECODE` visit) — proving named-partial override resolution.
- [ ] **Step 3: Re-skin.** `default.htm`: route on `__SELF__.code` exactly as frozen. `restore.htm`: `.p` panel, lead copy + the Hotmail warning as a themed notice, email field (label + `.input`), submit `.btn`; keep `data-request="onRestorePassword"`, `data-request-flash`, the `data-request-update` map and the `#partialUserResetForm` wrapper id byte-exact. `reset.htm`: code (prefilled `{{ __SELF__.code }}`) + password fields, same contracts. `complete.htm`: frozen copy verbatim in a themed panel.
- [ ] **Step 4: Verify live.** (a) `/user/forgotpassword` 200, themed restore form, 0 Bootstrap classes; (b) submit a real dump-user email → October AJAX runs (dev mail may fail → flash error is ACCEPTABLE and documented; the partial-swap contract is what's being verified — if mail throws before the swap, mint the flow instead: tinker `$u->getResetPasswordCode()` (RainLab User model) → visit `/user/forgotpassword/{id}!{code}` → reset partial shows with code prefilled → submit new password → complete partial → sign-in works with the new password (restore `dev12345` after if you used user 41); (c) logged-in visit redirects home (component onRun — log in first via Task-2's flow or defer this sub-check to Task 2 and note it); (d) 360/768/1200 no overflow; (e) `storage/logs` 0 new ERROR/Twig lines, console clean.
- [ ] **Step 5: Commit** `feat(theme-next): forgot-password page (SlothResetPassword override)`.

---

## Task 2 — Account `/user/:code?` — guest half (sign in / register)

**Files:**
- Create: `themes/heroeslounge-next/pages/user/account.htm`
- Create: `themes/heroeslounge-next/partials/slothaccount/{default,signin,register,activation_check}.htm`
- Create: `themes/heroeslounge-next/partials/site/ruleset.htm` (port of old-theme `partials/ruleSet/default.htm` — 24 lines of welcome prose, re-flowed into theme typography; content verbatim)
- Modify: `themes/heroeslounge-next/assets/css/pages.css` (extend `.auth-*`: two-column guest grid → 1-col ≤760px; `<dialog>` styling)
- Modify: `dev/README.md` (forceSecure dev-verification note)
- Frozen sources: `themes/HeroesLounge-Theme/pages/user/account.htm`, `plugins/rikki/heroeslounge/components/slothaccount/{default,signin,register,activation_check}.htm`, `SlothAccount.php` (handler field expectations)

- [ ] **Step 1: Page front-matter VERBATIM** (incl. `forceSecure = 1`): `title = "Account"`, `url = "/user/:code?"`, `layout = "default"`, `[SlothAccount] redirect = "user/account"`, `paramCode = "code"`, `forceSecure = 1`. Body `{% component 'SlothAccount' %}`. **The `redirect = "user/account"` property resolves a CMS page by file name — our page at `pages/user/account.htm` satisfies it.** Keep `forceSecure = 1` unchanged during local verification per the corrected frozen-child behavior.
- [ ] **Step 2: Marker-prove** `slothaccount/default.htm` override resolves on `/user`, then re-skin: guest branch = two `.p` panels (Sign in / Register) in a responsive grid; authed branch keeps the frozen `{% partial __SELF__ ~ '::activation_check' %}` + `'::update' %}` calls (update.htm override lands in Task 3 — until then the plugin's Bootstrap update partial renders for authed users; acceptable intermediate, note in PROGRESS).
- [ ] **Step 3: signin.htm.** Fields `login` (label `{{ loginAttributeLabel }}` — renders "Email" on this install) + `password`; `data-request="onSignin" data-request-flash`; forgot-password link via `{{ 'user/forgotpassword'|page }}` (ported in T1 → |page is correct now).
- [ ] **Step 4: register.htm.** ONE form `data-request="onRegister" data-request-flash` containing: the Discord-requirement notice (invite link verbatim), all fields with frozen names (`username`, `email`, `email_confirmation`, `battle_tag`, `discord_tag`, `region_id` select over `__SELF__.regions` with the value-0 placeholder — FIX the frozen `</option selected>` invalid-HTML typo, code-commented —, `password`, `password_confirmation`, `newsletter_subscription` checkbox), and the CoC step re-skinned: Bootstrap modal → native `<dialog>` whose body is `{% partial 'site/ruleset' %}`; the visible "Register" button is `type="button"` + opens the dialog (page-scoped `{% put scripts %}` — `showModal()`/`close()`, a few lines); the dialog's "I'm in - Register!" stays `type="submit"` INSIDE the form so October's framework submits the AJAX request (close the dialog on click so the flash is visible). Keep the dialog INSIDE the form exactly like the frozen modal.
- [ ] **Step 5: activation_check.htm.** Frozen conditional + copy; `data-request="onSendActivationEmail"` link as a themed inline action (`<a href="javascript:;">` frozen idiom is fine — or `<button class="linklike">` with a code comment; pick one and be consistent with Wave-1 precedent).
- [ ] **Step 6: Verify live** (`forceSecure = 1` unchanged): (a) `/user` guest → two themed panels, dialog opens/closes, keyboard: dialog is natively focus-trapped, Esc closes; (b) register submit with empty/invalid fields → server validation flash errors render (no Discord API needed for validation-path checks); full happy-path register is **dev-blind** (Discord membership check + mail) — document; (c) sign in as the standing user through the configured login attribute → redirects to `/user` account view (authed branch renders — plugin Bootstrap update partial until T3, expected); (d) `activation_check` only shows for non-activated users (tinker: check `is_activated` on a spare user to exercise both branches if quick); (e) responsive + logs/console hygiene; (f) verify committed `forceSecure = 1`.
- [ ] **Step 7: Commit** `feat(theme-next): account page guest half (sign in / register + CoC dialog)`.

---

## Task 3 — Account authed half (update tabs) + viewapps override

**Files:**
- Create: `themes/heroeslounge-next/partials/slothaccount/update.htm`
- Create: `themes/heroeslounge-next/partials/viewapps/default.htm`
- Create: `themes/heroeslounge-next/partials/user/country-select.htm` (port of old `country-state/default.htm`: keep `{{ form_select_country('country_id', countryId, {...}) }}` — RainLab.Location markup helper, theme-wide; keep the `countryId|default(form_value('country_id'))` line)
- Modify: `themes/heroeslounge-next/assets/css/pages.css` (account tab panels, form grids, media-upload rows)
- Frozen sources: `plugins/rikki/heroeslounge/components/slothaccount/update.htm` (305 l — the contract inventory is in the spec §1), `plugins/rikki/heroeslounge/components/viewapps/default.htm`, `ViewApps.php`

- [ ] **Step 1: Tabs shell.** Replace the Bootstrap navbar/nav-tabs with the lounge.js `[data-tabs]` pattern (Task-4b/7 precedent): tab buttons General / Media / Social / Game / Applications `[{{ __SELF__.appsCount }}]` / (conditional) Notifications `[n]` — the Notifications tab keeps the frozen `{% if this.session.get('notifications') %}` gate (pipeline dead upstream → renders never; faithful).
- [ ] **Step 2: General tab.** `{{ form_open({request: 'onUpdateGeneral', model: user}) }}` + `username` (via `{{ form_value('username') }}`), `password`, `password_confirmation`, submit; then the newsletter sub/unsub paired forms exactly as frozen (`onSubscribeNewsletter`/`onUnsubscribeNewsletter`, branch on `user.sloth.newsletter_subscription`). Replace `sr-only` label idiom with visible themed labels (a11y-sanctioned class, code comment).
- [ ] **Step 3: Media tab.** Avatar + Banner forms: `form_open({... files: true})`, keep the FULL `selectFile.js` contract (`.fileselect` wrapper, hidden file input named `avatar`/`banner` with `accept="image/png"`, readonly text sibling, `#avatarUploadError`/`#bannerUploadError`, helper copy verbatim); preview `<img>` from `__SELF__.sloth.user.avatar.path` / `__SELF__.sloth.banner.path` with the Task-5 `onerror` neutral-placeholder pattern (code-commented).
- [ ] **Step 4: Social tab.** `onUpdateDescription` textarea (`short_description`, maxlength 255, striptags prefill); the links form: plain `<form>` whose SUBMIT BUTTON carries `data-request="{{ __SELF__ }}::onUpdateLinks"` (keep this exact idiom — the handler reads the whole form); fields `facebook_url twitter_url twitch_url youtube_url website_url`, `discord_tag` (disabled + `{{ __SELF__ }}::onSyncDiscord` sync button when `__SELF__.sloth.discord_id`, editable otherwise), `battle_tag` (ALWAYS disabled, frozen tooltip copy), `{% partial 'user/country-select' countryId=user.country_id %}`, `region_id` EU/NA select. Replace fa-icons with lucide `site/icon` equivalents; the two inline brand SVGs (discord/battlenet) → the ported `assets/img/{discord,battlenet}.svg`.
- [ ] **Step 5: Game tab.** `form_open({request:'onUpdateGame'})`, hidden `tab=game`, `role_id` select over `__SELF__.roles`, NA-only (`region_id == 2`) `server_preference` select, frozen option values.
- [ ] **Step 6: Applications tab + viewapps.** Blockquote → themed panel; link = **literal `/application`** (deferred-page rule + standard comment; the frozen `|page` would emit `href=""` here). `{% component 'viewApps' %}` stays; re-skin `partials/viewapps/default.htm` (marker-prove first; lowercase dir serves the `viewApps` runtime alias). **The frozen `viewapps/default.htm` has two `{{ 'application/view'|page({id: app.id}) }}` links (`/application/view/:id` is out-of-scope Phase 3) → emit them as literal `/application/view/{{ app.id }}` with the standard deferred-page comment, so the Task-8 empty-href sweep stays clean.** (They only render when the user has open applications — likely dev-blind — but the literal rule covers them regardless.)
- [ ] **Step 7: Notifications tab.** Frozen loop over `this.session.get('notifications')` → themed notice rows (drop `wow shake`).
- [ ] **Step 8: Verify live** (`forceSecure = 1` unchanged; standing user): every tab renders themed (0 `nav-tabs`/`tab-pane`/`table-striped` remnants); live POST checks — onUpdateDescription (set + revert a description), onUpdateLinks (set + revert one URL), onUpdateGame (re-save current role), onUpdateGeneral (re-save username; password change → set BACK to dev12345), avatar upload with a small real PNG (verify preview + timeline entry appears; banner same); appsCount renders; viewapps override marker seen then themed; dev-blind: Discord sync, newsletter (MailChimp), activation mail — wiring-only. Responsive + logs/console. Verify committed `forceSecure=1`.
- [ ] **Step 9: Commit** `feat(theme-next): account update tabs + viewapps override`.

---

## Task 4 — Profile `/user/view/:id`

**Files:**
- Create: `themes/heroeslounge-next/pages/user/view.htm`
- Create: `themes/heroeslounge-next/partials/profile/default.htm`
- Create: `themes/heroeslounge-next/partials/slothstatistics/default.htm`
- Modify: `themes/heroeslounge-next/assets/css/pages.css` (profile banner/info blocks — reuse `.team-*` vocabulary where it genuinely matches; do not fork near-identical CSS)
- Frozen sources: `themes/HeroesLounge-Theme/pages/user/view.htm`, `plugins/rikki/loungeviews/components/profile/default.htm` (132 l), `Profile.php`, `plugins/rikki/loungestatistics/components/slothstatistics/*` (+ its PHP), Task-7 overrides `partials/roundMatches/default.htm`, `partials/timeLine/default.htm`, `partials/teamStatistics/default.htm` (pattern donor)

- [ ] **Step 1: Page.** Front-matter verbatim (`title = "Profile"`, `url = "/user/view/:id"`, `[Profile] maxTimelineEntries = 5`), `layout = "default"`. Body `{% component 'Profile' %}`. Add the Task-5-precedent onEnd 404 (`Rikki\Heroeslounge\Models\Sloth::find(param('id'))` → 404 + title) — code-commented sanctioned deviation (frozen old page happily renders an empty shell for a bad id; the 404 pattern is the established improvement; VERIFY first what the frozen partial does on a bad id in dev and note it).
- [ ] **Step 2: profile/default.htm.** Keep the `{% if user %}` full-page gate (guest → themed "You must be logged in…" panel, frozen copy). Authed: Task-7-style banner (sloth.banner → CSS background, fallback = themed gradient — do NOT copy `bg_CCC.png`; code-comment the swap) + avatar (fallback `site/initials` pattern or the ported `profile-icon` — prefer initials, comment it) + `sloth.title` `<h1>` + social icon links (lucide; only-if-set, `rel="noopener" target="_blank"` as frozen). Info panel: role (SVG from `assets/img/roles/`, ported Task 5), battle_tag (+ HeroesProfile deep link when `heroesprofile_id` — URL shape verbatim incl. `getHeroesProfileBattletagReformatted`/`getHeroesProfileRegionId`), discord_tag, MMR (renders even when null — faithful), country, birthday, description (`|striptags`). Teams: reuse `partials/team/shield` + `/team/view/{{ team.slug }}` literals. Matches: `{% component 'roundMatches' %}` (existing override — verify it renders type='sloth' groupings; if it derefs anything team-specific, generalize WITHOUT changing team-page output — regression-check `/team/view/AO` byte-diff). Statistics + timeline: `{% component 'slothStatistics' %}` + `{% component 'timeLine' id=__SELF__.sloth.id %}`. The frozen trailing unmask script is NOT ported — the re-skin's cards render spoiler-REVEALED (`revealScore=true` equivalents; comment the intent).
- [ ] **Step 3: slothstatistics override.** Marker-prove the lowercase dir resolves the `slothStatistics` alias, then re-skin off the `teamStatistics` donor: static themed tables, NO DataTables/collapse; hero pick/winrate columns are data-blind (`gameparticipation` = 0) → verify the empty states render.
- [ ] **Step 4: Verify live** (logged in): `/user/view/{sloth-of-user-41}` + one sloth with teams+timeline (find via SQL: a sloth id with team pivots and timeline rows); guest → gate; bad id → agreed 404/empty behavior; roundMatches groups + revealed scores; team-page regression (`/team/view/AO` unchanged); responsive; ssbuttons CSS 404 = known artifact; logs/console.
- [ ] **Step 5: Commit** `feat(theme-next): user profile page (Profile + slothStatistics overrides)`.

---

## Task 5 — Caster schedule `/user/casterschedule`

**Files:**
- Create: `themes/heroeslounge-next/pages/user/casterschedule.htm`
- Create: `themes/heroeslounge-next/partials/casterschedule/default.htm`
- Modify: `themes/heroeslounge-next/assets/css/pages.css` (only if the calendar row vocabulary needs a variant)
- Frozen sources: `themes/HeroesLounge-Theme/pages/user/casterschedule.htm`, `plugins/rikki/heroeslounge/components/casterschedule/default.htm`, `CasterSchedule.php`, `UpcomingMatches.php` type='caster' branch (~lines 88–99 + onRender), Task-6 calendar partials (row vocabulary donor)

- [ ] **Step 1: Page.** Front-matter verbatim (`title = "Casterschedule"`, `url = "/user/casterschedule"`, `[CasterSchedule] daysInFuture = 50`), `layout = "default"`; body keeps the frozen page-level `{% if user %}` gate + guest copy, `{% component 'CasterSchedule' %}` inside.
- [ ] **Step 2: Override.** `partials/casterschedule/default.htm` (marker-prove): keep the frozen `{% if user and can('cast_matches') %}` gate + else-copy. The three lists do NOT use `{% component %}` (their runtime aliases `upcomingMatchesPending/Accepted/Denied` match no override dir — Task-7 casing lesson): READ `UpcomingMatches.php` to confirm which public property the type='caster' path fills in `onRender()`, then per alias `{% do upcomingMatchesX.onRender() %}` + render the themed rows (calendar/Task-7 sidebar vocabulary: `match/card` or the `.cal-*` row with kickoff time + opposing teams + division/playoff label; casters are the VIEWER here — no apply/retract UI unless the frozen partial shows it for these lists; follow the frozen partial's actual content).
- [ ] **Step 3: Verify live.** Find a caster: SQL for a user whose group/permission satisfies `can('cast_matches')` (check the rikki `can()` helper implementation for where the permission lives; the dump has 817 match_caster rows so casters exist) → tinker-password them → log in: three themed section headings render; lists populated ONLY if that caster has future seeded matches — otherwise force-assign one seeded future match via tinker (`$match->casters()->syncWithoutDetaching(...)`, approved flag per filter 0/1/2), verify each of the three filters shows it appropriately, then DETACH (restore). Non-caster user → "must be a caster" copy; guest → page-level gate. Responsive; logs/console.
- [ ] **Step 4: Commit** `feat(theme-next): caster schedule page`.

---

## Task 6 — Events archive port + Events link verify

**Files:**
- Create: `themes/heroeslounge-next/pages/events/archive.htm`
- Create: `themes/heroeslounge-next/meta/menus/events_archive.yaml` (byte-verbatim copy of the old theme's — INCLUDING its quirky URLs; frozen data)
- Modify: `themes/heroeslounge-next/assets/css/pages.css` (only if `.arch-*` needs an events variant — prefer reusing the Task-8 season-archive block as-is)
- Frozen source: `themes/HeroesLounge-Theme/pages/events/archive.htm` + `meta/menus/events_archive.yaml`

- [ ] **Step 1: Copy the menu yaml verbatim** (`meta/menus/` dir is new in this theme). **Step 2: Page** front-matter verbatim (`title = "Events Archive"`, `url = "/events/archive"`, `layout = "default"`, drop old-only keys `contentType`/`force_show` if the new-theme pages don't carry them — check a Wave-1 page for precedent; keep `[staticMenu staticMenuEventsArchive] code = "events_archive"`). Body: `<h1>Events Archive</h1>` + the Task-8 `<details>/<summary>` accordion over `staticMenuEventsArchive.menuItems` — iterate each `item`'s children via `{% for child in item.items %}` (frozen uses `item.items`, NOT `item.children`), `item.title` as the summary, `<a href="{{ child.url }}">{{ child.title }}</a>` — URLs verbatim, several point at old-era pages/anomalies; do NOT "fix" them).
- [ ] **Step 3: Verify.** `/events/archive` 200: 3 groups, entries listed, details keyboard-toggle; nav Events `/blog/category/events` still 200 (dev data); links go where the yaml says (200 or graceful not-found for stale slugs — note, don't fix). Add the **prod content-op** (create `events` Indikator category at cutover) to PROGRESS's pre-production hardening list. Responsive; logs/console.
- [ ] **Step 4: Commit** `feat(theme-next): events archive page + menu`.

---

## Task 7 — Guides static-pages port

**Files:**
- Create: `themes/heroeslounge-next/layouts/static.htm` (mirror default.htm chrome/components — `[session] security="all"`, `[Navigation] paramCode=""`, `[SetTimezone]`, `[staticPage]` — with `{% page %}` inside a `.wrap` + prose container; reuse the Task-10 blog-post prose class, check `pages/blog/post.htm` for its name)
- Create: `themes/heroeslounge-next/content/static-pages/{guides,guides-signup-guide,guides-new-captains-guide-heroes-lounge,guides-scheduling-and-reporting-matches,guides-guide-scheduling-and-playing-your-frist-game,guides-uploading-replays,guides-aram-signup-guide}.htm` — copies; ONLY the viewBag `layout` value changes (`plain`/`plain-with-sidebar` → `static`); body HTML verbatim
- Create: `themes/heroeslounge-next/meta/static-pages.yaml` — guides subtree ONLY (root `guides` + its 6 children, same order, `{ }` leaves)
- Modify: `themes/heroeslounge-next/assets/css/pages.css` (guides landing list styling only if needed)

- [ ] **Step 1: Layout + meta + files** as above. **Heading order:** check whether guide content opens with its own `<h1>` — if not, the layout/page emits `<h1>{{ this.page.title }}</h1>` (one-h1 rule); if content h1s exist, don't double (decide once, comment it).
- [ ] **Step 2: Cache.** RainLab.Pages routes/lists are cached — after adding files run `php artisan cache:clear` in the web container before judging 404s.
- [ ] **Step 3: Verify.** All 7 URLs 200 with themed chrome + prose styling (`/guides`, `/guides/signup-guide`, `/guides/captains-guide`, `/guides/scheduling-and-reporting-matches`, `/guides/guide-scheduling-and-playing-your-frist-game`, `/guides/uploading-replays`, `/guides/aram-signup-guide`); nav Guides + footer `/guides/signup-guide` + `/guides/captains-guide` resolve; content images may 404 (uploaded media absent in dev — known artifact class, note); guides landing lists/links per its content; the OTHER static pages (privacy etc.) remain un-ported (out of scope) — confirm no accidental route grabs. Responsive; logs/console.
- [ ] **Step 4: Commit** `feat(theme-next): guides static pages + static layout`.

---

## Task 8 — Phase-3 finishing pass

**Files:** docs only (unless defects found): `docs/superpowers/PROGRESS.md`, `docs/superpowers/NEXT-SESSION.md`, `docs/superpowers/KNOWN-ISSUES.md`, possibly `dev/README.md`

- [ ] **Step 1: Committed-tree checks.** `git show HEAD:themes/heroeslounge-next/pages/user/account.htm | grep forceSecure` → MUST be `1`; `git ls-files themes/heroeslounge-next/partials | grep -iE 'slothaccount|slothresetpassword|casterschedule|profile|viewapps|slothstatistics'` → all-lowercase dirs; no stray dev flips or temp markers anywhere (`git grep -n MARKER themes/heroeslounge-next` empty).
- [ ] **Step 2: Sweep** every Phase-3 page (guest + authed states) at 360/768/1200: no page-level horizontal overflow, one `<h1>` except the approved byte-faithful First Game body (six), no empty/`#` hrefs, focus rings on new focusable elements (dialog buttons, details summaries, tabs — chamfered ones in the affordance list), reduced-motion neutralizes any new transition, console + `storage/logs` clean (bar documented artifacts), no Bootstrap class remnants on themed surfaces (`nav-tabs|tab-pane|form-control|card-body|jumbotron|modal` grep against rendered HTML).
- [ ] **Step 3: Restore + document.** All tinker data mutations restored (passwords you changed back, detached casters, reverted profile fields); document the standing dev conveniences (user-41 password) in dev/README if kept. Update KNOWN-ISSUES (severity rows 1/2/3 → resolved-by-port), PROGRESS (task table + notes + prod-cutover content-op + any new gotchas), NEXT-SESSION (launch pad → next: Wave-2 remainder / finishing-a-development-branch).
- [ ] **Step 4: Commit** `docs(progress): Phase 3 complete — finishing pass`.
