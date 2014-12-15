<?php

class Crawler_CTS implements Crawler_Common
{
    public static function crawlIndex()
    {
        $content = Crawler::getBody('http://news.cts.com.tw/real');
        $content .= Crawler::getBody('http://news.cts.com.tw/real/index2.html');
        $content .= Crawler::getBody('http://news.cts.com.tw/real/index3.html');
        $content .= Crawler::getBody('http://news.cts.com.tw/real/index4.html');
        $content .= Crawler::getBody('http://news.cts.com.tw/real/index5.html');
        $content .= Crawler::getBody('http://news.cts.com.tw/real/index6.html');
        return $content;
    }

    public static function findLinksIn($content)
    {
        preg_match_all('#[a-z]*/[a-z]*/[0-9]*/[0-9]*\.html#', $content, $matches);
        array_walk($matches[0], function(&$link) { $link = 'http://news.cts.com.tw/' . $link; });
        return array_unique($matches[0]);
    }

    public static function parse($body)
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        @$doc->loadHTML($body);
        $ret = new StdClass;
        if (false !== strpos($body, '很抱歉，您所輸入的網址已過期或不存在，目前無法提供瀏覽 ')) {
            $ret->title = $ret->body = 404;
            return $ret;
        }
        $ret->title = null;
        foreach ($doc->getElementsByTagName('meta') as $meta_dom) {
            if ('title' == $meta_dom->getAttribute('name')) {
                $ret->title = $meta_dom->getAttribute('content');
                break;
            }
        }
        if (is_null($ret->title)) {
            if (!$title_dom = $doc->getElementsByTagName('h1')->item(0)) {
                return null;
            }
            if (!$doc->getElementById('ctscontent')) {
                return null;
            }
            $ret->title = trim($title_dom->nodeValue);
            $ret->body = Crawler::getTextFromDom($doc->getElementById('ctscontent'));
            return $ret;
        }
        $ret->body = Crawler::getTextFromDom($doc->getElementById('article'));
        return $ret;
    }
}
