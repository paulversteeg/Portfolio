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
