# Waxine — migration & cleanup plan

Your connected folder is the server's `httpdocs`. The **live web root is
`public_html/`** (because `www.waxine.nl/waxine/` maps to
`public_html/waxine/`). The live site is `public_html/waxine/`, with 67
stories in `public_html/waxine/thoughts/`. Almost everything else is dead.

> ⚠️ This folder writes directly to your live server. **Take a full backup
> before moving anything** (download a zip of `httpdocs`, or snapshot in your
> hosting panel). Nothing below deletes — the plan is archive, not delete.

---

## Step 0 — Back up
Download/zip the whole `httpdocs` folder, or take a hosting-panel snapshot.
Keep it somewhere off the server.

## Step 1 — Fix the two security exposures (do this regardless)
Move these out of any web-served folder (into a private folder above the
web root, e.g. the account root, or delete after backup):

- `httpdocs/waxine_key` and `httpdocs/waxine_key.pub` — an **SSH private key**
  in the web tree. Move it out and, to be safe, rotate the key
  (generate a new pair, replace it wherever it's used).
- `public_html/mysqli_connect.php` — contains **database credentials**.
  Move above the web root and include it by absolute path, or at minimum
  block it in `.htaccess`.

## Step 2 — Migrate the 67 stories into the CMS
The content is now available at `public_html/waxine/thoughts/`.

    cd waxine-cms
    php migrate/import.php --source=../public_html/waxine/thoughts          # dry run
    # review migrate/review/*.html, then:
    php migrate/import.php --source=../public_html/waxine/thoughts \
                           --images=../public_html/waxine/thoughts/images --commit

(See README.md for DB setup and admin-user creation first.)

## Target architecture — main site + one subdomain per side project

The goal: **Waxine is the site everyone lands on at `waxine.nl`**, and every
side project (Dordtologie, and the many to come) is an independent site on
its **own subdomain with its own isolated web root** — never nested inside
the main site's folder. Today they're all jammed into `public_html/`, which
is why the structure is confusing and fragile.

This is a **Plesk** server. Everything for the domain lives under one
subscription root, `/var/www/vhosts/waxine.nl/`, and each subdomain is a
sibling folder there with its own document root. Recommended layout:

    /var/www/vhosts/waxine.nl/        (subscription root)
    ├── httpdocs/                     → document root for waxine.nl (MAIN SITE)
    │       (contents of waxine-cms/public/ — the CMS front-end)
    ├── waxine-cms/                   → CMS code, above the web root
    │       (lib/, admin/, migrate/, db/, config.php)
    ├── dordtologie/                  → document root for dordtologie.waxine.nl
    ├── jubal/                        → document root for jubal.waxine.nl
    ├── granitafun/                   → document root for granitafun.waxine.nl
    ├── …                             → future projects drop in here
    └── _ARCHIVE_2026/                → dead files (never web-served)

> Note: your domain's document root is currently repointed at the nested
> `httpdocs/public_html`, which is why the live site sits at
> `.../httpdocs/public_html/waxine/`. That nesting is the leftover from an
> earlier cPanel→Plesk move and is what we're straightening out here.

### The repeatable convention (for every future project) — Plesk
1. **Websites & Domains → Add Subdomain.** Enter the name (e.g. `dordtologie`).
2. Set its **Document root** to a clean folder in the subscription, e.g.
   `dordtologie` (Plesk creates `/var/www/vhosts/waxine.nl/dordtologie`).
   Keep it a sibling of `httpdocs`, not inside it, so the project is isolated
   and never also reachable at `waxine.nl/dordtologie`.
3. Put the project's files in that folder and enable SSL under **SSL It!**
   (Let's Encrypt). A single wildcard `*.waxine.nl` cert there covers every
   subdomain at once.

Same three steps every time; each project stays fully isolated — its own
files, logs, PHP version and failures never touch the main site.

## Step 3 — Deploy the CMS as the main site (waxine.nl root)

In Plesk: **Websites & Domains → waxine.nl → Hosting & DNS → Hosting Settings**,
and set **Document root** to the CMS's public folder (e.g. `waxine-cms/public`).
That single change makes the CMS the site served at `waxine.nl` and undoes the
`public_html/waxine` nesting. New URLs:

- `https://waxine.nl/`                → Thoughts listing
- `https://waxine.nl/thoughts/<slug>` → a post
- `https://waxine.nl/about`, `/talks`, `/publications`

Preserve old inbound links (the current live site is at `/waxine/...`) with a
301 in `public/.htaccess`:

    RewriteRule ^waxine/thoughts/([^/]+)\.php$ /thoughts/$1 [R=301,L,NE]
    RewriteRule ^waxine/?$                     /            [R=301,L]

(The CMS already maps `/thoughts/<slug>` and old `.php` filenames to posts.)

---

## Step 4 — Sort the existing folders

Each of these is a standalone site currently living inside `public_html/`.
**Your call which are still wanted** — wanted ones become subdomains
(move to `~/sites/<name>/`), the rest go to `_ARCHIVE_2026/`.

| Folder in public_html | Suggested subdomain | Notes |
|---|---|---|
| `dordtologie`      | dordtologie.waxine.nl | active PHP site |
| `jubal`            | jubal.waxine.nl       | 21 files, PHP |
| `jubaltenor`       | jubaltenor.waxine.nl  | 23 files |
| `Granitafun`       | granitafun.waxine.nl  | 20 files, PHP |
| `esmouter`         | esmouter.waxine.nl    | PHP site |
| `hku`              | hku.waxine.nl         | static site |
| `marchingworld`    | marchingworld.waxine.nl | static |
| `webartrose`       | webartrose.waxine.nl  | needs an index added |
| `weertest`         | weertest.waxine.nl    | PHP |
| `mod-dow`, `inside`, `puurpxl_banner`, `klantomgeving_v3/v4` | your call | smaller/older projects |
| `drumcorps`, `webtv`, `Balans`, `tracosa`, `redirect`, `facebook` | probably archive | thin / stub folders |
| `waxine2005`, `waxine2018`, `old` | **archive** | previous versions of THIS site, not separate projects |

### Straight to `_ARCHIVE_2026/` (clearly dead, no decision needed)
Every backup copy and scratch folder:

    duplicate___index.php, duplicate__static, duplicate_about.php,
    duplicate_blog, duplicate_dev.waxine.nl, duplicate_facebook,
    duplicate_index.php, duplicate_jubal, duplicate_old,
    duplicate_paulversteeg_avatar_big.png, duplicate_paulversteeg_avatar_small.png,
    duplicate_phpinfo.php, duplicate_publications.php, duplicate_talks.php,
    duplicate_thoughts, duplicate_waxine2005,
    _dump, _ziptest, index.html, "index copy.html", mysqli_connect.php

Plus the stray half-copy at the `httpdocs` top level (superseded by the CMS):

    httpdocs/index.php, about.php, talks.php, publications.php, resume.php,
    httpdocs/_static/, httpdocs/thoughts/, httpdocs/noise.php

### Leave alone
- `public_html/dev.waxine.nl/`, `httpdocs/blog.waxine.nl/` — check the panel;
  if these subdomains are in use, keep them (they already follow the model).
- `httpdocs/.well-known/` — SSL/domain validation.

### Delete after backup (your call)
`duplicate_phpinfo.php` (leaks server config); and once the CMS is verified
live, the whole `_ARCHIVE_2026/`.

---

## Suggested order
1. Back up everything.
2. Fix the security files (Step 1).
3. Set up DB + admin user, migrate the 67 posts (dry run → commit).
4. Deploy the CMS to a staging URL; verify all posts render.
5. Point `waxine.nl` docroot at the CMS; add the `/waxine/` redirects.
6. For each side project you're keeping: add the subdomain in Plesk with its
   document root at `/var/www/vhosts/waxine.nl/<name>`, move the files, enable SSL.
7. Archive the dead folders. After a clean week, delete the archive.
