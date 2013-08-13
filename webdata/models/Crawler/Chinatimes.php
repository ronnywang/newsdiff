<?php

class Crawler_Chinatimes
{
    public static function crawl()
    {
        $content = Crawler::getBody('http://www.chinatimes.com');
        $content .= Crawler::getBody('http://www.chinatimes.com/rss/focus.xml');

        preg_match_all('#/(newspapers|realtimenews)/[^"]*-\d+-\d+#', $content, $matches);
        foreach ($matches[0] as $link) {
            try {
                $url = 'http://www.chinatimes.com' . $link;
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
