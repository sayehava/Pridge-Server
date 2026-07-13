<?php

declare(strict_types=1);

use PrintBridge\Support\Text;
use PrintBridge\Support\View;

/** @var array<int, array<string, mixed>> $jobs */
/** @var string $emptyTextKey */
?>
<?php if (empty($jobs)): ?>
    <p class="empty"><?= View::e(Text::get($emptyTextKey)) ?></p>
<?php else: ?>
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
<?php endif; ?>
