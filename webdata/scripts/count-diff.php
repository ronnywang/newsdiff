<?php

include(__DIR__ . '/../init.inc.php');
Pix_Table::$_save_memory = true;

$fetch_at = intval(KeyValue::get('countdiff-time'));
foreach (News::search("last_fetch_at >= $fetch_at")->order('last_fetch_at ASC') as $news) {
    $news->generateDiff();
}
if ($news) {
    KeyValue::set('countdiff-time', $news->last_fetch_at);
}
