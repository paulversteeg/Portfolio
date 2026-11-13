<?php
/**
 * Shared <head> + opening chrome for the public site.
 * Expects $pageTitle (string) to be set before include.
 */
$cfg = config();
$siteTitle = $cfg['site']['title'] ?? 'Waxine';
$pageTitle = $pageTitle ?? $siteTitle;
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($pageTitle) ?></title>
<link href="/assets/screen.css?v=<?= @filemtime(__DIR__ . '/../assets/screen.css') ?>" rel="stylesheet" type="text/css" media="screen">
</head>
<body>
<?= staging_banner() ?>
<div id="wrapper">
<nav class="nav" aria-label="Primary">
  <ul id="menu">
    <?php
    // The menu follows Pages > order. Thoughts and Work are entries under
    // Pages too (Work is the home page, Thoughts lists the posts), so the
    // menu reads: Work, Talks, Publications, Thoughts, Resume, About.
    // Until there is a Thoughts entry, Thoughts stays first, as the home page.
    $navHasThoughts = in_array('thoughts', array_column(nav_pages(), 'slug'), true);

    // The item for the page you are on gets aria-current and a line under it.
    // Sub-pages count for their section: a post for Thoughts, a case for Work.
    $navPath = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/') ?: '/';
    $navActive = function (string $href) use ($navPath, $navHasThoughts): bool {
        if ($href === '/') {
            if ($navPath === '/') return true;
            return $navHasThoughts ? str_starts_with($navPath, '/work/')
                                   : str_starts_with($navPath, '/thoughts');
        }
        return $navPath === $href || str_starts_with($navPath, $href . '/');
    };
    $navAttrs = fn (string $href) => $navActive($href) ? ' class="nav-item is-active"' : ' class="nav-item"';
    $navCurrent = fn (string $href) => $navActive($href) ? ' aria-current="page"' : '';
    ?>
    <?php if (!$navHasThoughts): ?>
    <li<?= $navAttrs('/') ?>>
      <a href="/"<?= $navCurrent('/') ?>>
        <strong>Thoughts</strong>
        <span class="subtitle">on my mind</span>
      </a>
    </li>
    <?php endif; ?>
    <?php foreach (nav_pages() as $pg): ?>
    <li<?= $navAttrs(page_url($pg)) ?>>
      <a href="<?= esc(page_url($pg)) ?>"<?= $navCurrent(page_url($pg)) ?>>
        <strong><?= esc($pg['title']) ?></strong>
        <?php if ($pg['subtitle'] !== ''): ?><span class="subtitle"><?= esc($pg['subtitle']) ?></span><?php endif; ?>
      </a>
    </li>
    <?php endforeach; ?>
  </ul>
</nav>
<main>
