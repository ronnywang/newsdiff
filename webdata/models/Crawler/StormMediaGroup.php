<?php

class Crawler_StormMediaGroup
{
    public static function crawl($insert_limit)
    {
        $urls = array();
        for ($i=1; $i<=4; $i++) {
            $urls[] = 'http://www.storm.mg/articles/' . $i;
        }

        $content = '';
        foreach ($urls as $url) {
            try {
                $content .= Crawler::getBody($url);
            } catch (Exception $e) {
                error_log("StormMediaGroup {$url} failed: {$e->getMessage()}");
            }
        }

        preg_match_all('#href="(/article/[0-9]*)"#', $content, $matches);
        $insert = $update = 0;
        foreach ($matches[1] as $link) {
            $url = Crawler::standardURL("http://www.storm.mg{$link}");
            $update ++;
            $insert += News::addNews($url, 16);
            if ($insert_limit <= $insert) {
                break;
            }
        }

        return array($update, $insert);
    }

    public static function parse($body)
    {
        $ret = new StdClass;
        $doc = new DOMDocument('1.0', 'UTF-8');
        $body = str_ireplace('<meta charest="utf-8">', '<meta charest="utf-8"><meta http-equiv="Content-Type" content="text/html; charset=utf-8">', $body);
        $body = str_replace('<html lang="zh-TW">', '<html lang="zh-TW"><meta http-equiv="Content-Type" content="text/html; charset=utf-8">', $body);

           
        @$doc->loadHTML($body);

        $ret->title = trim($doc->getElementById('article_title')->nodeValue);
        $ret->body = '';
        foreach ($doc->getElementsByTagName('article')->item(0)->childNodes as $node) {
            if ($node->nodeName == 'div' and $node->getAttribute('class') == 'clear-float') {
                break;
            }
            $ret->body = trim($ret->body . "\n" . Crawler::getTextFromDom($node));
        }

        return $ret;
    }
}
