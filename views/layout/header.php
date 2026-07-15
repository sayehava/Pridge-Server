<?php

use Pridge\Services\AdminAuth;
use Pridge\Support\Text;
use Pridge\Support\View;

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
<header class="topbar" id="topbar">
    <button type="button" class="brand" onclick="document.getElementById('about-modal').showModal()" aria-haspopup="dialog" title="<?= View::e(Text::get('about.open')) ?>">
        <img src="/assets/logo.png" alt="<?= View::e(Text::get('app.name')) ?>">
    </button>
    <?php if ($isLoggedIn): ?>
        <button type="button" class="nav-toggle" id="nav-toggle" aria-expanded="false" aria-controls="site-nav" aria-label="<?= View::e(Text::get('action.toggle_nav')) ?>">
            <span></span><span></span><span></span>
        </button>
        <nav class="nav" id="site-nav">
            <a href="/"><?= View::e(Text::get('nav.dashboard')) ?></a>
            <a href="/endpoints"><?= View::e(Text::get('nav.endpoints')) ?></a>
            <a href="/clients"><?= View::e(Text::get('nav.clients')) ?></a>
            <a href="/queue"><?= View::e(Text::get('nav.queue')) ?></a>
            <a href="/archive"><?= View::e(Text::get('nav.archive')) ?></a>
            <a href="/settings"><?= View::e(Text::get('nav.settings')) ?></a>
        </nav>
        <form method="post" action="/logout" class="logout-form">
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
            <dd><?= View::e(PRIDGE_VERSION) ?></dd>
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

    (function () {
        var toggle = document.getElementById('nav-toggle');
        var topbar = document.getElementById('topbar');

        if (!toggle || !topbar) {
            return;
        }

        toggle.addEventListener('click', function () {
            var isOpen = topbar.classList.toggle('nav-open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    })();
</script>

<main class="page">
