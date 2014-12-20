<?php

class Crawler_Ettoday implements Crawler_Common
{
    public static function crawlIndex()
    {
        // http://www.ettoday.net/news/20130813/255848.htm
        $content = Crawler::getBody('http://www.ettoday.net');
        $content .= Crawler::getBody('http://feeds.feedburner.com/ettoday/realtime');
        return $content;
    }

    public static function findLinksIn($content)
    {
        preg_match_all('#/news/\d+/\d+\.htm#', $content, $matches);
        array_walk($matches[0], function(&$link) { $link = 'http://www.ettoday.net' . $link; });
        return array_unique($matches[0]);
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
