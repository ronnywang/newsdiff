<?php

class Crawler_Newtalk
{
    public static function crawl($insert_limit)
    {
        $content = Crawler::getBody('http://newtalk.tw');
        $content .= Crawler::getBody('http://newtalk.tw/rss/all');
        for ($i = 1; $i <= 5; $i ++) {
            $content .= Crawler::getBody('http://newtalk.tw/rss/category/' . $i);
        }

        preg_match_all('#http://newtalk.tw\/news/view/\d+-\d+-\d+/\d+#', $content, $matches);
        $insert = $update = 0;
        foreach ($matches[0] as $link) {
            $update ++;
            $link = Crawler::standardURL($link);
            $insert += News::addNews($link, 6);
            if ($insert_limit <= $insert) {
                break;
            }
        }
        return array($update, $insert);
    }

    public static function parse($body)
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        @$doc->loadHTML($body);
        $ret = new StdClass;
        foreach ($doc->getElementsByTagName('div') as $div_dom) {
            if ($div_dom->getAttribute('class') == 'content_title') {
                $ret->title = $div_dom->nodeValue;
            }
            if ($div_dom->getAttribute('class') == 'content_reporter') {
                $reporter = Crawler::getTextFromDom($div_dom);
            }
        }
        $ret->body = $reporter . "\n" . trim($doc->getElementById('news_content')->nodeValue);

        return $ret;
    }

}
