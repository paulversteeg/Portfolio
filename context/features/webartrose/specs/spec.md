---
status: active
owner: Paul Versteeg
last-reviewed: 2026-11-14
---

# Spec: Webartrose

## Code

| Part | File |
|---|---|
| Storage, limits, reading | `waxine-cms/lib/wear.php` (tables `wear`, `wear_trail` and `wear_mark`, created on first use) |
| The maker's edits | `wear_maker()` called on save in `waxine-cms/admin/edit.php` and `admin/edit_page.php` |
| Endpoint | `waxine-cms/public/wear.php` (POST, JSON) |
| Counting and drawing, control | `waxine-cms/public/assets/wear.js` |
| Effects | "Webartrose" section at the end of `waxine-cms/public/assets/screen.css` |
| Story age | `data-published` on the cover in `public/post.php` |
| Explanation page | `/webartrose`, created by `waxine-cms/bin/apply_webartrose_page.php` (not in the menu; linked from the panel). The script replaces the text only while it is unchanged since an earlier version (`EARLIER_BODIES`); once edited in the admin it is left alone |

## What is counted

| Count | When | Per page view |
|---|---|---|
| touched | pointer rests 250 ms on a link, card, photo or button | once per element |
| pressed | click | at most 3 |
| seen | at least 60% in view: 1 s for photos and cards, 2.5 s for text | once per element |
| trail | pointer passes a grid square (counted from the page's centre line and top) | at most 5 per square, 600 squares |
| touched by keyboard | Tab to a link or button (`:focus-visible`) | as touched |
| graphite (`t` press) | text selected, at least 4 characters | at most 3 |
| page view (`h <path>`, and `h <section>`) | page opened | 1 |
| from outside (`e <path>`) | opened with no referrer or one from another site (not the home page) | 1 |
| route (`r <from>><to>`) | click on a link to another menu section | at most 3 |
| slider (`s 0..20`) | the time slider is let go, in steps of 5 | at most 3 |
| dead click (`wear_mark` c) | click on something not interactive, by grid square | at most 3 per square, 60 squares |
| how far (`wear_mark` d) | the lowest grid row seen | once |
| maker (`m <path>`) | a save in the admin; never accepted from the public endpoint | 1 |

Elements share wear when they show the same thing: the key is the kind plus the link target or image path (`a /talks`, `img /images/x.jpg`, `card /thoughts/slug`); text blocks and buttons use the page plus a hash of their text (`t /about#1k2j3`). Counts are sent with `sendBeacon` when the page is left, and read in one request when it opens.

## From count to wear

Wear per counter is `log10(1 + n × f) / 4`, capped at 1: ten uses show, ten thousand is worn through. `f` is the time slider: 0 (as new) to 1 (now), and to the right `1 + yearsAhead / yearsCountedSoFar`, up to 30 years ahead. A story's age adds sun to its photos and text (30 years is the maximum).

## The signs added on 2026-11-14

| Sign | From | Drawn as |
|---|---|---|
| Tried | dead clicks | scuffs (SVG) on top, only for squares counted 3 or more times by everyone together; your own show at once |
| How far | depth | a worn strip along the left edge, darkest as far as most people got |
| The slider | `s` | lighter spots on the track of the time slider |
| Tan line | page views and site age | the screen yellows (`.wear-sun`, multiply), except a ring behind the round button |
| Worn through | `h <section>`; a card's touched | the active menu item shows the grid paper (`--wh`); a card shows the grid where the thumb goes |
| Staples | `h <path>` / 10 | a strip of staples in the footer (5 squares high) |
| Graphite | `t` press | grey smudge behind the text (`--wg`) |
| Routes | `r` | strokes in the empty bands above and below the menu titles, never across them |
| Dog-ears | `e` | a folded corner on the page header and on cards linking to it (`--we`) |
| The maker | `m` | a worn spot beside the title (`--wm`) |
| A waist | press on buttons | the outline narrows in the middle |

Related work (on the explanation page): Hill, Hollan et al., *Edit wear and read wear* (CHI 1992, doi 10.1145/142750.142751); Matejka, Grossman, Fitzmaurice, *Patina: Dynamic heatmaps for visualizing application usage* (Autodesk Research, CHI 2013). Webartrose started independently, in Paul's graduation; what it adds: shared wear shown to every visitor, as physical wear on the element, with a time slider.

At the far end (30 years, full sun, graphite and a thumb mark on the same spot) text stays above 4.5:1.

## Limits against misuse

Per request: 200 elements, 3 per counter, 600 squares, 5 per square, keys of at most 190 plain characters in known kinds, 60 dead-click squares, 600 keys per read, page paths of at most 160 characters, a body of at most 64 KB. Requests from another origin are refused. Someone determined could still inflate counts; for a layer like this that is acceptable.

## Staging

Staging gets a copy of the live database every night, so wear counted on staging does not last.
