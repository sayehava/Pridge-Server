<?php

use Pridge\Support\Text;
use Pridge\Support\View;

$title = Text::get('archive.title');
?>
<section class="hero">
    <div>
        <h1><?= View::e(Text::get('archive.title')) ?></h1>
        <p><?= View::e(Text::get('archive.subtitle')) ?></p>
    </div>
</section>

<section class="panel">
    <?php
    $emptyTextKey = 'empty.archive';
    $deleteSelectedAction = '/archive/delete-selected';
    $deleteAllAction = '/archive/delete-all';
    $deleteAllConfirmKey = 'confirm.delete_all_archived';
    require PRIDGE_ROOT . '/views/queue/_table.php';
    ?>
</section>
