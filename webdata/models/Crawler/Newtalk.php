<?php

class Crawler_Newtalk
{
    public static function crawl($insert_limit)
    {
        $content = Crawler::getBody('http://newtalk.tw');
        $content .= Crawler::getBody('http://newtalk.tw/rss_news.php');
        for ($i = 1; $i >= 14; $i ++) {
            $content .= Crawler::getBody('http://newtalk.tw/rss_news.php?oid=' . $i);
        }

        preg_match_all('#http://newtalk.tw\/news/\d+/\d+/\d+/\d+\.html#', $content, $matches);
        $insert = $update = 0;
        foreach ($matches[0] as $link) {
            $update ++;
            $link = Crawler::standardURL($link);
            $insert += News::addNews($link, 6);
            if ($insert_limit <= $insert) {
                break;
            }
        }
        return array($update, $insert);
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
