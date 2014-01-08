<?php

include(__DIR__ . '/../init.inc.php');
Pix_Table::$_save_memory = true;
Crawler_Nownews::crawl();
Crawler_Appledaily::crawl();
Crawler_Chinatimes::crawl();
Crawler_Newtalk::crawl();
Crawler_Libertytimes::crawl();
Crawler_CNA::crawl();
Crawler_Ettoday::crawl();
Crawler_UDN::crawl();
Crawler_TVBS::crawl();
Crawler_BCC::crawl();
