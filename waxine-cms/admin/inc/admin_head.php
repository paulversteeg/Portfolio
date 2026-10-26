<?php
/** Admin chrome. Expects $adminTitle and $me (current user). */
$adminTitle = $adminTitle ?? 'Admin';
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($adminTitle) ?> - Waxine admin</title>
<style>
  :root{ --ink:#25282a; --paper:#f4f1ef; --panel:#fff; --line:#e2ddd7; --accent:#3a6ea5; }
  *{box-sizing:border-box}
  body{margin:0;font:16px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;color:var(--ink);background:var(--paper)}
  header.adminbar{display:flex;align-items:center;justify-content:space-between;padding:.8rem 1.2rem;background:var(--panel);border-bottom:1px solid var(--line)}
  header.adminbar a{color:var(--accent);text-decoration:none;margin-left:1rem}
  header.adminbar .brand{font-weight:700;color:var(--ink);margin:0}
  main.admin{max-width:900px;margin:2rem auto;padding:0 1.2rem}
  .card{background:var(--panel);border:1px solid var(--line);border-radius:10px;padding:1.4rem;margin-bottom:1.2rem}
  table{width:100%;border-collapse:collapse}
  th,td{text-align:left;padding:.6rem .5rem;border-bottom:1px solid var(--line);vertical-align:top}
  th{font-size:.8rem;text-transform:uppercase;letter-spacing:.03em;color:#7a736c}
  label{display:block;font-weight:600;margin:.9rem 0 .3rem}
  input[type=text],input[type=password],input[type=datetime-local],select,textarea{
    width:100%;padding:.6rem .7rem;border:1px solid var(--line);border-radius:8px;font:inherit;background:#fff}
  textarea{min-height:340px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:14px}
  .btn{display:inline-block;background:var(--accent);color:#fff;border:0;border-radius:8px;padding:.6rem 1.1rem;font:inherit;cursor:pointer;text-decoration:none}
  .btn.secondary{background:#eee;color:var(--ink)}
  .btn.danger{background:#b23b3b}
  .form-actions{display:flex;align-items:center;gap:.6rem;margin-top:1.2rem}
  .form-actions-main{display:flex;gap:.6rem;margin-left:auto}
  .row{display:flex;gap:1rem;flex-wrap:wrap}
  .row>div{flex:1;min-width:220px}
  .muted{color:#8a837c;font-size:.85rem}
  .pill{display:inline-block;padding:.1rem .5rem;border-radius:20px;font-size:.75rem;background:#eee}
  .pill.published{background:#d9ecd9}
  .flash{background:#d9ecd9;border:1px solid #b6d6b6;padding:.7rem 1rem;border-radius:8px;margin-bottom:1rem}
  .flash.error{background:#f6dcdc;border-color:#e2b4b4}
  input[type=number]{padding:.5rem .6rem;border:1px solid var(--line);border-radius:8px;font:inherit;background:#fff}
</style>
</head>
<body>
<header class="adminbar">
  <p class="brand">Waxine admin</p>
  <nav>
    <a href="/admin/">Posts</a>
    <a href="/admin/pages.php">Pages</a>
    <a href="/admin/experiences.php">Experience</a>
    <a href="/admin/edit.php">New post</a>
    <a href="/" target="_blank">View site ↗</a>
    <a href="/admin/logout.php">Log out</a>
  </nav>
</header>
<main class="admin">
