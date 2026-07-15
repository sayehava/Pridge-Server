<?php

use Pridge\Support\Text;
use Pridge\Support\View;

$title = Text::get('password.forgot_title');
?>
<section class="auth-panel">
    <h1><?= View::e(Text::get('password.forgot_title')) ?></h1>
    <p><?= View::e(Text::get('password.forgot_help')) ?></p>
    <?php if (!empty($message)): ?>
        <div class="notice"><strong><?= View::e(Text::get($message)) ?></strong></div>
    <?php endif; ?>
    <form method="post" class="form">
        <label>
            <?= View::e(Text::get('field.username')) ?>
            <input name="username" autocomplete="username" required>
        </label>
        <button class="button" type="submit"><?= View::e(Text::get('action.send_reset_link')) ?></button>
    </form>
</section>
