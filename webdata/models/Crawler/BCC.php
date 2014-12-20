<?php

class Crawler_BCC implements Crawler_Common
{
    public static function crawlIndex()
    {
        return Crawler::getBody('http://www.bcc.com.tw/news');
    }

    public static function findLinksIn($content)
    {
        preg_match_all('#newsView\.[0-9A-Z-z]*#', $content, $matches);
        $links = array_unique($matches[0]);
        array_walk($matches[0], function(&$link) { $link = 'http://www.bcc.com.tw/'.$link; });
        return array_unique($matches[0]);
    }

    public static function parse($body)
    {
        $ret = new StdClass;
        if (preg_match('#目前無相關新聞$#', trim($body))) {
            $ret = new StdClass;
            $ret->title = 404;
            $ret->body = 404;
            return $ret;
        }
        $ret->title = null;

        $doc = new DOMDocument('1.0', 'UTF-8');
        @$doc->loadHTML($body);
        foreach ($doc->getElementsByTagName('div') as $div_dom) {
            if ($div_dom->getAttribute('class') == 'tt26') {
                $ret->title = $div_dom->nodeValue;
                break;
            }
        }
        if (is_null($ret->title)) {
            return null;
        }
        if (!$clickBody_start_dom = $doc->getElementById('iclickAdBody_Start')) {
            return null;
        }
        $ret->body = '';
        $dom = $clickBody_start_dom;
        while ($dom = $dom->nextSibling) {
            if ($dom->nodeType == XML_ELEMENT_NODE and $dom->getAttribute('id') == 'iclickAdBody_End') {
                break;
            }
            $ret->body .= Crawler::getTextFromDom($dom);
        }
        $ret->body = trim($ret->body);
        return $ret;
    }
}
