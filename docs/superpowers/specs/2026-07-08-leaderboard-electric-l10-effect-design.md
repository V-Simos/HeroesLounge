# Leaderboard "Storm" effect — L10-driven electric rows

**Date:** 2026-07-08
**Theme:** `themes/heroeslounge-next`
**Status:** Design — pending review

## Goal

Replace the static gold-trophy treatment on the division standings with a
Heroes-of-the-Storm-flavoured **electric** treatment: qualifying team rows get a
solid electric-blue border, a subtle travelling highlight, and a periodic
lightning strike. Intensity scales in **three tame stages** driven by a team's
**L10** (last-10-maps) record. A new **L10** column is added to the table.

This is a visual/UI change only. It affects two partials that render the same
row markup and one shared stylesheet.

## What is removed

From both standings partials:

- The gold **trophy** icon on the rank-1 row (`{% partial 'site/icon' name='trophy' %}` inside `<span class="gold-ic">`).
- The **gold leader** styling: the `.first` class application (leader-only gold team name).
- The **team shield / logo** on each row (the "activity blue boxes" — in the mockup these were placeholder squares standing in for `{% partial 'team/shield' %}`).

Dead CSS after removal (to delete from `components.css`):

- `.tr.first .tteam { color: var(--gold); }`
- `.gold-ic { color: var(--gold); width: 14px; height: 14px; }`
- The `.gold-ic` shim block (`display:inline-flex` + svg sizing).

The `trophy` case in `partials/site/icon.htm` may stay (harmless; no longer
referenced by these partials).

> ⚠️ **Confirm before implementing:** removing the shield removes the team
> *logos* from the standings rows, not just a decorative box. The user asked to
> "remove the activity blue boxes" while viewing the mockup, where those boxes
> were logo placeholders. Flagged for explicit confirmation.

## New column: L10

A right-aligned **L10** column showing the last-10-maps record as a coloured
**W–L record** (e.g. green `8` – red `2`), monospace.

- Header cell `L10` added after `Map ±`.
- Grid template gains a 7th track (~92px). Existing tracks tightened slightly to fit.

### Data (placeholder for now)

Real last-10-maps history is **not** currently exposed by
`Division::getDivisionTableStandings()` (which yields `pivot.match_count`,
`match_wins`, `map_wins`, `map_score`). Per decision, L10 is a **random
placeholder** until a real source exists.

To avoid the record (and therefore the effect) flickering between page loads,
the placeholder is **deterministic per team**, derived in Twig from the team id:

```twig
{% set l10w = (team.id * 37 + 11) % 11 %}   {# 0..10 wins #}
{% set l10l = 10 - l10w %}
```

Marked clearly in code as a placeholder to be replaced by a real L10 query.

## The effect

Applied to a row via an `eff` class plus a stage class (`s1`/`s2`/`s3`). Three
absolutely-positioned layers are added as the first children of the row:

1. `<span class="edge">` — the solid electric border (with a `::before` conic
   sheen highlight travelling slowly around it).
2. `<span class="flash">` — a radial white-blue flash, fired with each strike.
3. `<svg class="bolts">` — three lightning-bolt `<path>`s (clean 2-kink shape),
   clipped to the row (`overflow:hidden` + `border-radius`) so they never spill
   into neighbouring rows.

Row text (`.rank`, `.tteam`, `.num`, `.l10`) is given `position:relative;
z-index:4` so it always sits above the effect layers.

### Stages (driven by L10 wins, kept tame)

| Stage | L10 wins | Bolts | Border | Flash peak |
|-------|----------|-------|--------|-----------|
| —     | 0–5      | 0     | none (plain row) | — |
| `s1`  | 6–7      | 1     | faint electric border, small glow | ~0.28 |
| `s2`  | 8–9      | 2     | medium border + glow | ~0.42 |
| `s3`  | 10       | 3     | bright border + glow + subtle bg tint | ~0.50 |

Bolt count per stage is handled purely in CSS (`.s1 .bolts path:nth-child(n+2){display:none}`,
`.s2 .bolts path:nth-child(n+3){display:none}`); the markup always contains all
three paths. Even `s3` stays restrained — thin 1.8px bolts, gentle flash.

Stage class computed inline:

```twig
{% set stage = l10w >= 10 ? 's3' : (l10w >= 8 ? 's2' : (l10w >= 6 ? 's1' : '')) %}
```

### Timing / motion

- Lightning strikes on a **45s** cycle, **first strike ~10s** after load
  (`animation: strike 45s ... var(--d) infinite`, `--d` ≥ 10s).
- Each strike is **visible ~1s** (draw → flicker → fade), then gone for the rest
  of the cycle.
- Rows are **staggered** so the table never flashes all at once:
  `--d: 10s + loop.index0 * 2.5s` (computed per row).
- The `edge::before` sheen travels around the border over ~8s (uses `@property --a` for a smooth angle animation).

### Reduced motion

Per theme convention, add to the `@media (prefers-reduced-motion: reduce)` block
at the **end** of `components.css`:

- `.tr.eff .bolts path { animation: none; opacity: 0; }` (no strikes)
- `.tr.eff .flash { animation: none; opacity: 0; }`
- `.tr.eff .edge::before { animation: none; }` (no travelling sheen)

The **static electric border stays** — it's identity, not motion. Reduced-motion
users get the coloured border + L10 column, just no animation.

### Graceful degradation

- `@property --a` (Chromium/Safari 16.4+): where unsupported, the sheen simply
  stays static — no breakage.
- `mask-composite` uses the `-webkit-` prefix alongside the standard property.

## Components / structure

- **`partials/division/_electric.htm`** *(new, small, shared)* — emits the
  effect layers (`.edge`, `.flash`, `.bolts` svg) so the markup is not duplicated
  across the two consuming partials. Included only when `stage` is non-empty.
- **`partials/division/standings.htm`** — full table. Adds the L10 column,
  computes `l10w/l10l/stage/--d` per team, drops trophy/`.first`/shield, includes
  `_electric.htm` on qualifying rows.
- **`partials/DivisionTable/default.htm`** — the shared override used by the
  homepage top-5 widget *and* the dashboard window. Same changes. (This partial
  serves two consumers; the change is identical for both.)
- **`assets/css/components.css`** — new `.tr.eff` / stage / keyframes block near
  the existing standings-table styles; L10 column styles; grid-template update;
  removal of the dead gold/trophy rules; reduced-motion additions at the end.
  **Note:** the `.tr` grid is redefined in the ≤720px (and ≤480px) responsive
  overrides — the new 7th (L10) track must be added to those column lists too, or
  the column count mismatches on mobile.

### Data flow

Twig iterates the standings collection (unchanged) → per team, compute
placeholder `l10w` from `team.id` → derive `stage` and `--d` → render row; if
`stage` non-empty, add `eff {{ stage }}` classes, set `--d`, and include the
effect partial. No backend/model/query changes.

## Interactions / edge cases

- **`.you` + `.eff`**: a selected team (dashboard) that also qualifies gets both
  the `.you` left-border highlight and the electric border. Acceptable; note in
  implementation, adjust only if it looks wrong.
- **Disbanded (`.inact`) rows**: keep the dimmed treatment; do not apply the
  effect (a disbanded team shouldn't glow). Guard: only apply `eff` when not
  `team.disbanded`.
- **Bolt containment**: `overflow:hidden` on `.bolts` guarantees no overflow into
  adjacent rows; bolt paths are drawn within the row's height (y ≈ 2..52).

## Non-goals / YAGNI

- No real L10 data wiring (placeholder only; separate future task).
- No flame effect (explicitly dropped).
- No effect on non-standings surfaces.
- `showScore`/playoffs mode remains unported (as today).

## Testing / verification

- Visual check in browser at desktop and ≤720px (grid tightens) — border,
  L10 column, and a strike (~10s) render correctly; bolts stay inside rows.
- Confirm reduced-motion: border + column present, no strikes/sheen.
- Confirm both consumers (full standings page, homepage top-5 widget) render the
  effect identically.
- Confirm plain rows (L10 < 6) are visually unchanged from today (minus trophy/shield).
