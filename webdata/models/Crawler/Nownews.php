<?php

class Crawler_Nownews
{
    public static function crawl()
    {
        $content = Crawler::getBody('http://www.nownews.com');
        $content .= Crawler::getBody('http://feeds.feedburner.com/nownews/realtime');

        preg_match_all('#http://www\.nownews\.com\/\d\d\d\d/\d\d/\d\d/\d+-\d+\.htm#', $content, $matches);
        foreach ($matches[0] as $link) {
            $link = Crawler::standardURL($link);
            News::addNews($link, 7);
        }

    }
    public static function parse($body)
    {
        $body = str_replace('<meta http-equiv="Content-Type" content="text/html; charset=big5"/>', '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">', $body);
        $doc = new DOMDocument('1.0', 'UTF-8');
        @$doc->loadHTML($body);
        $ret = new StdClass;
        foreach ($doc->getElementsByTagName('h1') as $h1_dom) {
            if ($h1_dom->getAttribute('itemprop') == 'headline') {
                $ret->title = trim($h1_dom->nodeValue);
                break;
            }
        }
        foreach ($doc->getElementsByTagName('div') as $div_dom) {
            if ($div_dom->getAttribute('itemprop') == 'articleBody') {
                $ret->body = trim($div_dom->nodeValue);
                break;
            }
        }

        if (!$ret->title and !$ret->body) { // 可能是星光大道類型
            foreach ($doc->getElementsByTagName('div') as $div_dom) {
                if (in_array($div_dom->getAttribute('class'), array('news_story', 'ws_index_main_story'))) {
                    $ret->title = $div_dom->getElementsByTagName('h1')->item(0)->nodeValue;
                }

                if ($div_dom->getAttribute('class') == 'story_content') {
                    $ret->body .= trim(Crawler::getTextFromDom($div_dom));
                }
            }
        }

        if (!$ret->title and !$ret->body and $div_dom = $doc->getElementById('news_container')) {
            $ret->title = $div_dom->getElementsByTagName('h1')->item(0)->nodeValue;
            foreach ($div_dom->getElementsByTagName('div') as $child_div_dom) {
                if ($child_div_dom->getAttribute('class') == 'news_story') {
                    $ret->body = Crawler::getTextFromDom($child_div_dom);
                }
            }
        }
        return $ret;
    }
}
