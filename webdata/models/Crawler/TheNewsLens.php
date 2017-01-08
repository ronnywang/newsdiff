<?php

class Crawler_TheNewsLens
{
    public static function crawl($insert_limit)
    {
        $content = Crawler::getBody('https://www.thenewslens.com/');
        preg_match_all('#(/category/[^"/]*)#', $content, $matches);
        foreach ($matches[0] as $category) {
            $content .= Crawler::getBody('https://www.thenewslens.com' . $category);
        }

        preg_match_all('#https://www.thenewslens.com(/article/[0-9]+)#', $content, $matches);
        $insert = $update = 0;
        foreach ($matches[1] as $link) {
            $update ++;
            $url = Crawler::standardURL('https://www.thenewslens.com' . $link);
            $insert += News::addNews($url, 17);
            if ($insert_limit <= $insert) {
                break;
            }
        }
        return array($update, $insert);

    }

    public static function parse($body, $url)
    {
        $doc = new DOMDocument();
        $body = str_replace('<head>', '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">', $body);
        @$doc->loadHTML($body);

        $sections = $doc->getElementsByTagName('section');
        $ret = new StdClass;
        foreach ($doc->getElementsByTagName('h1') as $h1_dom) {
            if ($h1_dom->getAttribute('class') == 'article-title') {
                $ret->title = trim($h1_dom->nodeValue);
                break;
            }
        }
        foreach ($doc->getElementsByTagName('div') as $div_dom) {
            if ($div_dom->getAttribute('class') == 'article-content') {
                $ret->body = Crawler::getTextFromDom($div_dom);
                break;
            }
        }

        if (!$ret->title or !$ret->body) {
            throw new Exception('無法正常解析');
        }
        return $ret;
    }
}
