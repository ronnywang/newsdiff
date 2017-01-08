<?php

class Crawler_TheNewsLens
{
    public static function crawl($insert_limit)
    {
        $content = Crawler::getBody('https://www.thenewslens.com/');

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
        $ret->title = trim($sections->item(0)->getElementsByTagName('h1')->item(0)->nodeValue);
        foreach ($sections->item(1)->getElementsByTagName('div') as $div_dom) {
            if ($div_dom->getAttribute('class') == 'article-content') {
                $ret->body = Crawler::getTextFromDom($div_dom);
                break;
            }
        }

        if (!$ret->body) {
            throw new Exception('無法正常解析');
        }
        return $ret;
    }
}
