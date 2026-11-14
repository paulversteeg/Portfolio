---
status: active
owner: Paul Versteeg
last-reviewed: 2026-11-14
---

# Spec: Webartrose

## Code

| Part | File |
|---|---|
| Storage, limits, reading | `waxine-cms/lib/wear.php` (tables `wear` and `wear_trail`, created on first use) |
| Endpoint | `waxine-cms/public/wear.php` (POST, JSON) |
| Counting and drawing, control | `waxine-cms/public/assets/wear.js` |
| Effects | "Webartrose" section at the end of `waxine-cms/public/assets/screen.css` |
| Story age | `data-published` on the cover in `public/post.php` |

## What is counted

| Count | When | Per page view |
|---|---|---|
| touched | pointer rests 250 ms on a link, card, photo or button | once per element |
| pressed | click | at most 3 |
| seen | at least 60% in view: 1 s for photos and cards, 2.5 s for text | once per element |
| trail | pointer passes a grid square (counted from the page's centre line and top) | at most 5 per square, 600 squares |

Elements share wear when they show the same thing: the key is the kind plus the link target or image path (`a /talks`, `img /images/x.jpg`, `card /thoughts/slug`); text blocks and buttons use the page plus a hash of their text (`t /about#1k2j3`). Counts are sent with `sendBeacon` when the page is left, and read in one request when it opens.

## From count to wear

Wear per counter is `log10(1 + n × f) / 4`, capped at 1: ten uses show, ten thousand is worn through. `f` is the time slider: 0 (as new) to 1 (now), and to the right `1 + yearsAhead / yearsCountedSoFar`, up to 30 years ahead. A story's age adds sun to its photos and text (30 years is the maximum).

## Limits against misuse

Per request: 200 elements, 3 per counter, 600 squares, 5 per square, keys of at most 190 plain characters in five known kinds, page paths of at most 160 characters, a body of at most 64 KB. Requests from another origin are refused. Someone determined could still inflate counts; for a layer like this that is acceptable.

## Staging

Staging gets a copy of the live database every night, so wear counted on staging does not last.
