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

echo "waxine-cms deployed to $DEST $(date)"
