<?php

class Crawler_UDN
{
    public static function crawl($insert_limit)
    {
        $content = Crawler::getBody('http://udn.com/NEWS/hierArrays.js');
        preg_match_all('#http://udn.com/NEWS/[^\.]*\.js#', $content, $matches);
        $jslinks = $matches[0];
        $insert = $update = 0;
        foreach ($jslinks as $jslink) {
            $content = Crawler::getBody($jslink);
            preg_match_all('#http://udn.com/NEWS/[^/"\']*/[^/"\']*/[0-9]*\.shtml#', $content, $matches);
            foreach ($matches[0] as $link) {
                $update ++;
                $insert += News::addNews($link, 8);
                if ($insert_limit <= $insert) {
                    break;
                }
            }
        }
        return array($update, $insert);
    }

    public static function parse($body)
    {
        $body = str_replace('<meta content="text/html; charset=big5" http-equiv="Content-Type">', '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">', $body);
        $body = str_replace('<meta http-equiv="Content-Type" content="text/html; charset=big5">', '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">', $body);

        $doc = new DOMDocument('1.0', 'UTF-8');
        @$doc->loadHTML($body);
        $ret = new StdClass;
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
