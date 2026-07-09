<?php

use PrintBridge\Support\Text;
use PrintBridge\Support\View;

$title = Text::get('settings.title');
?>
<section class="hero">
    <div>
        <h1><?= View::e(Text::get('settings.title')) ?></h1>
        <p><?= View::e(Text::get('settings.subtitle')) ?></p>
    </div>
</section>

<?php if (!empty($message)): ?>
    <div class="notice"><strong><?= View::e(Text::get($message)) ?></strong></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert"><?= View::e(Text::get($error)) ?></div>
<?php endif; ?>

<section class="panel">
    <h2><?= View::e(Text::get('settings.account')) ?></h2>
    <form method="post" action="/settings/password" class="form">
        <label>
            <?= View::e(Text::get('field.current_password')) ?>
            <input name="current_password" type="password" autocomplete="current-password" required>
        </label>
        <label>
            <?= View::e(Text::get('field.new_password')) ?>
            <input name="new_password" type="password" autocomplete="new-password" minlength="12" required>
        </label>
        <button class="button" type="submit"><?= View::e(Text::get('action.update')) ?></button>
    </form>
</section>

<section class="panel">
    <h2><?= View::e(Text::get('settings.storage')) ?></h2>
    <dl class="definition-list">
        <dt><?= View::e(Text::get('settings.sqlite_database')) ?></dt>
        <dd><?= View::e($databasePath) ?></dd>
    </dl>
</section>
