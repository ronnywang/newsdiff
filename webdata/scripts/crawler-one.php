<?php

include(__DIR__ . '/../init.inc.php');

// 10 分鐘內如果沒有 crawler-one 就重作
if ($time = intval(KeyValue::get('crawlering')) and time() - $time < 600) {
    exit;
}
KeyValue::set('crawlering', time());

Pix_Table::$_save_memory = true;
Crawler::updateAllRaw();

KeyValue::set('crawlering', 0);
