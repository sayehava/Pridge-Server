<?php

use Pridge\Support\Text;
use Pridge\Support\View;

$title = Text::get('dashboard.title');
?>
<section class="hero">
    <div>
        <h1><?= View::e(Text::get('dashboard.title')) ?></h1>
        <p><?= View::e(Text::get('dashboard.subtitle')) ?></p>
    </div>
    <span class="status-pill"><?= View::e(Text::get('status.ready')) ?></span>
</section>

<section class="metric-grid">
    <article class="metric">
        <span><?= View::e(Text::get('metric.endpoints')) ?></span>
        <strong><?= (int) $counts['endpoints'] ?></strong>
    </article>
    <article class="metric">
        <span><?= View::e(Text::get('metric.clients')) ?></span>
        <strong><?= (int) $counts['clients'] ?></strong>
    </article>
    <article class="metric">
        <span><?= View::e(Text::get('metric.waiting_jobs')) ?></span>
        <strong><?= (int) $counts['pending'] ?></strong>
    </article>
    <article class="metric">
        <span><?= View::e(Text::get('metric.failed_jobs')) ?></span>
        <strong><?= (int) $counts['failed'] ?></strong>
    </article>
</section>
