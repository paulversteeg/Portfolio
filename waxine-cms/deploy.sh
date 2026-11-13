#!/bin/sh
# Plesk Git post-deployment step for waxine.nl.
#
#   sh waxine-cms/deploy.sh                    live:    ~/waxine-cms
#   sh waxine-cms/deploy.sh ../staging-cms     staging: ~/staging-cms
#
# Plesk pulls the Portfolio repository into a folder outside the website
# (~/portfolio for live, ~/portfolio-staging for staging) and runs this
# script from there. It copies only the CMS code into the site folder.
# Files that are not in the repository (config.php, uploaded images,
# downloads) are never touched.
#
# On the server the admin area lives at <site>/public/admin (so it is
# reachable at /admin); in the repository it sits at waxine-cms/admin.
set -e

SRC="waxine-cms"
DEST="${1:-../waxine-cms}"
LIVE="../waxine-cms"

for dir in lib bin db migrate; do
    mkdir -p "$DEST/$dir"
    cp -R "$SRC/$dir/." "$DEST/$dir/"
done
for file in README.md MIGRATION-AND-CLEANUP-PLAN.md config.sample.php; do
    cp "$SRC/$file" "$DEST/$file"
done

# Website files; public/images and public/downloads are not in the repo.
mkdir -p "$DEST/public"
cp -R "$SRC/public/." "$DEST/public/"

# Admin area
mkdir -p "$DEST/public/admin"
cp -R "$SRC/admin/." "$DEST/public/admin/"

if [ "$DEST" != "$LIVE" ]; then
    # Staging: its own config (no secrets, see config.staging.php) ...
    cp "$SRC/config.staging.php" "$DEST/config.php"
    # ... the live site's images and downloads, shared rather than copied ...
    for dir in images downloads; do
        if [ ! -e "$DEST/public/$dir" ]; then
            ln -s "../../waxine-cms/public/$dir" "$DEST/public/$dir"
        fi
    done
    # ... and any site assets that only exist on the server (the profile photo).
    for f in "$LIVE"/public/assets/*; do
        [ -e "$DEST/public/assets/$(basename "$f")" ] || cp "$f" "$DEST/public/assets/"
    done
fi

# One-off content steps, on live and on staging (staging also gets the
# previews that still need approval): move the hand-written talks into talk
# records, and the resume updates. Both are safe to run again and do nothing
# once done. A failure here never blocks the deploy.
if command -v php >/dev/null 2>&1; then
    php "$DEST/bin/import_talks.php" || echo "import_talks.php failed; content unchanged"
    php "$DEST/bin/import_publications.php" || echo "import_publications.php failed; content unchanged"
    php "$DEST/bin/apply_resume_updates.php" || echo "apply_resume_updates.php failed; content unchanged"
    php "$DEST/bin/apply_about_updates.php" || echo "apply_about_updates.php failed; content unchanged"
    php "$DEST/bin/apply_work_updates.php" || echo "apply_work_updates.php failed; content unchanged"
    php "$DEST/bin/apply_post_updates.php" || echo "apply_post_updates.php failed; content unchanged"
else
    echo "php not found; run bin/import_talks.php and bin/apply_resume_updates.php by hand"
fi

echo "waxine-cms deployed to $DEST $(date)"
