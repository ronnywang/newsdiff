<?php

class Crawler_FTV implements Crawler_Common
{
    public static function crawlIndex()
    {
        return Crawler::getBody('http://news.ftv.com.tw/');
    }

    public static function findLinksIn($content)
    {
        preg_match_all('#sno=[0-9A-Z]*#', $content, $matches);
        array_walk($matches[0], function(&$link) { $link = 'http://news.ftv.com.tw/NewsContent.aspx?' . $link; });
        return array_unique($matches[0]);
    }

    public static function parse($body)
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $body = str_replace('<head>', '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">', $body);

        @$doc->loadHTML($body);
        if (!$h1_dom = $doc->getElementById('h1')) {
            return null;
        }
        if (!$content_dom = $doc->getElementById('newscontent')) {
            return null;
        }
        $ret = new StdClass;
        $ret->title = trim(Crawler::getTextFromDom($doc->getElementById('h1')));
        $ret->body = trim(Crawler::getTextFromDom($doc->getElementById('newscontent')));
        return $ret;
    }
}
