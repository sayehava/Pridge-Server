<?php

use PrintBridge\Support\Text;
use PrintBridge\Support\View;

$title = Text::get('login.title');
?>
<section class="auth-panel">
    <h1><?= View::e(Text::get('login.title')) ?></h1>
    <?php if (!empty($error)): ?>
        <div class="alert"><?= View::e(Text::get($error)) ?></div>
    <?php endif; ?>
    <form method="post" class="form">
        <label>
            <?= View::e(Text::get('field.username')) ?>
            <input name="username" autocomplete="username" required>
        </label>
        <label>
            <?= View::e(Text::get('field.password')) ?>
            <input name="password" type="password" autocomplete="current-password" required>
        </label>
        <button class="button" type="submit"><?= View::e(Text::get('action.login')) ?></button>
    </form>
</section>
