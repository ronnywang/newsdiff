<?php

class Crawler_BCC
{
    public static function crawl($insert_limit)
    {
        $content = Crawler::getBody('http://www.bcc.com.tw/news_list.asp?type=總覽');
        preg_match_all('#news_view\.asp\?nid=[0-9]*#', $content, $matches);
        $links = array_unique($matches[0]);
        $insert = $update = 0;
        foreach ($links as $link) {
            $update ++;
            $link = 'http://www.bcc.com.tw/' . $link;
            $insert += News::addNews($link, 10);
            if ($insert_limit <= $insert) {
                break;
            }
        }
        return array($update, $insert);
    }

    public static function parse($body)
    {
        $ret = new StdClass;
        if (preg_match('#目前無相關新聞$#', trim($body))) {
            $ret = new StdClass;
            $ret->title = 404;
            $ret->body = 404;
            return $ret;
        }
        if (!preg_match('#<meta property="og:title" content="([^"]*)" />#', $body, $matches)) {
            return null;
        }
        $ret->title = $matches[1];

        $doc = new DOMDocument('1.0', 'UTF-8');
        @$doc->loadHTML($body);
        if (!$clickBody_start_dom = $doc->getElementById('iclickAdBody_Start')) {
            return null;
        }
        $ret->body = '';
        $dom = $clickBody_start_dom;
        while ($dom = $dom->nextSibling) {
            if ($dom->nodeType == XML_ELEMENT_NODE and $dom->getAttribute('id') == 'iclickAdBody_End') {
                break;
            }
            $ret->body .= Crawler::getTextFromDom($dom);
        }
        return $ret;
    }
}
