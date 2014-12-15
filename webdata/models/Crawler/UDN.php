<?php

class Crawler_UDN implements Crawler_Common
{
    public static function crawlIndex()
    {
        $indexJs = Crawler::getBody('http://udn.com/NEWS/hierArrays.js');
        preg_match_all('#http://udn.com/NEWS/[^\.]*\.js#', $indexJs, $matches);
        $content = '';
        foreach ($matches[0] as $jslink) {
            $content .= Crawler::getBody($jslink);
        }
        return $content;
    }

    public static function findLinksIn($content)
    {
        preg_match_all('#http://udn.com/NEWS/[^/"\']*/[^/"\']*/[0-9]*\.shtml#', $content, $matches);
       return array_unique($matches[0]);
    }

    public static function parse($body)
    {
        $body = str_replace('<meta content="text/html; charset=big5" http-equiv="Content-Type">', '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">', $body);
        $body = str_replace('<meta http-equiv="Content-Type" content="text/html; charset=big5">', '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">', $body);

        $ret = new StdClass;
        if (false !== strpos($body, '<img src="/NEWS/404.gif')) {
            $ret->title = $ret->body = 404;
            return $ret;
        }

        if (false !== strpos($body, 'window.location.href="http://udn.com/NEWS/404.shtml"')) {
            $ret->title = $ret->body = 404;
            return $ret;
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        @$doc->loadHTML($body);
        $ret->title = trim($doc->getElementById('story_title')->nodeValue);
        if ($doc->getElementById('story')) {
            $ret->body = trim($doc->getElementById('story_author')->nodeValue . "\n"
                . $doc->getElementById('story_update')->nodeValue . "\n"
                . Crawler::getTextFromDom($doc->getElementById('story'))
            );
        }

        if (!$ret->title and !$ret->body) {
            if (preg_match('#<script language=javascript>window.location.href="([^"]*)";</script>#', $body, $matches)) {
                $ret->title = trim($doc->getElementsByTagName('title')->item(0)->nodeValue);
                $ret->body = '重新導向至: ' . $matches[1];
            }
        }

        if (!$ret->title and !$ret->body) {
            if (preg_match('#<meta http-equiv="refresh" content="0;URL=([^"]*)">#', $body, $matches)) {
                $ret->title = trim($doc->getElementsByTagName('title')->item(0)->nodeValue);
                $ret->body = '重新導向至: ' . $matches[1];
            }
        }
        return $ret;
    }
}
