<?php

use Pridge\Support\Text;
use Pridge\Support\View;

$title = Text::get('updates.title');
?>
<section class="hero">
    <div>
        <h1><?= View::e(Text::get('updates.title')) ?></h1>
        <p><?= View::e(Text::get('updates.subtitle')) ?></p>
    </div>
</section>

<?php if (!empty($message)): ?>
    <div class="notice"><strong><?= View::e(Text::get($message)) ?></strong></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert"><?= View::e(Text::get($error)) ?></div>
<?php endif; ?>

<section class="panel">
    <h2><?= View::e(Text::get('updates.current_version')) ?>: v<?= View::e($currentVersion) ?></h2>

    <p class="panel-help">
        <?= View::e(Text::get('updates.last_checked')) ?>:
        <?= $lastCheckedAt !== null ? View::e($lastCheckedAt) . ' UTC' : View::e(Text::get('updates.never_checked')) ?>
    </p>

    <?php if ($lastCheckError !== null): ?>
        <div class="alert"><?= View::e(Text::get('updates.check_failed')) ?>: <?= View::e($lastCheckError) ?></div>
    <?php endif; ?>

    <form method="post" action="/updates/check" class="table-action">
        <button class="button button-secondary" type="submit"><?= View::e(Text::get('updates.check_now')) ?></button>
    </form>

    <?php if ($updateAvailable && $latest !== null): ?>
        <div class="notice">
            <strong><?= View::e(Text::get('updates.available')) ?>: <?= View::e($latest['tag']) ?></strong>
            <?php if ($latest['notes'] !== ''): ?>
                <p class="panel-help"><?= View::e(Text::get('updates.release_notes')) ?>:</p>
                <pre class="update-notes"><?= View::e($latest['notes']) ?></pre>
            <?php endif; ?>

            <?php if ($staged === null): ?>
                <p class="panel-help"><?= View::e(Text::get('updates.prepare_hint')) ?></p>
                <form method="post" action="/updates/prepare" class="table-action">
                    <button class="button" type="submit"><?= View::e(Text::get('updates.prepare')) ?></button>
                </form>
            <?php endif; ?>
        </div>
    <?php elseif ($latest !== null): ?>
        <p class="panel-help"><?= View::e(Text::get('updates.up_to_date')) ?></p>
    <?php endif; ?>
</section>

<?php if ($staged !== null): ?>
    <section class="panel">
        <h2><?= View::e(Text::get('updates.staged_heading')) ?></h2>
        <p class="panel-help">
            <?= View::e(sprintf(Text::get('updates.staged_body'), $staged['version'], basename($staged['backup_path']))) ?>
        </p>
        <form method="post" action="/updates/apply" class="table-action" onsubmit="return confirm('<?= View::e(Text::get('updates.apply_confirm')) ?>');">
            <input type="hidden" name="csrf_token" value="<?= View::e($csrfToken) ?>">
            <button class="button" type="submit"><?= View::e(Text::get('updates.apply')) ?></button>
        </form>
        <form method="post" action="/updates/discard" class="table-action">
            <button class="button button-secondary" type="submit"><?= View::e(Text::get('updates.discard')) ?></button>
        </form>
    </section>
<?php endif; ?>

<section class="panel">
    <h2><?= View::e(Text::get('updates.backups_heading')) ?></h2>
    <p class="panel-help"><?= View::e(sprintf(Text::get('updates.backups_hint'), 5)) ?></p>

    <?php if ($backups === []): ?>
        <p class="empty"><?= View::e(Text::get('updates.no_backups')) ?></p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th><?= View::e(Text::get('updates.backup_created_at')) ?></th>
                    <th><?= View::e(Text::get('updates.backup_size')) ?></th>
                    <th><?= View::e(Text::get('table.actions')) ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($backups as $backup): ?>
                    <tr>
                        <td><?= View::e($backup['created_at']) ?> UTC</td>
                        <td><?= View::e(number_format($backup['size'] / 1048576, 1)) ?> MB</td>
                        <td>
                            <form method="post" action="/updates/rollback" class="table-action" onsubmit="return confirm('<?= View::e(Text::get('updates.restore_confirm')) ?>');">
                                <input type="hidden" name="csrf_token" value="<?= View::e($csrfToken) ?>">
                                <input type="hidden" name="backup" value="<?= View::e($backup['name']) ?>">
                                <button class="button button-danger" type="submit"><?= View::e(Text::get('updates.restore')) ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
