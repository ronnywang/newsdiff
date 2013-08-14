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
            try {
                $url = Crawler::standardURL('http://www.appledaily.com.tw' . $link);
                News::insert(array(
                    'url' => $url,
                    'url_crc32' => crc32($url),
                    'created_at' => time(),
                    'last_fetch_at' => 0,
                ));
            } catch (Pix_Table_DuplicateException $e) {
            }
        }

    }

    public static function parse($body)
    {
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
