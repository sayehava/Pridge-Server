<?php

use PrintBridge\Support\Text;
use PrintBridge\Support\View;

$title = Text::get('queue.title');
?>
<section class="hero">
    <div>
        <h1><?= View::e(Text::get('queue.title')) ?></h1>
        <p><?= View::e(Text::get('queue.subtitle')) ?></p>
    </div>
</section>

<section class="panel">
    <?php $emptyTextKey = 'empty.waiting'; require PRINTBRIDGE_ROOT . '/views/queue/_table.php'; ?>
</section>
