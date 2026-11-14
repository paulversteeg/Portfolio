---
status: active
owner: Paul Versteeg
last-reviewed: 2026-11-14
---

# Feature definition: Webartrose

## What is it?

A layer over the whole site that shows signs of use, like things in the real world wear with use. Links get greasy and rubbed thin, menu items hang crooked and loose, cards get rounded corners and fingerprints, photos fade in the sun, ink fades, buttons get pressed in, and paths appear on the grid where the pointer walks. A much-pressed menu item flickers like old neon. Something nobody touched for months flickers once when it comes back on.

It is analytics shown on the thing itself instead of in a dashboard. The use of all visitors adds up, so the site ages for real over the years. It goes back to Paul's graduation concept at Interaction Design, with the same name.

## Who uses it, and in which situation?

Every visitor sees it: it is on by default, at the real level of use. Designers who notice it can open the control (the round button at the bottom right) to switch it off or move the time slider: as new, now, or up to 30 years ahead if the site keeps being used at the same rate. The setting is remembered in the visitor's own browser.

## What does it explicitly not do?

- It never makes the site unusable: text stays readable (never below the site's own lowest contrast) and everything stays clickable. Nothing breaks or disappears, also at 30 years.
- It never shifts the layout: wear is drawn with rotate, translate, colour and shadow only, so the grid (DD-002) stays intact.
- It stores nothing about the visitor: no cookies, no IP addresses, no ids. Only counts per element and per grid square. Browsers that send Do Not Track or Global Privacy Control are not counted.
- It does not count crawlers and tools.
