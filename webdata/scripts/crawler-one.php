<?php

include(__DIR__ . '/../init.inc.php');

if (KeyValue::get('crawlering')) {
    exit;
}
KeyValue::set('crawlering', time());

Pix_Table::$_save_memory = true;
Crawler::updateAllRaw();

KeyValue::set('crawlering', 0);
