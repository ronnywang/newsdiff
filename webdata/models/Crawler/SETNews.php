<?php

class Crawler_SETNews
{
    public static function crawl($insert_limit)
    {
        $content = Crawler::getBody('http://www.setn.com/Default.aspx');
        preg_match_all('#NewsID=[0-9]*#', $content, $matches);
        $links = array_unique($matches[0]);
        $insert = $update = 0;
        foreach ($links as $link) {
            $update ++;
            $link = 'http://www.setn.com/News.aspx?' . $link;
            $insert += News::addNews($link, 15);
            if ($insert_limit <= $insert) {
                break;
            }
        }
        return array($update, $insert);
    }

    public static function parse($body)
    {
        $doc = new DOMDocument('1.0', 'UTF-8');

        @$doc->loadHTML($body);
        $ret = new StdClass;
        if (!$h1_dom = $doc->getElementsByTagName('h1')->item(0)) {
            return null;
        }
        $ret->title = trim($h1_dom->nodeValue);
        $ret->body = Crawler::getTextFromDom($doc->getElementById('Content1'));
        return $ret;
    }
}
