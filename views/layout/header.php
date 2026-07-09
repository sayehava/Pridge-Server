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
    <a class="brand" href="/"><?= View::e(Text::get('app.name')) ?></a>
    <?php if ($isLoggedIn): ?>
        <nav class="nav">
            <a href="/"><?= View::e(Text::get('nav.dashboard')) ?></a>
            <a href="/endpoints"><?= View::e(Text::get('nav.endpoints')) ?></a>
            <a href="/clients"><?= View::e(Text::get('nav.clients')) ?></a>
            <a href="/queue"><?= View::e(Text::get('nav.queue')) ?></a>
            <a href="/settings"><?= View::e(Text::get('nav.settings')) ?></a>
        </nav>
        <form method="post" action="/logout">
            <button class="button button-secondary" type="submit"><?= View::e(Text::get('action.logout')) ?></button>
        </form>
    <?php endif; ?>
</header>
<main class="page">
