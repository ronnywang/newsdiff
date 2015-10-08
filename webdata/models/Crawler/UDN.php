<?php

class Crawler_UDN
{
    public static function crawl($insert_limit)
    {
        for ($i = 1; $i <10; $i ++) {
            $content .= Crawler::getBody("http://udn.com/rssfeed/news/1/{$i}?ch=news");
        }
        preg_match_all('#http://udn.com/news/story/[0-9]*/[0-9]*#', $content, $matches);
        foreach ($matches[0] as $link) {
            $update ++;
            $insert += News::addNews($link, 8);
            if ($insert_limit <= $insert) {
                break;
            }
        }
        return array($update, $insert);
    }

    public static function parse($body)
    {
        $ret = new StdClass;
        if (false !== strpos($body, '<link rel="canonical" href="http://udn.com/news/e404"/>')) {
            $ret->title = $ret->body = 404;
            return $ret;
        } elseif (false !== strpos($body, '<link rel="canonical" href="20150422_e404"/>')) {
            $ret->title = $ret->body = 404;
            return $ret;
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        @$doc->loadHTML($body);
        $ret->title = trim($doc->getElementById('story_art_title')->nodeValue);
        $ret->body = trim($doc->getElementById('story_body_content')->nodeValue);

        if (!$ret->body) {
            throw new Exception('not found');
        }
        return $ret;
    }
}
