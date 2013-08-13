<?php

class Crawler_CNA
{
    public static function crawl()
    {
        // http://www.cna.com.tw/News/aCN/201308130087-1.aspx
        // http://www.cna.com.tw/Topic/Popular/3907-1/201308130021-1.aspx
        $content = Crawler::getBody('http://www.cna.com.tw/');

        preg_match_all('#/(News|Topic/Popular)/[^/]*/\d+-\d+\.aspx#', $content, $matches);
        foreach ($matches[0] as $link) {
            try {
                $url = 'http://www.cna.com.tw/' . $link;
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
