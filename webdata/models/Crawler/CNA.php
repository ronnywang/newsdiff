<?php

class Crawler_CNA
{
    public static function crawl($insert_limit)
    {
        // http://www.cna.com.tw/News/aCN/201308130087-1.aspx
        // http://www.cna.com.tw/Topic/Popular/3907-1/201308130021-1.aspx
        $content = Crawler::getBody('http://www.cna.com.tw/');
        for ($i = 1; $i < 10; $i ++) {
            $content .= Crawler::getBody('http://www.cna.com.tw/list/aall-' . $i . '.aspx');
        }

        preg_match_all('#/(News|Topic/Popular)/[^/]*/\d+-\d+\.aspx#i', $content, $matches);
        $insert = $update = 0;
        foreach ($matches[0] as $link) {
            $update ++;
            $url = Crawler::standardURL('http://www.cna.com.tw' . $link);
            $insert += News::addNews($url, 3);
            if ($insert_limit <= $insert) {
                break;
            }
        }
        return array($update, $insert);

    }

    public static function parse($body)
    {
        if (preg_match('/<title>404<\/title>/', $body)) {
            $ret = new StdClass;
            $ret->title = '404';
            $ret->body = '404';
            return $ret;
        }
        $doc = new DOMDocument('1.0', 'UTF-8');
        @$doc->loadHTML($body);
        $ret = new StdClass;

        foreach ($doc->getElementsByTagName('div') as $div_dom) {
            if ($div_dom->getAttribute('class') == 'news_content') {
            } elseif ($div_dom->getAttribute('class') == 'news_content_new'){
            } else {
                continue;
            }

            if ($div_dom->getElementsByTagName('h1')->item(0)) {
                $ret->title = $div_dom->getElementsByTagName('h1')->item(0)->nodeValue;
            } else {
                $ret->title = $div_dom->getElementsByTagName('h2')->item(0)->nodeValue;
            }
            foreach ($div_dom->getElementsByTagName('div') as $child_div_dom) {
                if ($child_div_dom->getAttribute('class') == 'box_2') {
                    $ret->body = '';
                    foreach ($child_div_dom->getElementsByTagName('p')->item(0)->childNodes as $childNode) {
                        if (trim($childNode->nodeValue) == '※你可能還想看：') {
                            break;
                        }
                        $ret->body .= Crawler::getTextFromDom($childNode);
                    }
                    break;
                }
            }
            break;
        }
        $ret->title = trim($ret->title);
        $ret->body = trim($ret->body);

        return $ret;
    }
}
