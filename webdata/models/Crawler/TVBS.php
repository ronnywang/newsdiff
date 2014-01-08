<?php

class Crawler_TVBS
{
    public static function crawl()
    {
        $content = Crawler::getBody('http://news.tvbs.com.tw/todaynews');
        preg_match_all('#http://news.tvbs.com.tw/entry/[0-9]*#', $content, $matches);
        $links = $matches[0];
        foreach ($links as $link) {
            News::addNews($link, 9);
        }
    }

    public static function parse($body)
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $body = str_replace('<meta charest="utf-8">', '<meta charest="utf-8"><meta http-equiv="Content-Type" content="text/html; charset=utf-8">', $body);

        @$doc->loadHTML($body);
        $ret = new StdClass;
        if (!$article_dom = $doc->getElementsByTagName('article')->item(0)) {
            return null;
        }
        $ret->title = trim($article_dom->getElementsByTagName('h1')->item(0)->nodeValue);
        $ret->body = Crawler::getTextFromDom($doc->getElementById('news_contents'));
        return $ret;
    }
}