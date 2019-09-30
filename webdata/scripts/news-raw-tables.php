#!/usr/bin/env php
<?php
include(__DIR__ . '/../init.inc.php');
/*
 * to create 12 tables for NewsRaw
 */
$theTime = mktime();
$str = 'CREATE TABLE IF NOT EXISTS `news_raw_%s` LIKE news_raw';
$db = NewsRaw::getDb();
for ($i = 0; $i < 12; $i++) {
    $db->query(sprintf($str, date('Ym', $theTime)));
    $theTime = strtotime('+1 month', $theTime);
}
