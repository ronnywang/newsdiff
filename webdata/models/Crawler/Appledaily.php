<?php

class Crawler_Appledaily
{
    public static function crawl()
    {
        $content = Crawler::getBody('http://www.appledaily.com.tw');
        $content .= Crawler::getBody('http://www.appledaily.com.tw/appledaily/todayapple');
        $content .= Crawler::getBody('http://www.appledaily.com.tw/appledaily/article/headline');
        $content .= Crawler::getBody('http://ent.appledaily.com.tw/');
        $content .= Crawler::getBody('http://www.appledaily.com.tw/appledaily/article/international');
        $content .= Crawler::getBody('http://www.appledaily.com.tw/appledaily/article/sports');
        $content .= Crawler::getBody('http://www.appledaily.com.tw/appledaily/article/supplement');
        $content .= Crawler::getBody('http://www.appledaily.com.tw/appledaily/article/finance');
        $content .= Crawler::getBody('http://www.appledaily.com.tw/appledaily/article/property');
        $content .= Crawler::getBody('http://www.appledaily.com.tw/appledaily/article/forum');

        preg_match_all('#/(appledaily|realtimenews)/article/[^/]*/\d+/[^"]+#', $content, $matches);
        foreach ($matches[0] as $link) {
            $url = Crawler::standardURL('http://www.appledaily.com.tw' . $link);
            News::addNews($url, 1);
        }

    }

    public static function parse($body)
    {
        if ('<script>alert("該則即時新聞不存在 !");location.href="/";</script>' == trim($body)) {
            $ret = new StdClass;
            $ret->title = $ret->body = 404;
            return $ret;
        }
        if (strpos($body, '很抱歉，您所嘗試連結的頁面出現錯誤或不存在，請稍後再試，謝謝！') !== false) {
            $ret = new StdClass;
            $ret->title = $ret->body = 404;
            return $ret;
        }
        $body = str_replace('<meta charset="utf-8" />', '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">', $body);
        $doc = new DOMDocument('1.0', 'UTF-8');
        @$doc->loadHTML($body);
        $ret = new StdClass;
        $ret->title = trim($doc->getElementById('h1')->nodeValue);
        $ret->body = '';

        // 廣編特輯
        $body_dom = null;
        foreach ($doc->getElementById('maincontent')->getElementsByTagName('article')->item(0)->getElementsByTagName('div') as $div_dom) {
            if ($div_dom->getAttribute('class') == 'articulum') {
                $ret->body = trim(Crawler::getTextFromDom($div_dom));
                break;
            }
        }
        return $ret;
    }


}
