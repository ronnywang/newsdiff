<?php

class Crawler_TTV implements Crawler_Common
{
    public static function crawlIndex()
    {
        return Crawler::getBody('http://www.ttv.com.tw/news/');
    }

    public static function findLinksIn($content)
    {
        preg_match_all('#/[0-9]+/[0-9]+/[0-9]+/[0-9]+[0-9A-Z]\.htm#', $content, $matches);
        array_walk($matches[0], function(&$link) { $link = 'http://www.ttv.com.tw' . $link; });
       return array_unique($matches[0]);
    }

    public static function parse($body)
    {
        $ret = new StdClass;

        $doc = new DOMDocument('1.0', 'UTF-8');
        $body = str_replace('text/html; charset=big5', 'text/html; charset=utf-8', $body);

        if (FALSE !== strpos($body, '<title>錯誤代碼：404</title>')) {
            $ret->title = $ret->body = 404;
            return $ret;
        }
        @$doc->loadHTML($body);
        if (!$click_safeguard_dom = $doc->getElementById('iCliCK_SafeGuard')) {
            return null;
        }
        if (!$clickBody_start_dom = $doc->getElementById('iclickAdBody_Start')) {
            return null;
        }
        $ret->title = str_replace("\n", "", trim($click_safeguard_dom->nodeValue));
        $ret->body = '';
        $dom = $clickBody_start_dom;
        while ($dom = $dom->nextSibling) {
            if ($dom->nodeType == XML_ELEMENT_NODE and $dom->getAttribute('id') == 'iclickAdBody_End') {
                break;
            }
            $ret->body .= Crawler::getTextFromDom($dom);
        }
        return $ret;
    }
}
