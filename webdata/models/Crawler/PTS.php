<?php

class Crawler_PTS
{
    public static function crawl()
    {
        $content = Crawler::getBody('http://news.pts.org.tw/top_news.php');
        preg_match_all('#detail\.php\?NEENO=[0-9]*#', $content, $matches);
        $links = array_unique($matches[0]);
        foreach ($links as $link) {
            $link = 'http://news.pts.org.tw/' . $link;
            error_log($link);
            News::addNews($link, 11);
        }
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
            if ('og:description' == $meta_dom->getAttribute('property')) {
                $ret->body = $meta_dom->getAttribute('content');
            }
        }

        if ($ret->title and $ret->body) {
            return $ret;
        }

        return null;
    }
}
