<?php

use Pridge\Support\Text;
use Pridge\Support\View;

$title = Text::get('queue.job_title') . ' #' . (int) $job['id'];
$metadata = null;

if (!empty($job['metadata_json']) && is_string($job['metadata_json'])) {
    $decoded = json_decode($job['metadata_json'], true);
    $metadata = json_last_error() === JSON_ERROR_NONE ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : $job['metadata_json'];
}
?>
<section class="hero">
    <div>
        <h1><?= View::e(Text::get('queue.job_title')) ?> #<?= (int) $job['id'] ?></h1>
        <p><?= View::e((string) $job['endpoint_name']) ?></p>
    </div>
    <a class="button button-secondary" href="<?= $isArchived ? '/archive' : '/queue' ?>"><?= View::e(Text::get($isArchived ? 'action.back_to_archive' : 'action.back_to_queue')) ?></a>
</section>

<section class="panel">
    <dl class="definition-list">
        <dt><?= View::e(Text::get('table.status')) ?></dt>
        <dd><span class="badge badge-<?= View::e((string) $job['status']) ?>"><?= View::e((string) $job['status']) ?></span></dd>

        <dt><?= View::e(Text::get('table.assigned_clients')) ?></dt>
        <dd><?= View::e((string) ($job['assigned_client_names'] ?? '')) ?></dd>

        <dt><?= View::e(Text::get('table.reserved_by')) ?></dt>
        <dd><?= View::e((string) ($job['reserved_client_name'] ?? '')) ?></dd>

        <dt><?= View::e(Text::get('table.content_type')) ?></dt>
        <dd><?= View::e((string) $job['content_type']) ?></dd>

        <dt><?= View::e(Text::get('table.created')) ?></dt>
        <dd><?= View::e((string) $job['created_at']) ?></dd>

        <dt><?= View::e(Text::get('table.picked_up')) ?></dt>
        <dd><?= View::e((string) ($job['picked_up_at'] ?? '')) ?></dd>

        <dt><?= View::e(Text::get('table.completed')) ?></dt>
        <dd><?= View::e((string) ($job['completed_at'] ?? '')) ?></dd>

        <dt><?= View::e(Text::get('table.failed')) ?></dt>
        <dd><?= View::e((string) ($job['failed_at'] ?? '')) ?></dd>

        <?php if (!empty($job['last_error'])): ?>
            <dt><?= View::e(Text::get('table.last_error')) ?></dt>
            <dd><?= View::e((string) $job['last_error']) ?></dd>
        <?php endif; ?>

        <?php if ($metadata !== null): ?>
            <dt><?= View::e(Text::get('queue.metadata')) ?></dt>
            <dd><code><?= View::e($metadata) ?></code></dd>
        <?php endif; ?>
    </dl>
</section>

<section class="panel">
    <h2><?= View::e(Text::get('queue.preview')) ?></h2>

    <?php if ($preview['type'] === 'image'): ?>
        <img class="preview-image" src="/queue/<?= (int) $job['id'] ?>/payload" alt="<?= View::e(Text::get('queue.job_title')) ?> #<?= (int) $job['id'] ?> print preview">
    <?php elseif ($preview['type'] === 'pdf'): ?>
        <iframe class="preview-pdf" src="/queue/<?= (int) $job['id'] ?>/payload" title="<?= View::e(Text::get('queue.job_title')) ?> #<?= (int) $job['id'] ?> print preview"></iframe>
    <?php elseif ($preview['type'] === 'text'): ?>
        <pre class="preview-text"><?= View::e((string) $previewText) ?></pre>
        <?php if ($previewTruncated): ?>
            <p class="empty"><?= View::e(Text::get('queue.preview_truncated')) ?></p>
        <?php endif; ?>
    <?php else: ?>
        <p class="empty"><?= View::e(Text::get('queue.preview_unavailable')) ?></p>
    <?php endif; ?>

    <p class="panel-actions">
        <a class="button button-secondary" href="/queue/<?= (int) $job['id'] ?>/payload" download="job-<?= (int) $job['id'] ?>"><?= View::e(Text::get('action.download')) ?></a>
    </p>
</section>

<form method="post" action="/queue/<?= (int) $job['id'] ?>/delete" onsubmit="return confirm('Force delete this job? This cannot be undone.');">
    <button class="button button-danger" type="submit"><?= View::e(Text::get('action.delete')) ?></button>
</form>
