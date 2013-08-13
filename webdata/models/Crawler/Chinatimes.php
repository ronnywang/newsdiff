<?php

class Crawler_Chinatimes
{
    public static function crawl()
    {
        $content = Crawler::getBody('http://www.chinatimes.com');
        $content .= Crawler::getBody('http://www.chinatimes.com/rss/focus.xml');

        preg_match_all('#/(newspapers|realtimenews)/[^"]*-\d+-\d+#', $content, $matches);
        foreach ($matches[0] as $link) {
            try {
                $url = 'http://www.chinatimes.com' . $link;
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
        $doc = new DOMDocument;
        @$doc->loadHTML($body);
        $article_dom = $doc->getElementsByTagName('article')->item(0);
        $header_dom = $article_dom->getElementsByTagName('header')->item(0);
        $ret = new StdClass;
        $ret->title = trim($header_dom->getElementsByTagName('h1')->item(0)->nodeValue);
        $article_dom = $doc->getElementsByTagName('article')->item(1);

        // 有時候可能會有 div, 有的話就要跳過
        if ($div_pic_dom = $article_dom->getElementsByTagName('div')->item(0)) {
            $dom = $div_pic_dom->nextSibling;
        } else {
            $dom = $article_dom->childNodes->item(0);
        }
        $content = '';
        do {
            $content .= $dom->nodeValue. "\n";
        } while ($dom = $dom->nextSibling);

        $ret->body = trim($content);
        return $ret;
    }
}
