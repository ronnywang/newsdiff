<?php

class Crawler_FTV
{
    public static function crawl($insert_limit)
    {
        $content = Crawler::getBody('http://news.ftv.com.tw/');
        preg_match_all('#sno=[0-9A-Z]*#', $content, $matches);
        $links = array_unique($matches[0]);
        $insert = $update = 0;
        foreach ($links as $link) {
            $update ++;
            $link = 'http://news.ftv.com.tw/NewsContent.aspx?' . $link;
            $insert += News::addNews($link, 14);
            if ($insert_limit <= $insert) {
                break;
            }
        }
        return array($update, $insert);
    }

    public static function parse($body)
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $body = str_replace('</head>', '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>', $body);

        @$doc->loadHTML($body);
        if (!$h1_dom = $doc->getElementById('h1')) {
            return null;
        }
        if (!$content_dom = $doc->getElementById('newscontent')) {
            return null;
        }
        $ret = new StdClass;
        $ret->title = trim(Crawler::getTextFromDom($doc->getElementById('h1')));
        $ret->body = trim(Crawler::getTextFromDom($doc->getElementById('newscontent')));
        return $ret;
    }
}
