<?php

class Crawler_TVBS
{
    public static function crawl($insert_limit)
    {
        $urls = array();
        foreach (array('photos', 'politics', 'local', 'money', 'life', 'sports', 'entertainment', 'china', 'world', 'tech', 'travel', 'fun') as $type) {
            $urls[] = 'http://news.tvbs.com.tw/' . $type;
        }

        $content = '';
        foreach ($urls as $url) {
            $content .= Crawler::getBody($url);
        }
        preg_match_all('#href=\'/?([a-z]+/[0-9]+)\'#', $content, $matches);
        $links = $matches[1];
        $links = array_unique($matches[1]);
        $insert = $update = 0;
        foreach ($links as $link) {
            $update ++;
            $link = 'http://news.tvbs.com.tw/' . $link;
            $insert += News::addNews($link, 9);
            if ($insert_limit <= $insert) {
                break;
            }
        }
        return array($update, $insert);
    }

    public static function parse($body)
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $body = str_replace('<meta charest="utf-8">', '<meta charest="utf-8"><meta http-equiv="Content-Type" content="text/html; charset=utf-8">', $body);

        @$doc->loadHTML($body);
        $ret = new StdClass;

        $detail_dom = null;
        foreach ($doc->getElementsByTagName('div') as $div_dom) {
            if ($div_dom->getAttribute('class') == 'newsdetail') {
                $detail_dom = $div_dom;
                break;
            }
        }

        if (is_null($detail_dom)) {
            $ret->title = $ret->body = '無法判斷的內容';
            return $ret;
        }

        foreach ($detail_dom->childNodes as $child_node) {
            if ($child_node->nodeName != 'div') {
                continue;
            }

            if ($child_node->getAttribute('class') == 'newsdetail-titel') {
                $ret->title = trim($child_node->nodeValue);
            }

            if (in_array($child_node->getAttribute('class'), array('newsdetail-time', 'newsdetail-peo', 'newsdetail-img', 'newsdetail-content'))) {
                $ret->body = trim($ret->body) . "\n" . trim(Crawler::getTextFromDom($child_node));
            }
        }
        print_r($ret);
        exit;
        return $ret;
    }
}
