<?php

include(__DIR__ . '/../init.inc.php');
Pix_Table::$_save_memory = true;

$crawlers = array(
    1 => 'Crawler_Appledaily',
    2 => 'Crawler_Chinatimes',
    3 => 'Crawler_CNA',
    4 => 'Crawler_Ettoday',
    5 => 'Crawler_Libertytimes',
    6 => 'Crawler_Newtalk',
    7 => 'Crawler_Nownews',
    8 => 'Crawler_UDN',
    9 => 'Crawler_TVBS',
    10 => 'Crawler_BCC',
    11 => 'Crawler_PTS',
    12 => 'Crawler_TTV',
    13 => 'Crawler_CTS',
    14 => 'Crawler_FTV',
//    15 => 'Crawler_SETNews',
    16 => 'Crawler_StormMediaGroup'
);

$insert_count = 0;
$max_insert = 500;
foreach ($crawlers as $id => $class) {
    if ($max_insert - $insert_count <= 0) {
        break;
    }
    try {
        list($update, $insert) = call_user_func(array($class, 'crawl'), $max_insert - $insert_count);
    } catch (Exception $e) {
        error_log("$class failed: " . $e->getMessage());
        continue;
    }
    if ($update) {
        KeyValue::set('source_update-' . $id, time());
    }
    if ($insert) {
        $insert_count += $insert;
        KeyValue::set('source_insert-' . $id, time());
    }
}

error_log('Insert_count: ' . $insert_count);
