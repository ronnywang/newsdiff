<?php

class Crawler_Nownews
{
    public static function crawl($insert_limit)
    {
        $content = Crawler::getBody('https://www.nownews.com');

        preg_match_all('#href="(\/news/\d\d\d\d\d\d\d\d/\d+)"#', $content, $matches);
        $insert = $update = 0;
        foreach ($matches[1] as $link) {
            $link = Crawler::standardURL('https://www.nownews.com' . $link);
            $update ++;
            $insert += News::addNews($link, 7);
            if ($insert_limit <= $insert) {
                break;
            }
        }
        return array($update, $insert);
    }
    public static function parse($body)
    {
        $doc = new DOMDocument;
        @$doc->loadHTML($body);

        if (strpos($body, '找不到網頁或內容，回')) {
            $ret->title = $ret->body = 404;
            return $ret;
        }

        if (!$h1_dom = $doc->getElementsByTagName('h1')->item(0)) {
            $ret->title = $ret->body = '無法判斷的內容';
            return $ret;
        }

        $ret->title = trim($h1_dom->nodeValue);
        $ret->body = '';

        // 作者資訊
        $found = false;
        foreach ($doc->getElementsByTagName('span') as $span_dom) {
            if (strpos($span_dom->getAttribute('class'), 'author_') === 0) {
                $ret->body = trim($ret->body) . "\n" . trim($span_dom->nodeValue);
                $found = true;
                break;
            }
        }
        if (!$found) {
            $ret->title = $ret->body = '無法判斷的內容';
            return $ret;
        }

        // 時間
        $found = false;
        foreach ($doc->getElementsByTagName('img') as $img_dom) {
            if (strpos($img_dom->getAttribute('class'), 'timeIcon_') === 0) {
                $ret->body = trim($ret->body) . "\n" . trim($img_dom->nextSibling->nodeValue);
                $found = true;
                break;
            }
        }
        if (!$found) {
            $ret->title = $ret->body = '無法判斷的內容';
            return $ret;
        }

        // 內文
        if ($doc->getElementsByTagName('article')->length != 1) {
            $ret->title = $ret->body = '無法判斷的內容';
            return $ret;
        }
        $ret->body = trim($ret->body) . "\n" . Crawler::getTextFromDom($doc->getElementsByTagName('article')->item(0));

        return $ret;
    }
}
