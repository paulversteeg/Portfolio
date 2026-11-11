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
    // "Thoughts" (the home page) is not a CMS page; it always comes first.
    // The CMS pages follow in their menu order (Pages > order), which puts
    // About last: Thoughts, Talks, Publications, About.
    ?>
    <li class="nav-item">
      <a href="/">
        <strong>Thoughts</strong>
        <span class="subtitle"><?= esc($cfg['site']['tagline'] ?? '') ?></span>
      </a>
    </li>
    <?php foreach (nav_pages() as $pg): ?>
    <li class="nav-item">
      <a href="<?= esc(page_url($pg)) ?>">
        <strong><?= esc($pg['title']) ?></strong>
        <?php if ($pg['subtitle'] !== ''): ?><span class="subtitle"><?= esc($pg['subtitle']) ?></span><?php endif; ?>
      </a>
    </li>
    <?php endforeach; ?>
  </ul>
</nav>
<main>
