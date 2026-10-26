# Waxine CMS

A small, self-hosted CMS (PHP + MySQL) that replaces the old flat-file
"one PHP file per story" setup. It keeps the exact look and URL structure
of the original *Thoughts* blog, but stores content in a database and adds
a simple admin area for writing, editing and deleting posts.

## Why this exists

The old site scanned a `thoughts/` folder of `YYYYMMDD_title.php` files on
every request and rendered them through `_static/functions.php`. Two things
made it fragile:

1. `functions.php` used `$string{$index}` (curly-brace indexing), which was
   **removed in PHP 8.0**. On a host running PHP 8 this is a fatal error and
   produces the `HTTP 500` you were seeing on every page.
2. Content, layout and logic were interleaved in each file, so editing was
   error-prone and there was no admin UI.

This CMS fixes both: PHP 8-safe code, content in a database, everything
authored through `/admin`.

## What's in the box

```
waxine-cms/
├── config.sample.php      → copy to config.php and fill in
├── db/schema.sql          → MySQL tables + seed
├── lib/                   → db, helpers, auth
├── public/                → the web root (point your domain here)
│   ├── index.php          → Thoughts listing
│   ├── post.php           → single post (gradient header, prev/next)
│   ├── page.php           → static pages (about, talks, …)
│   ├── assets/screen.css  → your original stylesheet (copied)
│   ├── images/            → uploaded images
│   └── .htaccess          → pretty URLs + old-link redirects
├── admin/                 → login, dashboard, editor, upload
├── bin/create_user.php    → create the admin login (CLI)
└── migrate/import.php     → import the old thoughts/ files
```

## Setup

1. **Create a database** and load the schema:
   ```
   mysql -u USER -p -e "CREATE DATABASE waxine CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
   mysql -u USER -p waxine < db/schema.sql
   ```

2. **Configure**:
   ```
   cp config.sample.php config.php
   ```
   Edit `config.php` with your DB credentials, `base_url`, and a random
   `app_secret` (`php -r "echo bin2hex(random_bytes(32));"`).

3. **Create your admin login** (run on the server, never in a browser):
   ```
   php bin/create_user.php paul "Paul Versteeg"
   ```
   You'll be prompted for a password; only the bcrypt hash is stored.

4. **Point the web root at `public/`.** On shared hosting, either set the
   document root to `.../waxine-cms/public`, or move the contents of
   `public/` into your web root and keep `lib/`, `admin/`, etc. one level
   above it (outside the web root is ideal for security).

## Migrating the old content

You need the original `thoughts/` folder (the `YYYYMMDD_title.php` story
files) and, if you have them, the old `images/` folder.

1. **Dry run** (default — changes nothing, writes HTML previews):
   ```
   php migrate/import.php --source=/path/to/old/thoughts
   ```
   Open `migrate/review/*.html` and spot-check a few posts.

2. **Commit** once the previews look right:
   ```
   php migrate/import.php --source=/path/to/old/thoughts --images=/path/to/old/images --commit
   ```
   The import is **idempotent**: re-running updates existing posts (matched
   on their original filename) instead of creating duplicates.

The importer preserves each post's title, publish date, URL slug, and the
title-derived gradient colour, and converts the old `image()`, `dropcap()`
and `embedVideo()` helper calls into static HTML. Any file containing custom
PHP it can't translate is flagged in the output for a manual look.

## URLs & backwards compatibility

* New post URL: `/thoughts/come_work_in_ATL`
* Old inbound links `/thoughts/20180101_come_work_in_ATL.php` are redirected
  to the right post automatically (via `legacy_filename` + `.htaccess`).
* Static pages: `/about`, `/talks`, etc. (rows in the `pages` table).

## Notes

* The original site referenced a `_static/fonts/` folder that wasn't in the
  backup. If you have the Brando Sans font files, drop them in
  `public/assets/fonts/` to match the original typography exactly; otherwise
  the CSS falls back to system fonts.
* `config.php` and the `db/*.sql` files are blocked from being web-served by
  `.htaccess`, but keeping `lib/`, `db/` and `config.php` above the web root
  is the safest option.
* Requires PHP 8.0+ and the `pdo_mysql` extension.
```

## Deploying

This folder lives in the Portfolio repository. Every push to `main` is
picked up by Plesk (Git, repository `portfolio.git` on waxine.nl), which
pulls the repository into `~/portfolio` on the server and runs
`waxine-cms/deploy.sh`. That script copies the CMS code into the live
folder `~/waxine-cms`; the admin area goes to `public/admin`.

`config.php`, uploaded images and downloads are not in the repository,
so a deploy never touches them.

## Staging

`staging.waxine.nl` runs the `staging` branch of the Portfolio repository
(Plesk Git repository `portfolio-staging.git`, deployed to `~/staging-cms`
with `sh waxine-cms/deploy.sh ../staging-cms`). Only logged-in CMS users can
see it, and every page shows a staging bar.

- Its database `ywefbduf_waxine_staging` is a copy of live, refreshed every night by
  `bin/refresh_staging.php` (Plesk scheduled task). Edit content on live;
  content changed on staging is overwritten.
- Changes to the database structure travel with the code (tables are
  created by the code itself), so both databases follow the code.
- Images and downloads are shared with live.

Workflow: push to `staging`, check on staging.waxine.nl, then merge
`staging` into `main` to go live.
