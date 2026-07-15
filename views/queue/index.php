<?php

use Pridge\Support\Text;
use Pridge\Support\View;

$title = Text::get('queue.title');
?>
<section class="hero">
    <div>
        <h1><?= View::e(Text::get('queue.title')) ?></h1>
        <p><?= View::e(Text::get('queue.subtitle')) ?></p>
    </div>
</section>

<section class="panel">
    <?php
    $emptyTextKey = 'empty.waiting';
    $deleteSelectedAction = '/queue/delete-selected';
    $deleteAllAction = '/queue/delete-all';
    $deleteAllConfirmKey = 'confirm.delete_all_waiting';
    require PRIDGE_ROOT . '/views/queue/_table.php';
    ?>
</section>
