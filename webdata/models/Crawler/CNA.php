<?php

class Crawler_CNA
{
    public static function crawl($insert_limit)
    {
        // http://www.cna.com.tw/News/aCN/201308130087-1.aspx
        // http://www.cna.com.tw/Topic/Popular/3907-1/201308130021-1.aspx
        $content = Crawler::getBody('https://www.cna.com.tw/');
        for ($i = 1; $i < 10; $i ++) {
            $content .= Crawler::getBody('https://www.cna.com.tw/list/aall-' . $i . '.aspx');
        }

        preg_match_all('#/(News|Topic/Popular)/[^/]*/\d+\.aspx#i', $content, $matches);
        $insert = $update = 0;
        foreach ($matches[0] as $link) {
            $update ++;
            $url = Crawler::standardURL('https://www.cna.com.tw' . $link);
            $insert += News::addNews($url, 3);
            if ($insert_limit <= $insert) {
                break;
            }
        }
        return array($update, $insert);

    }

    public static function parse($body, $url)
    {
        if (preg_match('/<title>404<\/title>/', $body)) {
            $ret = new StdClass;
            $ret->title = '404';
            $ret->body = '404';
            return $ret;
        }

        preg_match('#/(\d+-?\d+)\.aspx#', $url, $matches);
        $aid = $matches[1];

        if (!preg_match('#<link href="([^"]*)" rel="canonical" />#', $body, $matches)) {
            $ret = new StdClass;
            $ret->title = $ret->body = '無法判斷的內容';
            return $ret;
        }
        $canonical_url = $matches[1];

        preg_match('#/(\d+-?\d+)\.aspx#', $canonical_url, $matches);
        if ($aid !== $matches[1]) {
            throw new Exception("article id 不同，原始: {$aid}, 抓下來: {$matches[1]}");
        }

        $body = str_replace('<meta charset="utf-8" />', '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">', $body);
            
        $doc = new DOMDocument();
        @$doc->loadHTML($body);
        $ret = new StdClass;

        $ret->title = $doc->getElementsByTagName('h1')->item(0)->nodeValue;
        foreach ($doc->getElementsByTagName('div') as $div_dom) {
            if ($div_dom->getAttribute('class') == 'centralContent') {
                foreach ($div_dom->childNodes as $childnode) {
                    if ($childnode->getAttribute('class') == 'paragraph') {
                        $ret->body = Crawler::getTextFromDOM($childnode);
                        break;
                    }
                }
                break;
            }
        }

        return $ret;
    }
}
