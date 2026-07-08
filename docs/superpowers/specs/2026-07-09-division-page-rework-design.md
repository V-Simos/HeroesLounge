# Division page rework — full-width rounds, mini-card recent results, photo timeline

**Date:** 2026-07-09
**Theme:** `themes/heroeslounge-next`
**Status:** Design — pending review

## Goal

Three focused improvements to the division page (`pages/season/division.htm`),
the richest read-only viewing surface of the theme:

1. **Rounds** — show every match of a round as a **full-width row** (one match
   per line) instead of the current 2–3-per-row grid. Round tabs stay.
2. **Recent results** — rework the sidebar's text-only list into **mini match
   cards** that reuse the shared `match/card` partial, so recent results and
   rounds look consistent.
3. **Timeline** — give timeline items **bigger round photos** (like the old
   design) instead of the small hexagon-clipped icons.

This is a visual/UI change only. It touches theme markup + `pages.css`. **No
plugin/PHP changes.** The two-column layout (main column + 360px sidebar),
standings, header, upcoming matches, and the spoiler toggle are untouched.

## 1. Rounds — full-width match rows

**File:** `partials/division/rounds.htm` (no markup change),
`assets/css/pages.css` (one rule).

The round tabs, panel `[hidden]` toggling (lounge.js contract), latest-round
default, per-round `onRender()` + eager-load, and the `result`/`fixture` variant
selection all stay **exactly as-is**. The only change is the panel's grid:

```css
/* before */
.round-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }
/* after */
.round-grid { display: grid; grid-template-columns: 1fr; gap: 16px; }
```

`.round-grid[hidden] { display: none; }` and `.round-empty { grid-column: 1 / -1; … }`
stay (the empty-state span rule is harmless with a single track). Each
`match/card` now stretches to the full main-column width, one per row.

No change to the `match/card` partial itself — it already fills its container.

## 2. Recent results — mini match cards

**File:** `partials/division/recent.htm`.

Replace the hand-rolled `.mrow` text rows with the shared **`match/card`**
partial — the same component the rounds render — so the two surfaces match.

- Keep the existing `onRender` pattern: `RecentResults.setProperty('id', div.id)`
  then `RecentResults.onRender()`.
- **Add eager-loading** the text list didn't need but the card does: after
  `onRender()`, guard-and-load the shields + VOD channels, mirroring
  `rounds.htm`:

  ```twig
  {% if RecentResults.matches %}{% do RecentResults.matches.load('teams.smallLogo', 'channels') %}{% endif %}
  ```

- Render each match with:

  ```twig
  {% partial 'match/card' match=match variant='result' revealScore=false withDivision=false %}
  ```

  - `variant='result'` — recent results are played matches (`is_played=1`).
  - `revealScore=false` — score stays behind the page spoiler blur
    (`body.reveal-spoilers`), preserving today's behaviour.
  - `withDivision=false` — every match here is this division; drop the redundant
    "DIV n" tag (also avoids a division lazy-load), same as rounds.
  - `withDate` / `withVod` keep their defaults (`true`) — the card shows the
    date + VOD line on top.
- Keep the panel wrapper (`<section class="p chamfer divside-panel">` +
  `<div class="p-head"><span>Recent results</span></div>`) and the empty state
  (a muted "No results yet." row) so the panel still reads as a sidebar panel.

The cards are constrained by the 360px sidebar column; `match/card` is the same
compact card used elsewhere and reflows within that width. If the cards feel
cramped at 360px, a scoped `.divside-panel .match { … }` tweak in `pages.css` is
the escape hatch — not expected to be needed, noted for the implementer.

## 3. Timeline — bigger round photos

**File:** `assets/css/pages.css` (the `.tl-ic` rules);
`partials/division/timeline.htm` (the `resize()` calls only).

Keep all timeline markup and per-type sentences. Change only the icon slot's
shape and size:

```css
/* before */
.tl-row { … grid-template-columns: 40px 1fr; … }
.tl-ic { width: 40px; height: 40px; … overflow: hidden;
  clip-path: polygon(50% 0, 100% 25%, 100% 75%, 50% 100%, 0 75%, 0 25%); }
.tl-ic svg { width: 18px; height: 18px; }
/* after */
.tl-row { … grid-template-columns: 52px 1fr; … }
.tl-ic { width: 52px; height: 52px; … overflow: hidden;
  border-radius: 50%; }               /* round, not hexagon */
.tl-ic svg { width: 22px; height: 22px; }
```

- The `.tl-row` first grid track widens 40px → 52px to match.
- The `.tl-ic` background/colour fallback tint and centring stay; the lucide
  fallback icon (`users` / `swords` / `message-square`) stays and is now round.
- In `timeline.htm`, bump the four `resize(48,48)` calls to a size that fills the
  larger circle crisply (e.g. `resize(64,64)`, matching the old design's source
  size). Purely the resize dimensions — no other markup change.

## What is NOT changing

- Round tabs, standings, header, upcoming matches, spoiler toggle + cookie flow.
- The `match/card` partial (byte-identical — reused, not edited).
- The two-column `.divwrap` grid and its 980px single-column breakpoint.
- Sidebar panel order: recent → upcoming → timeline.
- Any plugin / PHP / component code.

## Testing

Manual visual verification via the running app (Docker theme render):

1. A division mid-season (`current_round > 0`):
   - Each round tab shows its matches as full-width rows, one per line; tabs
     still switch panels; latest round active by default.
   - Recent results panel shows mini match cards with logos, date, and VOD
     where present; scores blurred until the spoiler toggle is on.
   - Timeline rows show larger round photos; rows without a photo show the round
     fallback icon.
2. Spoiler toggle still masks/reveals recent-result and round scores.
3. Narrow viewport (<980px): sidebar drops below the main column; rounds and
   recent cards remain full-width and legible.
4. Empty states: a division with no recent results / no timeline activity still
   renders the muted placeholder rows.
