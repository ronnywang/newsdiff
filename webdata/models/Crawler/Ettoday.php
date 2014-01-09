<?php

class Crawler_Ettoday
{
    public static function crawl($insert_limit)
    {
        // http://www.ettoday.net/news/20130813/255848.htm
        $content = Crawler::getBody('http://www.ettoday.net');
        $content .= Crawler::getBody('http://feeds.feedburner.com/ettoday/realtime');

        preg_match_all('#/news/\d+/\d+\.htm#', $content, $matches);
        $insert = $update = 0;
        foreach ($matches[0] as $link) {
            $update = 0;
            $url = Crawler::standardURL('http://www.ettoday.net' . $link);
            $insert += News::addNews($url, 4);
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
        foreach ($doc->getElementsByTagName('h2') as $h2_dom) {
            if ($h2_dom->getAttribute('itemprop') == 'headline') {
                $ret->title = trim($h2_dom->nodeValue);
                break;
            }
        }
        foreach ($doc->getElementsByTagName('sectione') as $sectione_dom) {
            if ($sectione_dom->getAttribute('itemprop') == 'articleBody') {
                $ret->body = '';
                foreach ($sectione_dom->childNodes as $node) {
                    if ($node->nodeType == XML_ELEMENT_NODE and $node->getAttribute('class') == 'test-keyword') {
                        break;
                    }
                    $ret->body .= Crawler::getTextFromDom($node);
                }
                break;
            }
        }

        return $ret;
    }
}
