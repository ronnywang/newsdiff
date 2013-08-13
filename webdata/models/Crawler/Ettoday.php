<?php

class Crawler_Ettoday
{
    public static function crawl()
    {
        // http://www.ettoday.net/news/20130813/255848.htm
        $content = Crawler::getBody('http://www.ettoday.net');
        $content .= Crawler::getBody('http://feeds.feedburner.com/ettoday/realtime');

        preg_match_all('#/news/\d+/\d+\.htm#', $content, $matches);
        foreach ($matches[0] as $link) {
            try {
                $url = 'http://www.ettoday.net' . $link;
                News::insert(array(
                    'url' => $url,
                    'url_crc32' => crc32($url),
                    'created_at' => time(),
                    'last_fetch_at' => 0,
                ));
            } catch (Pix_Table_DuplicateException $e) {
            }
        }

    }
}
