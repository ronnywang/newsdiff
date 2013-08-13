<?php

class Crawler_Appledaily
{
    public static function crawl()
    {
        $content = Crawler::getBody('http://www.appledaily.com.tw');
        $content .= Crawler::getBody('http://www.appledaily.com.tw/appledaily/todayapple');
        $content .= Crawler::getBody('http://www.appledaily.com.tw/appledaily/article/headline');
        $content .= Crawler::getBody('http://ent.appledaily.com.tw/');
        $content .= Crawler::getBody('http://www.appledaily.com.tw/appledaily/article/international');
        $content .= Crawler::getBody('http://www.appledaily.com.tw/appledaily/article/sports');
        $content .= Crawler::getBody('http://www.appledaily.com.tw/appledaily/article/supplement');
        $content .= Crawler::getBody('http://www.appledaily.com.tw/appledaily/article/finance');
        $content .= Crawler::getBody('http://www.appledaily.com.tw/appledaily/article/property');
        $content .= Crawler::getBody('http://www.appledaily.com.tw/appledaily/article/forum');

        preg_match_all('#/(appledaily|realtimenews)/article/[^/]*/\d+/[^"]+#', $content, $matches);
        foreach ($matches[0] as $link) {
            try {
                $url = 'http://www.appledaily.com.tw' . $link;
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
