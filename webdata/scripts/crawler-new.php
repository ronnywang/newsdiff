<?php

include(__DIR__ . '/../init.inc.php');
Pix_Table::$_save_memory = true;

$crawlers = NewsSourcesCfg::getCrawlers();

$insert_count = 0;
$max_insert = 500;
foreach ($crawlers as $id => $class) {
    if ($max_insert - $insert_count <= 0) {
        break;
    }
    try {
        list($update, $insert) = Crawler::crawl($id, $class, $max_insert - $insert_count);
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
