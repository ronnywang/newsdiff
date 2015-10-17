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
            $update ++;
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
        $body = str_replace('<meta charset="utf-8">', '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>', $body);

        $doc = new DOMDocument('1.0', 'UTF-8');
        @$doc->loadHTML($body);
        $ret = new StdClass;
        $type = null;
        foreach ($doc->getElementsByTagName('h2') as $h2_dom) {
            if ($h2_dom->getAttribute('itemprop') == 'headline') {
                $ret->title = trim($h2_dom->nodeValue);
                $type = 1;
                break;
            }

            if ($h2_dom->getAttribute('class') == 'title') {
                $ret->title = trim($h2_dom->nodeValue);
                $type = 2;
                break;
            }
        }

        if (is_null($type)) {
            foreach ($doc->getElementsByTagName('h1') as $h1_dom) {
                if ($h1_dom->getAttribute('class') == 'title') {
                    $ret->title = trim($h1_dom->nodeValue);
                    $type = 2;
                    break;
                }
            }
        }

        if ($type == 1) {
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
        } elseif ($type == 2) {
            foreach ($doc->getElementsByTagName('div') as $div_dom) {
                if ($div_dom->getAttribute('class') == 'story') {
                    $ret->body = Crawler::getTextFromDom($div_dom);
                }
            }
        } else {
            throw new Exception('無法正常解析');
        }

        return $ret;
    }
}
