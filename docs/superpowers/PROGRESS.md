# UI Rework — Progress Tracker

**This file is the single source of truth for execution progress.** Update it at
every task/review transition. (The plan file's inline checkboxes are NOT
maintained — this table is.)

- Spec: `docs/superpowers/specs/2026-07-03-ui-ux-rework-design.md`
- Plan being executed: `docs/superpowers/plans/2026-07-03-ui-rework-phase-1.md`
- Branch: `ui-rework` (fork V-Simos/HeroesLounge, upstream Fabian-Sommer/HeroesLounge)
- Process: superpowers subagent-driven development — per task: fresh implementer
  subagent → spec-compliance review → code-quality review → (fixes → re-review) → next task.

## Task status (Phase 1)

| Task | Implemented | Spec review | Quality review | Commits |
|---|---|---|---|---|
| 0 — Verify dev env | ✅ resolved: no Vagrant/dump; Docker chosen | n/a | n/a | 7b4d7d2 (plan amendment) |
| 0.5 — Docker dev env + fixtures | ✅ | ✅ | ✅ (fixes applied + re-approved) | 806e802, b2f7d5e, 520336a, 902c7c6, 559d994 |
| 1 — Theme skeleton | ✅ | ✅ | ✅ approved (minor notes → Task 6) | 619f7db |
| 2 — Self-hosted fonts | ✅ | ✅ | ✅ | d932116 |
| 3 — Design tokens + base styles | — | — | — | |
| 4 — Component library CSS | — | — | — | |
| 5 — lounge.js | — | — | — | |
| 6 — Layouts + site chrome | — | — | — | |
| 7 — Homepage static sections | — | — | — | |
| 8 — Homepage data sections | — | — | — | |
| 9 — Dashboard (logged-in home) | — | — | — | |
| 10 — Blog pages | — | — | — | |
| 11 — Maintenance + finishing pass | — | — | — | |

**Next action:** Task 3 (design tokens + base styles).

**Notes from Task 2 reviews:**
- 8 woff2 files, not 9 — the plan header said 9 but the variant list totals 8
  (plan doc fixed). Weight↔file mapping verified at binary level (fonttools).
- Latin-only subset lacks latin-ext glyphs (ł, č, ş, ő…) — EU roster names will
  per-glyph fall back mid-heading. Watch during visual QA with real data; fix
  would be adding latin-ext subsets (Phase-level decision, not a Task 2 defect).
- For Task 3 wiring: load fonts.css before other CSS; preload only
  chakra-petch-700 + barlow-regular with `crossorigin` (required even
  same-origin); no italic faces shipped → `<em>` synthesizes oblique (check one
  real blog post during QA).

**Deferred minor notes from Task 1 quality review (address in Task 6 when
default.htm is rewritten):**
- theme.yaml `require` list is aspirational (lists RainLab.Blog unused; omits
  RainLab.User / Rikki.LoungeViews that the layout actually uses) — true up or comment.
- `<title>{{ this.page.title }} — Heroes Lounge</title>` renders a dangling
  "— Heroes Lounge" if a page has an empty title.
- Layout has no `{% styles %}` / `{% scripts %}` / `{% framework %}` and no CSRF
  meta yet — required before any data-request/AJAX work (Task 5 wires framework).
- `lang="en"` hardcoded; switch to active locale when translation work starts.

## Resuming a session

1. Ensure Docker Desktop is running, then from repo root:
   `docker compose -f dev/docker-compose.yml up -d`
   (containers don't auto-start after reboot; DB data persists in the volume).
2. Site: http://localhost:8090 — should show the `heroeslounge-next` placeholder
   ("heroeslounge-next lives"). If it shows the OLD theme, re-run
   `docker compose -f dev/docker-compose.yml exec -T web php artisan theme:use heroeslounge-next`.
3. If the DB is ever empty/broken: reseed with
   `docker compose -f dev/docker-compose.yml exec -T web php artisan fixtures:seed --force`
   (destructive — see dev/README.md).
4. Credentials and env details: `dev/README.md`
   (backend admin/dev12345 + editor/dev12345; fixture users password dev12345).

## Standing decisions (from the approved spec — do not re-litigate)

- New theme `themes/heroeslounge-next` only; NOTHING under `plugins/rikki/` or
  `themes/HeroesLounge-Theme/` may be modified.
- Design North Star: `docs/superpowers/specs/assets/reference-design.html`;
  authoritative v2 tokens/type scale: `docs/superpowers/specs/assets/dashboard-v2.html`.
- Hand-rolled CSS (no build step), vanilla JS, self-hosted fonts, inline lucide SVGs.
- Buttons/active tabs: dark text `#041020` on storm-blue gradient (decided; contrast-verified).
- Dual homepage: logged-out marketing page / logged-in dashboard, same URL.
- All existing site URLs are frozen.

## Hard-won gotchas (verified this session — trust these)

- October v1 build bug: `v`-prefixed keys in version.yaml loose-compare to 0 →
  plugin/theme silently skipped. Never prefix versions with `v`.
- `Indikator.Content` marketplace plugin is unobtainable (source deleted); dev
  shim lives at `dev/docker/plugins/indikator/content` — swap instructions in dev/README.md.
- RainLab.GoogleAnalytics IS required despite not being in theme.yaml: 10 old-theme
  layouts declare `[googleTracker]`. Removing it 500s the whole site.
- `| page` Twig filter resolves against the ACTIVE theme → for pages not yet
  ported to heroeslounge-next, hardcode literal URL paths (details in plan crash-course).
- Component partial overrides: theme `partials/<alias>/default.htm` dir must match
  the registered alias casing exactly (container FS is case-sensitive; wrong case = silently ignored).
- DivisionTable's real properties are `teamId` + `surroundingEntries`; the
  `maxEntries` seen in old partials is a dead property.
- The notifications pipeline is disabled upstream (Session::put commented out in
  `plugins/rikki/heroeslounge/Plugin.php` boot() ~114-117) — dashboard notifications
  panel legitimately shows its empty state.
- `mmr_bound` column is production drift — created by `plugins/dev/fixtures`'s
  guarded migration, not by rikki migrations.
- Host port 8080 is occupied (EnterpriseDB) — the dev site uses 8090.

## Blocked/waiting

- Real team DB dump + October Project ID: user will obtain later. When available:
  follow "swap in the real dump" in dev/README.md, stop using fixtures:seed,
  re-run visual verification of all pages built so far.
