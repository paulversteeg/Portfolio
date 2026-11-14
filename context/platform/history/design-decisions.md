---
status: draft            # draft | active | outdated
owner:                   # who keeps this file current
last-reviewed:           # YYYY-MM-DD
---

# Platform design decisions

## DD-001: Work is the home page; menu runs from what I do to who I am
- **Date:** 2026-11-12
- **Context:** Visitors (recruiters, clients) landed on the Thoughts list, whose last post is from 2024, and had to dig for projects; the resume was only linked from About.
- **Decision:** A Work page is the home page and first menu item. Menu: Work · Talks · Publications · Thoughts · Resume · About: what I do, what I think, what I have done, who I am. Work cards are Experience records with "Show on the Work page" ticked, each with its own case page (`/work/<slug>`). Thoughts moves to `/thoughts`; post URLs stay the same.
- **Alternatives considered:** Work first with Thoughts third (Work · Resume · Thoughts …); keeping Thoughts as the home page.
- **Consequences:** One set of Experience records feeds the Resume, the Work page and the case pages.

## DD-002: A notebook grid under the site; everything rests on its lines
- **Date:** 2026-11-13
- **Context:** The portfolio mock-up explored a faint squared background with every element aligned to it. Of that exploration, only the grid and the alignment carry over to the site; colours, type and layout stay as they are.
- **Decision:** A grid of 0.9rem squares (18px), lines at 2.25% of the text colour, centred on the page. Text lines are 2 squares; all vertical spacing and block heights are whole squares; the page column is an odd number of squares wide so both edges fall on a line. Details in `design-system/DESIGN.md` (Grid).
- **Alternatives considered:** A 24px grid with 24px text lines (as in the mock-up), which would have changed the site's type sizes; a grid drawn only as decoration, without aligning the elements.
- **Consequences:** New styles use multiples of `--u` for line heights and vertical spacing, and inset shadows instead of borders that add height. Menu items in their own columns only line up with the grid at full width. Live since 2026-11-13.

## DD-003: Webartrose: the site shows the signs of its use
- **Date:** 2026-11-14
- **Context:** Paul's graduation concept (Interaction Design): everything around us wears with use, and that gives it character. Applied to the site as a layer, it is also the "interesting gimmick to discover" the brief asks for.
- **Decision:** Anonymous counts of how all visitors touch, press and see each element, and where the pointer walks, drawn as wear on the element itself. On by default at the real level; a control to switch it off and a time slider (as new, now, up to 30 years ahead). Details in `features/webartrose/`.
- **Alternatives considered:** wear per visitor, stored in their own browser; simulated wear without counting; off by default; letting things break at the far end of the slider.
- **Consequences:** Wear never shifts the layout or makes the site unusable, and stores nothing about visitors. New interactive elements get a wear key in `wear.js` when they should wear.
- **Extended 2026-11-14:** after a series of photos of things worn by time (Paul's inspiration), ten more signs: dead clicks and near misses, how far down people get, the slider worn where it is left, a tan line behind the round button, the active menu item worn through to the grid, staples per ten visits, graphite on selected text, routes between sections in the menu, dog-ears from shared links, the maker's own edits, and a waist on much-pressed buttons. A dead-click mark only shows once several people made it (a worn keypad gives away the code).

