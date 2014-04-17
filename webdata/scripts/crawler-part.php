<?php

include(__DIR__ . '/../init.inc.php');

$part = $_SERVER['argv'][1];
$total = $_SERVER['argv'][2];
KeyValue::set('crawlering-' . $part, time());

Pix_Table::$_save_memory = true;
Crawler::updatePart($part, $total);

KeyValue::set('crawlering-' . $part, 0);
// 清掉網址
KeyValue::set('crawling-' . $part, '');
