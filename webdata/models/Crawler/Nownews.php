<?php

class Crawler_Nownews
{
    public static function crawl()
    {
        $content = Crawler::getBody('http://www.nownews.com');
        $content .= Crawler::getBody('http://feeds.feedburner.com/nownews/realtime');

        preg_match_all('#http://www\.nownews\.com\/\d\d\d\d/\d\d/\d\d/\d+-\d+\.htm#', $content, $matches);
        foreach ($matches[0] as $link) {
            try {
                News::insert(array(
                    'url' => $link,
                    'url_crc32' => crc32($link),
                    'created_at' => time(),
                    'last_fetch_at' => 0,
                ));
            } catch (Pix_Table_DuplicateException $e) {
            }
        }

    }
}
