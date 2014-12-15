<?php

class Crawler_PTS implements Crawler_Common
{
    public static function crawlIndex()
    {
        return Crawler::getBody('http://news.pts.org.tw/top_news.php');
    }

    public static function findLinksIn($content)
    {
        preg_match_all('#detail\.php\?NEENO=[0-9]*#', $content, $matches);
        array_walk($matches[0], function(&$link) { $link = 'http://news.pts.org.tw/' . $link; });
        return array_unique($matches[0]);
    }

    public static function parse($body)
    {
        $doc = new DOMDocument('1.0', 'UTF-8');

        @$doc->loadHTML($body);
        $ret = new StdClass;
        foreach ($doc->getElementsByTagName('meta') as $meta_dom) {
            if ('og:title' == $meta_dom->getAttribute('property')) {
                $ret->title = preg_replace('#-公視新聞網$#', '', $meta_dom->getAttribute('content'));
            }
        }

        $ret->body = '';
        foreach ($doc->getElementsByTagName('p') as $p_dom) {
            if ($p_dom->getAttribute('class') == 'Page') {
                $ret->body .= Crawler::getTextFromDom($p_dom);
            }
        }
        if ($ret->title and $ret->body) {
            return $ret;
        }

        return null;
    }
}
