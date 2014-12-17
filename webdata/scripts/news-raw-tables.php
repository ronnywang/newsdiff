#!/usr/bin/env php
<?php
include(__DIR__ . '/../init.inc.php');
/*
 * to create 12 tables for NewsRaw
 */
$theTime = mktime();
$str = "CREATE TABLE IF NOT EXISTS `news_raw_%s` (
  `news_id` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  `raw` text NOT NULL,
  PRIMARY KEY (`news_id`,`time`)
) ENGINE=InnoDB;";
$db = NewsRaw::getDb();
for ($i = 0; $i < 12; $i++) {
    $db->query(sprintf($str, date('Ym', $theTime)));
    $theTime = strtotime('+1 month', $theTime);
}