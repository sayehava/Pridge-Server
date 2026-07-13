<?php

use PrintBridge\Support\Text;
use PrintBridge\Support\View;

$title = Text::get('archive.title');
?>
<section class="hero">
    <div>
        <h1><?= View::e(Text::get('archive.title')) ?></h1>
        <p><?= View::e(Text::get('archive.subtitle')) ?></p>
    </div>
</section>

<section class="panel">
    <?php $emptyTextKey = 'empty.archive'; require PRINTBRIDGE_ROOT . '/views/queue/_table.php'; ?>
</section>
