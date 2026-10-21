---
status: draft            # draft | active | outdated
owner:                   # who keeps this file current
last-reviewed:           # YYYY-MM-DD
---

# Tech constraints

## Platform and stack

- **Language and database:** PHP 8.0+ with the `pdo_mysql` extension, MySQL.
- **Server:** Plesk hosting for waxine.nl.
- **CMS:** `waxine-cms`, self-built by Paul and live on waxine.nl. Content lives in the database; posts and pages are edited in `/admin`. Code: https://github.com/paulversteeg/Waxine (see `waxine-cms/README.md`).
- **Fonts:** Brando Sans and Univers Condensed, from `_static/fonts`.

## Hard constraints (standards, regulation, legacy)

- **Keep the CMS.** It replaced the fragile flat-file setup and is a real improvement. Changes build on it, not around it.
- **Keep the URL structure** of Thoughts (`/thoughts/<slug>`), including the redirects for old links (`/thoughts/YYYYMMDD_title.php`, `/waxine/...`).
- **Keep the title-derived colour per story.** Each story gets a unique colour calculated from its title. It may be strengthened, not dropped.
- **No secrets in git or the web root:** `config.php`, database credentials and SSH keys stay out of the repo and outside any web-served folder.

## What the platform can and cannot do

Can:

- Write, edit and delete posts and static pages (about, talks, publications) through `/admin`, including image uploads.
- Import old `thoughts/` story files, keeping title, date, slug and colour.

Cannot (yet): not provided.
