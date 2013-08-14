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
                $link = Crawler::standardURL($link);
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

    public static function parse($body)
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        @$doc->loadHTML($body);
        $ret = new StdClass;
        foreach ($doc->getElementsByTagName('div') as $div_dom) {
            if ($div_dom->getAttribute('class') == 'cont_main_tit news_cont_area_tit') {
                $ret->title = $div_dom->getElementsByTagName('label')->item(0)->nodeValue;
            }
            if ($div_dom->getAttribute('class') == 'news_ctxt_area_word') {
                $ret->body = trim(Crawler::getTextFromDom($div_dom));
            }
        }

        return $ret;
    }

}
