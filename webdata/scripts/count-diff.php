<?php

include(__DIR__ . '/../init.inc.php');
Pix_Table::$_save_memory = true;
Pix_Table::addStaticResultSetHelper('Pix_Array_Volume');

$start_at = time();
$fetch_at = intval(KeyValue::get('countdiff-time'));
foreach (News::search("last_fetch_at > last_diff_at")->order('last_fetch_at DESC')->volumemode(1000) as $news) {
    $news->generateDiff();
    KeyValue::set('countdiff-time', $news->last_fetch_at);
    // 最多只跑五分鐘
    if (time() - $start_at > 300) {
        exit;
    }
}
