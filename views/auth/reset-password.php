<?php

use PrintBridge\Support\Text;
use PrintBridge\Support\View;

$title = Text::get('password.reset_title');
?>
<section class="auth-panel">
    <h1><?= View::e(Text::get('password.reset_title')) ?></h1>
    <?php if (!empty($error)): ?>
        <div class="alert"><?= View::e(Text::get($error)) ?></div>
    <?php endif; ?>
    <form method="post" class="form">
        <input type="hidden" name="token" value="<?= View::e(is_string($token) ? $token : '') ?>">
        <label>
            <?= View::e(Text::get('field.new_password')) ?>
            <input name="password" type="password" autocomplete="new-password" minlength="12" required>
        </label>
        <button class="button" type="submit"><?= View::e(Text::get('action.update')) ?></button>
    </form>
</section>
