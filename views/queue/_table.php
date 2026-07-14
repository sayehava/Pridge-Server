<?php

declare(strict_types=1);

use PrintBridge\Support\Text;
use PrintBridge\Support\View;

/** @var array<int, array<string, mixed>> $jobs */
/** @var string $emptyTextKey */
/** @var int $total */
/** @var int $page */
/** @var int|null $perPage */
/** @var string $basePath */
/** @var string $deleteSelectedAction */
/** @var string $deleteAllAction */
/** @var string $deleteAllConfirmKey */
?>
<?php if (empty($jobs) && $total === 0): ?>
    <p class="empty"><?= View::e(Text::get($emptyTextKey)) ?></p>
<?php else: ?>
    <div class="table-toolbar">
        <form method="post" action="<?= View::e($deleteAllAction) ?>" onsubmit="return confirm('<?= View::e(Text::get($deleteAllConfirmKey)) ?>');">
            <button class="button button-danger" type="submit"><?= View::e(Text::get('action.delete_all')) ?></button>
        </form>
    </div>

    <?php if (empty($jobs)): ?>
        <p class="empty"><?= View::e(Text::get($emptyTextKey)) ?></p>
    <?php else: ?>
        <form method="post" action="<?= View::e($deleteSelectedAction) ?>">
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all-jobs" aria-label="<?= View::e(Text::get('action.select_all')) ?>"></th>
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
                            <td><input type="checkbox" name="ids[]" value="<?= (int) $job['id'] ?>" class="job-select"></td>
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
                                <button class="button button-danger" type="submit" formaction="/queue/<?= (int) $job['id'] ?>/delete" onclick="return confirm('<?= View::e(Text::get('confirm.delete_job')) ?>');"><?= View::e(Text::get('action.delete')) ?></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="panel-actions">
                <button class="button button-danger" type="submit" onclick="return confirm('<?= View::e(Text::get('confirm.delete_selected')) ?>');"><?= View::e(Text::get('action.delete_selected')) ?></button>
            </div>
        </form>
    <?php endif; ?>

    <?php require PRINTBRIDGE_ROOT . '/views/queue/_pagination.php'; ?>

    <script>
        (function () {
            var selectAll = document.getElementById('select-all-jobs');

            if (!selectAll) {
                return;
            }

            selectAll.addEventListener('change', function () {
                var checkboxes = document.querySelectorAll('.job-select');
                for (var i = 0; i < checkboxes.length; i++) {
                    checkboxes[i].checked = selectAll.checked;
                }
            });
        })();
    </script>
<?php endif; ?>
