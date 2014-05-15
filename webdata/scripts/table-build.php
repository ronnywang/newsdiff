#!/usr/bin/env php
<?php

$path = dirname(__DIR__);

include($path . '/init.inc.php');
Pix_Table::$_save_memory = true;

foreach(glob($path . '/models/*.php') AS $m) {
    $p = pathinfo($m);
    $o = new $p['filename'];
    if($o instanceof Pix_Table) {
        $o->createTable();
    }
}