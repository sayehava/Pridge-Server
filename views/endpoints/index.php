<?php

use PrintBridge\Support\Text;
use PrintBridge\Support\View;

$title = Text::get('endpoints.title');
?>
<section class="hero">
    <div>
        <h1><?= View::e(Text::get('endpoints.title')) ?></h1>
        <p><?= View::e(Text::get('endpoints.subtitle')) ?></p>
    </div>
</section>

<?php if (!empty($error)): ?>
    <div class="alert"><?= View::e(Text::get($error)) ?></div>
<?php endif; ?>

<?php if (!empty($token)): ?>
    <div class="notice">
        <strong><?= View::e(Text::get('endpoints.token_created')) ?></strong>
        <code><?= View::e($token) ?></code>
    </div>
<?php endif; ?>

<section class="panel">
    <h2><?= View::e(Text::get('endpoints.create')) ?></h2>
    <form method="post" class="inline-form">
        <label>
            <?= View::e(Text::get('field.name')) ?>
            <input name="name" required>
        </label>
        <button class="button" type="submit"><?= View::e(Text::get('action.create')) ?></button>
    </form>
</section>

<section class="panel">
    <?php if (empty($endpoints)): ?>
        <p class="empty"><?= View::e(Text::get('empty.endpoints')) ?></p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th><?= View::e(Text::get('table.name')) ?></th>
                    <th><?= View::e(Text::get('table.status')) ?></th>
                    <th><?= View::e(Text::get('table.created')) ?></th>
                    <th><?= View::e(Text::get('table.actions')) ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($endpoints as $endpoint): ?>
                    <tr>
                        <td><?= View::e((string) $endpoint['name']) ?></td>
                        <td><?= ((int) $endpoint['enabled'] === 1) ? View::e(Text::get('status.enabled')) : View::e(Text::get('status.disabled')) ?></td>
                        <td><?= View::e((string) $endpoint['created_at']) ?></td>
                        <td>
                            <form class="table-action" method="post" action="/endpoints/<?= (int) $endpoint['id'] ?>/toggle">
                                <button class="button button-secondary" type="submit">
                                    <?= ((int) $endpoint['enabled'] === 1) ? View::e(Text::get('action.disable')) : View::e(Text::get('action.enable')) ?>
                                </button>
                            </form>
                            <form class="table-action" method="post" action="/endpoints/<?= (int) $endpoint['id'] ?>/delete">
                                <button class="button button-danger" type="submit"><?= View::e(Text::get('action.delete')) ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
