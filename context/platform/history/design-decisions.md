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
- **Consequences:** New styles use multiples of `--u` for line heights and vertical spacing, and inset shadows instead of borders that add height. Menu items in their own columns only line up with the grid at full width. On staging first.

