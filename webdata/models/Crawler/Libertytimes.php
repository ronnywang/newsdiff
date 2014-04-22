<?php

class Crawler_Libertytimes
{
    public static function crawl($insert_limit)
    {
        // http://www.libertytimes.com.tw/2013/new/aug/13/today-t3.htm
        // http://iservice.libertytimes.com.tw/liveNews/news.php?no=852779&type=%E7%A4%BE%E6%9C%83
        // http://news.ltn.com.tw/list/BreakingNews 即時新聞

        $categories = array('即時新聞', '政治', '社會', '科技', '國際', '財經', '生活', '體育', '影劇', '趣聞');

        $content = '';
        $url = 'http://news.ltn.com.tw/list/BreakingNews'; // 即時新聞
        $content .= Crawler::getBody($url, 0.5, false);

        $url = 'http://news.ltn.com.tw/newspaper'; // 報紙
        $content .= Crawler::getBody($url, 0.5, false);

        $categories = array('focus', 'politics', 'society', 'local', 'life', 'opinion', 'world', 'business', 'sports', 'entertainment', 'consumer', 'supplement');
        foreach ($categories as $category) {
            $url = 'http://news.ltn.com.tw/section/' . $category;
            $content .= Crawler::getBody($url, 0.5, false);
        }

        preg_match_all('#/news/[a-z]*/[a-z]*/[0-9]*#', $content, $matches);
        $insert = $update = 0;
        foreach ($matches[0] as $link) {
            $update = 0;
            $url = Crawler::standardURL('http://news.ltn.com.tw' . $link);
            $update ++;
            $insert += News::addNews($url, 5);
            if ($insert_limit <= $insert) {
                break;
            }
        }
        return array($update, $insert);
    }

    public static function parse($body)
    {
        if (strpos($body, '<div class="newsbox"><ul><li>網址錯誤</li></ul></div>')) {
            $ret = new StdClass;
            $ret->title = $ret->body = 404;
            return $ret;
        }

        if ("<script>alert('無這則新聞');location='index.php';</script>" == trim($body)) {
            $ret = new StdClass;
            $ret->title = $ret->body = 404;
            return $ret;
        }

        if (strpos($body, '<div class="newsbox"><ul><li>無這則新聞</li></ul></div>')) {
            $ret = new StdClass;
            $ret->title = $ret->body = 404;
            return $ret;
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        @$doc->loadHTML($body);
        $ret = new StdClass;
        if (!$doc->getElementById('newsti')){
            // 新版
            if (strpos($body, '無此則新聞') and $doc->getElementsByTagName('title')->item(0)->nodeValue == '自由時報電子報') {
                $ret = new StdClass;
                $ret->title = $ret->body = 404;
                return $ret;
            }

            if ($doc->getElementById('newstext') and $doc->getElementsByTagName('h1')->length == 1) {
                $body = '';
                foreach ($doc->getElementById('newstext')->childNodes as $child_node) {
                    if ($child_node->nodeName == 'p') {
                        $body = $body . "\n" . $child_node->nodeValue;
                    }
                }
                $title = $doc->getElementsByTagName('h1')->item(0)->nodeValue;
                $ret->title = $title;
                $ret->body = $body;
                return $ret;
            }
            throw new Exception('newsti not found');
        }
        $ret->title = trim($doc->getElementById('newsti')->childNodes->item(0)->nodeValue);

        foreach ($doc->getElementsByTagName('div') as $div_dom) {
            if ($div_dom->getAttribute('class') == 'news_content') {
                $ret->body = trim(Crawler::getTextFromDom($div_dom));
            }
        }

        return $ret;
    }

    public static function parse2($body)
    {
        $body = str_replace('<meta http-equiv="Content-Type" content="text/html; charset=big5" />', '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">', $body);
        $doc = new DOMDocument('1.0', 'UTF-8');
        @$doc->loadHTML($body);
        $ret = new StdClass;
        $ret->title = trim($doc->getElementById('newtitle')->nodeValue);

        foreach ($doc->getElementById('newsContent')->childNodes as $node) {
            if ($node->nodeName == 'span' and $node->getAttribute('id') != 'newtitle') {
                $ret->body = trim(Crawler::getTextFromDom($node));
                break;
            }
        }

        if (!$ret->title) {
            $ret->title = $ret->body = '';
            // http://www.libertytimes.com.tw/2013/new/aug/13/today-o13.htm
            foreach ($doc->getElementById('newsContent')->childNodes as $node) {
                if ($node->nodeName == 'span' and $node->getAttribute('class') == 'insubject1') {
                    $ret->title = $node->nodeValue;
                }
                if ($node->nodeName == 'table') {
                    $ret->body = trim(Crawler::getTextFromDom($node));
                }
            }
        }

        return $ret;
    }
}
