<?php

use PrintBridge\Services\AdminAuth;
use PrintBridge\Support\Text;
use PrintBridge\Support\View;

$title = $title ?? Text::get('app.name');
$isLoggedIn = AdminAuth::userId() !== null;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= View::e($title) ?></title>
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
<header class="topbar">
    <button type="button" class="brand" onclick="document.getElementById('about-modal').showModal()" aria-haspopup="dialog" title="<?= View::e(Text::get('about.open')) ?>">
        <img src="/assets/logo.png" alt="<?= View::e(Text::get('app.name')) ?>">
    </button>
    <?php if ($isLoggedIn): ?>
        <nav class="nav">
            <a href="/"><?= View::e(Text::get('nav.dashboard')) ?></a>
            <a href="/endpoints"><?= View::e(Text::get('nav.endpoints')) ?></a>
            <a href="/clients"><?= View::e(Text::get('nav.clients')) ?></a>
            <a href="/queue"><?= View::e(Text::get('nav.queue')) ?></a>
            <a href="/archive"><?= View::e(Text::get('nav.archive')) ?></a>
            <a href="/settings"><?= View::e(Text::get('nav.settings')) ?></a>
        </nav>
        <form method="post" action="/logout">
            <button class="button button-secondary" type="submit"><?= View::e(Text::get('action.logout')) ?></button>
        </form>
    <?php endif; ?>
</header>

<dialog id="about-modal" class="about-modal" aria-labelledby="about-modal-title">
    <img class="about-hero" src="/assets/hero.png" alt="">
    <div class="about-body">
        <div class="about-heading">
            <h2 class="about-brand" id="about-modal-title"><?= View::e(Text::get('app.name')) ?></h2>
            <button type="button" class="about-close" onclick="document.getElementById('about-modal').close()" aria-label="<?= View::e(Text::get('action.close')) ?>">&times;</button>
        </div>
        <p class="about-description"><?= View::e(Text::get('about.description')) ?></p>
        <dl class="definition-list">
            <dt><?= View::e(Text::get('about.version')) ?></dt>
            <dd><?= View::e(PRINTBRIDGE_VERSION) ?></dd>
            <dt><?= View::e(Text::get('about.author')) ?></dt>
            <dd>Sayeh Ava Pazouki</dd>
            <dt><?= View::e(Text::get('about.license')) ?></dt>
            <dd><?= View::e(Text::get('about.license_name')) ?></dd>
        </dl>
        <p class="about-license-note"><?= View::e(Text::get('about.license_note')) ?></p>
    </div>
</dialog>
<script>
    document.getElementById('about-modal').addEventListener('click', function (event) {
        if (event.target === this) {
            this.close();
        }
    });
</script>

<main class="page">
