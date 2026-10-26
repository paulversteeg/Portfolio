<?php
/**
 * Admin bootstrap. Finds the CMS app root (the folder that contains lib/)
 * by walking up from wherever this admin folder lives, so the admin works
 * whether it sits at  waxine-cms/admin  or  waxine-cms/public/admin.
 */
$APP = __DIR__;
while ($APP !== dirname($APP) && !is_file($APP . '/lib/db.php')) {
    $APP = dirname($APP);
}
require_once $APP . '/lib/helpers.php';
require_once $APP . '/lib/auth.php';
