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

    public static function parse($body, $url)
    {
        if (preg_match('/<title>404<\/title>/', $body)) {
            $ret = new StdClass;
            $ret->title = '404';
            $ret->body = '404';
            return $ret;
        }

        preg_match('#/(\d+-\d+)\.aspx#', $url, $matches);
        $aid = $matches[1];

        if (!preg_match('#<link href="([^"]*)" rel="canonical" />#', $body, $matches)) {
            $ret = new StdClass;
            $ret->title = $ret->body = '無法判斷的內容';
            return $ret;
        }
        $canonical_url = $matches[1];

        preg_match('#/(\d+-\d+)\.aspx#', $canonical_url, $matches);
        if ($aid !== $matches[1]) {
            throw new Exception("article id 不同，原始: {$aid}, 抓下來: {$matches[1]}");
        }

        if (preg_match('#<!--新聞本文 開始 -->\s+<h1>\s+([^<]*)\s+</h1>.*<!--字級 結束 -->(.*)<!--新聞本文 結束 -->#ms', $body, $matches)) {
            $ret = new StdClass;
            $ret->title = htmlspecialchars_decode($matches[1]);
            $doc = new DOMDocument('1.0', 'UTF-8');
            @$doc->loadHTML('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><boy>' . $matches[2] . '</body></html>');
            $ret->body = trim(Crawler::getTextFromDom($doc));
            return $ret;
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        @$doc->loadHTML($body);
        $ret = new StdClass;

        foreach ($doc->getElementsByTagName('div') as $div_dom) {
            if ($div_dom->getAttribute('class') == 'news_content') {
            } elseif ($div_dom->getAttribute('class') == 'news_content_new'){
            } elseif ($div_dom->getAttribute('class') == 'news_title') {
                $ret->title = $div_dom->getElementsByTagName('h1')->item(0)->nodeValue;
                $ret->body = '';
                $dom = $div_dom;
                while ($dom = $dom->nextSibling) {
                    if ($dom->nodeType == XML_ELEMENT_NODE and $dom->getAttribute('class') == 'update_times') { 
                        $ret->body .= $dom->getElementsByTagName('p')->item(0)->nodeValue . "\n";
                        $ret->body .= $dom->getElementsByTagName('p')->item(1)->nodeValue . "\n";
                        continue;
                    } elseif ($dom->nodeType == XML_ELEMENT_NODE and $dom->getAttribute('class') == 'article_box') { 
                        $ret->body .= Crawler::getTextFromDom($dom);
                        break;
                    } 
                }
                return $ret;
            } else {
                continue;
            }


            if ($div_dom->getElementsByTagName('h1')->item(0)) {
                $ret->title = $div_dom->getElementsByTagName('h1')->item(0)->nodeValue;
            } else {
                $ret->title = $div_dom->getElementsByTagName('h2')->item(0)->nodeValue;
            }
            $ret->body = '';
            foreach ($div_dom->getElementsByTagName('div') as $child_div_dom) {
                if (in_array($child_div_dom->getAttribute('class'), array('box_1', 'box_2'))) {
                    foreach ($child_div_dom->childNodes as $childNode) {
                        if (trim($childNode->nodeValue) == '※你可能還想看：') {
                            break;
                        }
                        $ret->body .= Crawler::getTextFromDom($childNode);
                    }
                }
            }
            break;
        }
        $ret->title = trim($ret->title);
        $ret->body = trim($ret->body);

        return $ret;
    }
}
