<?php

class Crawler_Appledaily implements Crawler_Common
{

    public static function crawlIndex()
    {
        $urls = array(
            'http://www.appledaily.com.tw',
            'http://www.appledaily.com.tw/appledaily/todayapple',
            'http://www.appledaily.com.tw/appledaily/article/headline',
            'http://ent.appledaily.com.tw/',
            'http://www.appledaily.com.tw/appledaily/article/international',
            'http://www.appledaily.com.tw/appledaily/article/sports',
            'http://www.appledaily.com.tw/appledaily/article/supplement',
            'http://www.appledaily.com.tw/appledaily/article/finance',
            'http://www.appledaily.com.tw/appledaily/article/property',
            'http://www.appledaily.com.tw/appledaily/article/forum',
        );
        for ($i = 1; $i < 10; $i ++) {
            $urls[] = 'http://www.appledaily.com.tw/realtimenews/section/new/' . $i;
        }

        $content = '';
        foreach ($urls as $url) {
            try {
                $content .= Crawler::getBody($url);
            } catch (Exception $e) {
                error_log("Crawler_Appledaily {$url} failed: {$e->getMessage()}");
            }
        }

        return $content;
    }

    public static function findLinksIn($content)
    {
        preg_match_all('#/(appledaily|realtimenews)/article/[^/]*/\d+/[^"]+#', $content, $matches);
        array_walk($matches[0], function(&$link) { $link = 'http://www.appledaily.com.tw'.$link; });
        return array_unique($matches[0]);
    }

    public static function parse($body)
    {
        if ('<script>alert("該則即時新聞不存在 !");location.href="/";</script>' == trim($body)) {
            $ret = new StdClass;
            $ret->title = $ret->body = 404;
            return $ret;
        }
        if (strpos($body, '<script>alert("查無此新聞 !");location.href="/index"</script>') !== false) {
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

        if ($doc->getElementById('maincontent') and $article_dom = $doc->getElementById('maincontent')->getElementsByTagName('article')->item(0)) {
            // 廣編特輯
            $body_dom = null;
            foreach ($article_dom->getElementsByTagName('div') as $div_dom) {
                if ($div_dom->getAttribute('class') == 'articulum') {
                    $ret->body = trim(Crawler::getTextFromDom($div_dom));
                    break;
                }
            }
        }
        return $ret;
    }

}
