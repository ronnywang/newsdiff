<?php

class Crawler_CTS
{
    public static function crawl($insert_limit)
    {
        $content = Crawler::getBody('http://news.cts.com.tw/real/all/');
        preg_match_all('#[a-z]*/[a-z]*/[0-9]*/[0-9]*\.html#', $content, $matches);
        $links = array_unique($matches[0]);
        $insert = $update = 0;
        foreach ($links as $link) {
            $update ++;
            $link = 'http://news.cts.com.tw/' . $link;
            $insert += News::addNews($link, 13);
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
        if (!$title_dom = $doc->getElementsByTagName('h1')->item(0)) {
            return null;
        }
        $ret->title = trim($title_dom->nodeValue);
        $ret->body = Crawler::getTextFromDom($doc->getElementById('ctscontent'));
        return $ret;
    }
}
