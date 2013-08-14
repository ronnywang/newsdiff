<?php

class Crawler_Libertytimes
{
    public static function crawl()
    {
        // http://www.libertytimes.com.tw/2013/new/aug/13/today-t3.htm
        // http://iservice.libertytimes.com.tw/liveNews/news.php?no=852779&type=%E7%A4%BE%E6%9C%83

        $categories = array('即時新聞', '政治', '社會', '科技', '國際', '財經', '生活', '體育', '影劇', '趣聞');

        $content = '';
        foreach ($categories as $category) {
            $url = 'http://iservice.libertytimes.com.tw/liveNews/list.php?type=' . urlencode($category);
            $content .= Crawler::getBody($url);
        }
        $url = 'http://iservice.libertytimes.com.tw/liveNews/?Slots=LiveMore';
        $content .= Crawler::getBody($url);

        preg_match_all('#news\.php?[^"]*#', $content, $matches);
        foreach ($matches[0] as $link) {
            $url = Crawler::standardURL('http://iservice.libertytimes.com.tw/liveNews/' . $link);
            News::addNews($url, 5);
        }

        $base = 'http://www.libertytimes.com.tw/' . date('Y') . '/new/' . strtolower(date('M')) . '/' . date('d') . '/';
        $content = Crawler::getBody($base . 'menu2.js');

        preg_match_all('#today-.*\.htm#', $content, $matches);
        foreach ($matches[0] as $link) {
            try {
                $url = $base . $link;
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
        if ("<script>alert('無這則新聞');location='index.php';</script>" == trim($body)) {
            $ret = new StdClass;
            $ret->title = $ret->body = 404;
            return $ret;
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        @$doc->loadHTML($body);
        $ret = new StdClass;
        if (!$doc->getElementById('newsti')){
            error_log($body);
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
