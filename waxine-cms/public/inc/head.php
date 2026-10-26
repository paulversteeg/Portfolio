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
<div id="wrapper">
<nav class="nav" aria-label="Primary">
  <ul id="menu">
    <?php
    // "Thoughts" (the home page) is not a CMS page. It sits right after the
    // first page in the CMS menu order (About), so the menu reads:
    // About, Thoughts, Talks, Publications.
    $thoughts_shown = false;
    foreach (nav_pages() as $i => $pg):
      if ($i === 1): $thoughts_shown = true; ?>
    <li class="nav-item">
      <a href="/">
        <strong>Thoughts</strong>
        <span class="subtitle"><?= esc($cfg['site']['tagline'] ?? '') ?></span>
      </a>
    </li>
    <?php endif; ?>
    <li class="nav-item">
      <a href="<?= esc(page_url($pg)) ?>">
        <strong><?= esc($pg['title']) ?></strong>
        <?php if ($pg['subtitle'] !== ''): ?><span class="subtitle"><?= esc($pg['subtitle']) ?></span><?php endif; ?>
      </a>
    </li>
    <?php endforeach; ?>
    <?php if (!$thoughts_shown): // fewer than two CMS pages in the menu ?>
    <li class="nav-item">
      <a href="/">
        <strong>Thoughts</strong>
        <span class="subtitle"><?= esc($cfg['site']['tagline'] ?? '') ?></span>
      </a>
    </li>
    <?php endif; ?>
  </ul>
</nav>
<main>
