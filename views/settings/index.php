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
    <h2><?= View::e(Text::get('settings.archive_retention')) ?></h2>
    <p class="panel-help"><?= View::e(Text::get('settings.archive_retention_help')) ?></p>
    <form method="post" action="/settings/archive-retention" class="form">
        <label class="radio-option">
            <input type="radio" name="mode" value="never" <?= $archiveMode === 'never' ? 'checked' : '' ?> onchange="printbridgeToggleArchiveRetention()">
            <span><?= View::e(Text::get('archive_retention.never')) ?></span>
        </label>

        <label class="radio-option">
            <input type="radio" name="mode" value="preset" <?= $archiveMode === 'preset' ? 'checked' : '' ?> onchange="printbridgeToggleArchiveRetention()">
            <span><?= View::e(Text::get('archive_retention.preset')) ?></span>
            <select name="preset_days" id="archive-preset-select" <?= $archiveMode !== 'preset' ? 'disabled' : '' ?>>
                <?php foreach ($archivePresets as $days => $labelKey): ?>
                    <option value="<?= $days ?>" <?= ($archiveMode === 'preset' && $archiveDays === $days) ? 'selected' : '' ?>><?= View::e(Text::get($labelKey)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label class="radio-option">
            <input type="radio" name="mode" value="custom" <?= $archiveMode === 'custom' ? 'checked' : '' ?> onchange="printbridgeToggleArchiveRetention()">
            <span><?= View::e(Text::get('archive_retention.custom')) ?></span>
            <input type="number" name="custom_days" id="archive-custom-input" min="1" max="3650"
                   value="<?= $archiveMode === 'custom' ? (int) $archiveDays : '' ?>"
                   placeholder="<?= View::e(Text::get('field.custom_days')) ?>"
                <?= $archiveMode !== 'custom' ? 'disabled' : '' ?>>
        </label>

        <button class="button" type="submit"><?= View::e(Text::get('action.update')) ?></button>
    </form>
</section>

<script>
    function printbridgeToggleArchiveRetention() {
        var mode = document.querySelector('input[name="mode"]:checked').value;
        document.getElementById('archive-preset-select').disabled = mode !== 'preset';
        document.getElementById('archive-custom-input').disabled = mode !== 'custom';
    }
</script>

<section class="panel">
    <h2><?= View::e(Text::get('settings.mail')) ?></h2>
    <p class="panel-help"><?= View::e(Text::get('settings.mail_help')) ?></p>
    <form method="post" action="/settings/mail" class="form">
        <label class="radio-option">
            <input type="radio" name="driver" value="php_mail" <?= $mailDriver === 'php_mail' ? 'checked' : '' ?> onchange="printbridgeToggleMailDriver()">
            <span><?= View::e(Text::get('mail_driver.php_mail')) ?></span>
        </label>

        <label class="radio-option">
            <input type="radio" name="driver" value="smtp" <?= $mailDriver === 'smtp' ? 'checked' : '' ?> onchange="printbridgeToggleMailDriver()">
            <span><?= View::e(Text::get('mail_driver.smtp')) ?></span>
        </label>

        <div id="smtp-fields" <?= $mailDriver !== 'smtp' ? 'hidden' : '' ?>>
            <label>
                <?= View::e(Text::get('field.smtp_host')) ?>
                <input name="smtp_host" type="text" value="<?= View::e($smtpSettings['host']) ?>">
            </label>
            <label>
                <?= View::e(Text::get('field.smtp_port')) ?>
                <input name="smtp_port" type="number" min="1" max="65535" value="<?= View::e((string) $smtpSettings['port']) ?>">
            </label>
            <label>
                <?= View::e(Text::get('field.smtp_encryption')) ?>
                <select name="smtp_encryption">
                    <?php foreach (['tls', 'ssl', 'none'] as $option): ?>
                        <option value="<?= $option ?>" <?= $smtpSettings['encryption'] === $option ? 'selected' : '' ?>><?= View::e(Text::get('smtp_encryption.' . $option)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <?= View::e(Text::get('field.smtp_username')) ?>
                <input name="smtp_username" type="text" autocomplete="off" value="<?= View::e($smtpSettings['username']) ?>">
            </label>
            <label>
                <?= View::e(Text::get('field.smtp_password')) ?>
                <input name="smtp_password" type="password" autocomplete="new-password" placeholder="<?= $smtpSettings['password'] !== '' ? '••••••••' : '' ?>">
                <small><?= View::e(Text::get('field.smtp_password_help')) ?></small>
            </label>
            <label>
                <?= View::e(Text::get('field.smtp_from_address')) ?>
                <input name="smtp_from_address" type="email" value="<?= View::e($smtpSettings['from_address']) ?>">
            </label>
            <label>
                <?= View::e(Text::get('field.smtp_from_name')) ?>
                <input name="smtp_from_name" type="text" value="<?= View::e($smtpSettings['from_name']) ?>">
            </label>
        </div>

        <button class="button" type="submit"><?= View::e(Text::get('action.update')) ?></button>
    </form>
</section>

<script>
    function printbridgeToggleMailDriver() {
        var driver = document.querySelector('input[name="driver"]:checked').value;
        document.getElementById('smtp-fields').hidden = driver !== 'smtp';
    }
</script>

<section class="panel">
    <h2><?= View::e(Text::get('settings.storage')) ?></h2>
    <dl class="definition-list">
        <dt><?= View::e(Text::get('settings.sqlite_database')) ?></dt>
        <dd><?= View::e($databasePath) ?></dd>
    </dl>
</section>
