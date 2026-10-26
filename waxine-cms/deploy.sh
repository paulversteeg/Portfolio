#!/bin/sh
# Plesk Git post-deployment step for waxine.nl.
#
# Plesk pulls the Portfolio repository into ~/portfolio (outside the
# website) and then runs this script from that folder. It copies only the
# CMS code into the live site folder ~/waxine-cms. Files that are not in
# the repository (config.php, uploaded images, downloads) are never touched.
#
# On the server the admin area lives at waxine-cms/public/admin (so it is
# reachable at /admin); in the repository it sits at waxine-cms/admin.
set -e

SRC="waxine-cms"
DEST="../waxine-cms"

for dir in lib bin db migrate; do
    mkdir -p "$DEST/$dir"
    cp -R "$SRC/$dir/." "$DEST/$dir/"
done
for file in README.md MIGRATION-AND-CLEANUP-PLAN.md config.sample.php; do
    cp "$SRC/$file" "$DEST/$file"
done

# Website files; public/images and public/downloads are not in the repo.
cp -R "$SRC/public/." "$DEST/public/"

# Admin area
mkdir -p "$DEST/public/admin"
cp -R "$SRC/admin/." "$DEST/public/admin/"

echo "waxine-cms deployed $(date)"
