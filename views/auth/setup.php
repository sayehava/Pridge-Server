<?php

use PrintBridge\Support\Text;
use PrintBridge\Support\View;

$title = Text::get('setup.title');
?>
<section class="auth-panel">
    <h1><?= View::e(Text::get('setup.title')) ?></h1>
    <p><?= View::e(Text::get('setup.help')) ?></p>
    <?php if (!empty($error)): ?>
        <div class="alert"><?= View::e(Text::get($error)) ?></div>
    <?php endif; ?>
    <form method="post" class="form">
        <label>
            <?= View::e(Text::get('field.username')) ?>
            <input name="username" autocomplete="username" required>
        </label>
        <label>
            <?= View::e(Text::get('field.email')) ?>
            <input name="email" type="email" autocomplete="email">
        </label>
        <label>
            <?= View::e(Text::get('field.password')) ?>
            <input name="password" type="password" autocomplete="new-password" minlength="12" required>
        </label>
        <button class="button" type="submit"><?= View::e(Text::get('action.create')) ?></button>
    </form>
</section>
