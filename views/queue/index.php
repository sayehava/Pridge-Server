<?php

use PrintBridge\Support\Text;
use PrintBridge\Support\View;

$title = Text::get('queue.title');

$renderJobTable = static function (array $jobs) {
    ?>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th><?= View::e(Text::get('table.status')) ?></th>
                <th><?= View::e(Text::get('table.endpoint')) ?></th>
                <th><?= View::e(Text::get('table.assigned_clients')) ?></th>
                <th><?= View::e(Text::get('table.reserved_by')) ?></th>
                <th><?= View::e(Text::get('table.content_type')) ?></th>
                <th><?= View::e(Text::get('table.created')) ?></th>
                <th><?= View::e(Text::get('table.completed')) ?></th>
                <th><?= View::e(Text::get('table.failed')) ?></th>
                <th><?= View::e(Text::get('table.actions')) ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($jobs as $job): ?>
                <tr>
                    <td><?= (int) $job['id'] ?></td>
                    <td><span class="badge badge-<?= View::e((string) $job['status']) ?>"><?= View::e((string) $job['status']) ?></span></td>
                    <td><?= View::e((string) $job['endpoint_name']) ?></td>
                    <td><?= View::e((string) ($job['assigned_client_names'] ?? '')) ?></td>
                    <td><?= View::e((string) ($job['reserved_client_name'] ?? '')) ?></td>
                    <td><?= View::e((string) $job['content_type']) ?></td>
                    <td><?= View::e((string) $job['created_at']) ?></td>
                    <td><?= View::e((string) ($job['completed_at'] ?? '')) ?></td>
                    <td><?= View::e((string) ($job['failed_at'] ?? '')) ?></td>
                    <td class="actions-cell">
                        <a class="button button-secondary" href="/queue/<?= (int) $job['id'] ?>"><?= View::e(Text::get('action.view')) ?></a>
                        <form class="table-action" method="post" action="/queue/<?= (int) $job['id'] ?>/delete" onsubmit="return confirm('Force delete this job? This cannot be undone.');">
                            <button class="button button-danger" type="submit"><?= View::e(Text::get('action.delete')) ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
};
?>
<section class="hero">
    <div>
        <h1><?= View::e(Text::get('queue.title')) ?></h1>
        <p><?= View::e(Text::get('queue.subtitle')) ?></p>
    </div>
</section>

<section class="panel">
    <h2><?= View::e(Text::get('queue.waiting_heading')) ?></h2>
    <p class="panel-help"><?= View::e(Text::get('queue.waiting_help')) ?></p>
    <?php if (empty($waiting)): ?>
        <p class="empty"><?= View::e(Text::get('empty.waiting')) ?></p>
    <?php else: ?>
        <?php $renderJobTable($waiting) ?>
    <?php endif; ?>
</section>

<section class="panel">
    <h2><?= View::e(Text::get('queue.archive_heading')) ?></h2>
    <p class="panel-help"><?= View::e(Text::get('queue.archive_help')) ?></p>
    <?php if (empty($archived)): ?>
        <p class="empty"><?= View::e(Text::get('empty.archive')) ?></p>
    <?php else: ?>
        <?php $renderJobTable($archived) ?>
    <?php endif; ?>
</section>
