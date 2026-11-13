---
status: draft            # draft | active | outdated
owner:                   # who keeps this file current
last-reviewed:           # YYYY-MM-DD
---

# Design system tokens

Colour, typography, spacing, radius, elevation. Link to the token source (Figma variables or code) instead of copying everything.

## Grid (DD-002)

The site sits on a faint squared grid, like a page in a notebook, and every element rests on its lines.

- **Square:** `--u` = 0.9rem (18px at default settings). A line of running text is 2 squares (`line-height: calc(2 * var(--u))`).
- **Lines:** the text colour at 2.25% (`--grid-line`), on the page wrapper. The grid is centred on the page.
- **Vertical rhythm:** line heights, margins, paddings and block heights are whole squares. Headings: h1 3 squares, h2 and h3 2. Paragraphs: 1 square above, 3 below. Menu: 5 squares high.
- **Columns:** the page column is an odd number of squares wide (at most 47, 846px), so both its edges fall on a line. Cards are 13 squares (8 on phones) with 1 square between them.
- **Lines that would add a pixel** (dividers, outlines, underlines on block links) are drawn as an inset shadow, which takes no room.
- **Photos and embeds** with an unknown height: `assets/grid.js` rounds the block that holds them up to a whole square.
- **Checking:** add `?grid=check` to any URL to show the lines in red.
- **Code:** the "Notebook grid" section at the end of `waxine-cms/public/assets/screen.css`.

