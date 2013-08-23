<?php

include(__DIR__ . '/../init.inc.php');
Pix_Table::$_save_memory = true;
Pix_Table::addStaticResultSetHelper('Pix_Array_Volume');

foreach (News::search("last_fetch_at > last_diff_at + 1")->order('last_fetch_at DESC')->volumemode(1000) as $news) {
    $news->generateDiff(false);
}
