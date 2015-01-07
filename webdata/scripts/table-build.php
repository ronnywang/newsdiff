#!/usr/bin/env php
<?php

$path = dirname(__DIR__);

include($path . '/init.inc.php');
Pix_Table::$_save_memory = true;
Pix_Setting::set('Table:DropTableEnable', true);

foreach(glob($path . '/models/*.php') AS $m) {
    $p = pathinfo($m);
    $o = new $p['filename'];
    if($o instanceof Pix_Table) {
        try {
            $o->dropTable();
        } catch (Pix_Table_Exception $e) {
            echo 'Unable to drop table "'.$p['filename'].'". ';
            if (preg_match('/^Table: \w+SQL Error: \(1051\)Unknown table/',
                $e->getMessage())) {
                echo 'Table missing. Ignored.'."\n";
            } else {
                echo "\n";
                die($e->getMessage());
            }
        }
        $o->createTable();
    }
}
