<?php

declare(strict_types=1);

use Pridge\Support\Pagination;
use Pridge\Support\Text;
use Pridge\Support\View;

/** @var int $total */
/** @var int $page */
/** @var int|null $perPage */
/** @var string $basePath */

$totalPages = Pagination::totalPages($total, $perPage);
$page = min($page, $totalPages);
$prevPage = max(1, $page - 1);
$nextPage = min($totalPages, $page + 1);
?>
<div class="pagination">
    <form class="pagination-size" method="get" action="<?= View::e($basePath) ?>">
        <input type="hidden" name="page" value="1">
        <label>
            <?= View::e(Text::get('pagination.per_page')) ?>
            <select name="per_page" onchange="this.form.submit()">
                <?php foreach (Pagination::PAGE_SIZES as $size): ?>
                    <option value="<?= $size ?>"<?= $perPage === $size ? ' selected' : '' ?>><?= $size ?></option>
                <?php endforeach; ?>
                <option value="all"<?= $perPage === null ? ' selected' : '' ?>><?= View::e(Text::get('pagination.all')) ?></option>
            </select>
        </label>
    </form>

    <?php if ($perPage !== null && $totalPages > 1): ?>
        <nav class="pagination-nav" aria-label="Pagination">
            <?php if ($page <= 1): ?>
                <span class="button button-secondary is-disabled">&lsaquo; <?= View::e(Text::get('pagination.previous')) ?></span>
            <?php else: ?>
                <a class="button button-secondary" href="<?= View::e($basePath) ?>?per_page=<?= $perPage ?>&page=<?= $prevPage ?>">&lsaquo; <?= View::e(Text::get('pagination.previous')) ?></a>
            <?php endif; ?>

            <span class="pagination-status"><?= View::e(Text::get('pagination.page_label')) ?> <?= $page ?> <?= View::e(Text::get('pagination.of_label')) ?> <?= $totalPages ?></span>

            <?php if ($page >= $totalPages): ?>
                <span class="button button-secondary is-disabled"><?= View::e(Text::get('pagination.next')) ?> &rsaquo;</span>
            <?php else: ?>
                <a class="button button-secondary" href="<?= View::e($basePath) ?>?per_page=<?= $perPage ?>&page=<?= $nextPage ?>"><?= View::e(Text::get('pagination.next')) ?> &rsaquo;</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</div>
