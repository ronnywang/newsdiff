<?php

// declare unit test mode on to skip mysql connection
define('UNITTEST_MODE', true);

// Bootstrap
require_once __DIR__ . '/../webdata/init.inc.php';
require_once __DIR__ . '/../webdata/stdlibs/simpletest/autorun.php'; // from include path

// Include all tests (*.test) in this folder
$dir = new RecursiveDirectoryIterator(__DIR__);
$iter = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($iter, '/^.+\.test$/i', RecursiveRegexIterator::GET_MATCH);
foreach($files as $file => $info){
    require_once $file;
}
