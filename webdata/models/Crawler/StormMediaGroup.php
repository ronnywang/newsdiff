<?php

class Crawler_StormMediaGroup implements Crawler_Common
{
    public static function crawlIndex()
    {
        $urls = array();
        for ($i=1; $i<=4; $i++) {
            $urls[] = "http://www.stormmediagroup.com/opencms/news/news-{$i}/more.html";
        }

        $content = '';
        foreach ($urls as $url) {
            try {
                $content .= Crawler::getBody($url);
            } catch (Exception $e) {
                error_log("StormMediaGroup {$url} failed: {$e->getMessage()}");
            }
        }

        return $content;
    }

    public static function findLinksIn($content)
    {
        preg_match_all('#href="(/opencms/news/detail/.*/?uuid=[0-9a-zA-Z\-]*)#', $content, $matches);
        array_walk($matches[1], function(&$link) { $link = 'http://www.stormmediagroup.com' . $link; });
       return array_unique($matches[1]);
    }

    public static function parse($body)
    {
        $ret = new StdClass;
        $doc = new DOMDocument('1.0', 'UTF-8');
        @$doc->loadHTML($body);
        $divCentercolumn = $doc->getElementById('centercolumn');

        $ret->title = trim($divCentercolumn->getElementsByTagName('h3')->item(0)->nodeValue);
        $ret->body = trim($divCentercolumn->getElementsByTagName('div')->item(9)->nodeValue);

        return $ret;
    }
}
