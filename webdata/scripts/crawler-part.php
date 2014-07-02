<?php

include(__DIR__ . '/../init.inc.php');

$part = $_SERVER['argv'][1];
$total = $_SERVER['argv'][2];

$key = "crawlering-{$part}-{$total}";
if ($_SERVER['argv'][3] != 'force' and $time = intval(KeyValue::get($key)) and time() - $time < 600) {
    exit;
}
KeyValue::set($key, time());

Pix_Table::$_save_memory = true;
Crawler::updatePart($part, $total);

KeyValue::set($key, 0);
// 清掉網址
KeyValue::set('crawling-' . $part, '');
