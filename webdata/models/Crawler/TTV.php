<?php

class Crawler_TTV {

    public static function crawl($insert_limit) {
        $cats = array('A', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'P');
        $content = '';
        foreach ($cats AS $cat) {
            $content .= Crawler::getBody('http://www.ttv.com.tw/news/catlist/' . $cat);
        }
        preg_match_all('#/news/view/default.asp\?i=([0-9a-z]*)([^"]*)#i', $content, $matches);
        $links = array_unique($matches[1]);
        $insert = $update = 0;
        foreach ($links as $link) {
            $update ++;
            $link = 'http://www.ttv.com.tw/news/view/' . $link;
            $insert += News::addNews($link, 12);
            if ($insert_limit <= $insert) {
                break;
            }
        }
        return array($update, $insert);
    }

    public static function parse($body) {
        $ret = new StdClass;

        $doc = new DOMDocument('1.0', 'UTF-8');
        $body = str_replace('<meta charset="utf-8">', '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">', $body);

        @$doc->loadHTML($body);

        $ret->title = trim($doc->getElementsByTagName('h1')->item(0)->nodeValue);

        $blocks = array();
        $doms = $doc->getElementsByTagName('div');
        foreach ($doms AS $dom) {
            if ($dom->getAttribute('class') === 'content') {
                $c = trim(Crawler::getTextFromDom($dom));
                if (strpos($c, '相關新聞：')) {
                    continue;
                }
                $blocks[] = $c;
            }
        }
        $ret->body = implode("\n", $blocks);
        return $ret;
    }

}
