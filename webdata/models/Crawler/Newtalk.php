<?php

class Crawler_Newtalk
{
    public static function crawl()
    {
        $content = Crawler::getBody('http://newtalk.tw');
        $content .= Crawler::getBody('http://newtalk.tw/rss_news.php');
        for ($i = 1; $i >= 14; $i ++) {
            $content .= Crawler::getBody('http://newtalk.tw/rss_news.php?oid=' . $i);
        }

        preg_match_all('#http://newtalk.tw\/news/\d+/\d+/\d+/\d+\.html#', $content, $matches);
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
