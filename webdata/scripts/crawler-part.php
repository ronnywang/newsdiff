<?php

include(__DIR__ . '/../init.inc.php');

$part = $_SERVER['argv'][1];
$total = $_SERVER['argv'][2];

if ($_SERVER['argv'][1] != 'force' and $time = intval(KeyValue::get('crawlering-' . $part)) and time() - $time < 600) {
    exit;
}
KeyValue::set('crawlering-' . $part, time());

Pix_Table::$_save_memory = true;
Crawler::updatePart($part, $total);

KeyValue::set('crawlering-' . $part, 0);
// 清掉網址
KeyValue::set('crawling-' . $part, '');
