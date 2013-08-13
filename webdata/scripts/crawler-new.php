<?php

include(__DIR__ . '/../init.inc.php');
Pix_Table::$_save_memory = true;
Crawler_Nownews::crawl();
