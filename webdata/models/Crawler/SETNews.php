<?php

class Crawler_SETNews implements Crawler_Common
{
    public static function crawlIndex()
    {
        return Crawler::getBody('http://www.setnews.net/');
    }

    public static function findLinksIn($content)
    {
        preg_match_all('#NewsID=[0-9]*#', $content, $matches);
        array_walk($matches[0], function(&$link) { $link = 'http://www.setnews.net/News.aspx?' . $link; });
       return array_unique($matches[0]);
    }

    public static function parse($body)
    {
        $doc = new DOMDocument('1.0', 'UTF-8');

        @$doc->loadHTML($body);
        $ret = new StdClass;
        if (!$h1_dom = $doc->getElementsByTagName('h1')->item(0)) {
            return null;
        }
        $ret->title = trim($h1_dom->nodeValue);
        $ret->body = Crawler::getTextFromDom($doc->getElementById('Content1'));
        return $ret;
    }
}
