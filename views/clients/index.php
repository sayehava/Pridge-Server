<?php

use PrintBridge\Support\Text;
use PrintBridge\Support\View;

$title = Text::get('clients.title');
?>
<section class="hero">
    <div>
        <h1><?= View::e(Text::get('clients.title')) ?></h1>
        <p><?= View::e(Text::get('clients.subtitle')) ?></p>
    </div>
</section>

<?php if (!empty($error)): ?>
    <div class="alert"><?= View::e(Text::get($error)) ?></div>
<?php endif; ?>

<?php if (!empty($token)): ?>
    <div class="notice">
        <strong><?= View::e(Text::get($tokenMessage ?? 'clients.token_created')) ?></strong>
        <code><?= View::e($token) ?></code>
    </div>
<?php endif; ?>

<section class="panel">
    <h2><?= View::e(Text::get('clients.create')) ?></h2>
    <form method="post" class="form">
        <label>
            <?= View::e(Text::get('field.name')) ?>
            <input name="name" required>
        </label>
        <label>
            <?= View::e(Text::get('field.assign_endpoints')) ?>
            <select name="endpoint_ids[]" multiple>
                <?php foreach ($endpoints as $endpoint): ?>
                    <option value="<?= (int) $endpoint['id'] ?>"><?= View::e((string) $endpoint['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="button" type="submit"><?= View::e(Text::get('action.create')) ?></button>
    </form>
</section>

<section class="panel">
    <?php if (empty($clients)): ?>
        <p class="empty"><?= View::e(Text::get('empty.clients')) ?></p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th><?= View::e(Text::get('table.name')) ?></th>
                    <th><?= View::e(Text::get('table.status')) ?></th>
                    <th><?= View::e(Text::get('table.assignments')) ?></th>
                    <th><?= View::e(Text::get('table.created')) ?></th>
                    <th><?= View::e(Text::get('table.actions')) ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($clients as $client): ?>
                    <tr>
                        <td>
                            <form class="table-action" method="post" action="/clients/<?= (int) $client['id'] ?>/rename">
                                <input name="name" value="<?= View::e((string) $client['name']) ?>" required>
                                <button class="button button-secondary" type="submit"><?= View::e(Text::get('action.rename')) ?></button>
                            </form>
                        </td>
                        <td>
                            <span class="badge <?= ((int) $client['enabled'] === 1) ? 'badge-enabled' : 'badge-disabled' ?>">
                                <?= ((int) $client['enabled'] === 1) ? View::e(Text::get('status.enabled')) : View::e(Text::get('status.disabled')) ?>
                            </span>
                        </td>
                        <td><?= View::e((string) ($client['endpoint_names'] ?? '')) ?></td>
                        <td><?= View::e((string) $client['created_at']) ?></td>
                        <td class="actions-cell">
                            <form class="table-action" method="post" action="/clients/<?= (int) $client['id'] ?>/toggle">
                                <button class="button button-secondary" type="submit">
                                    <?= ((int) $client['enabled'] === 1) ? View::e(Text::get('action.disable')) : View::e(Text::get('action.enable')) ?>
                                </button>
                            </form>
                            <form class="table-action" method="post" action="/clients/<?= (int) $client['id'] ?>/regenerate" onsubmit="return confirm('Regenerate the token for this client? The old token and any active sessions will stop working immediately.');">
                                <button class="button button-secondary" type="submit"><?= View::e(Text::get('action.regenerate')) ?></button>
                            </form>
                            <form class="table-action" method="post" action="/clients/<?= (int) $client['id'] ?>/delete">
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
