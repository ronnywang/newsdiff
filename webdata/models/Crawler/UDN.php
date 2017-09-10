<?php

class Crawler_UDN
{
    public static function crawl($insert_limit)
    {
        $rss_content = Crawler::getBody("https://udn.com/rssfeed/lists/2");
        preg_match_all('#"(/rssfeed/news/[^"]*)"#', $rss_content, $matches);
        $content = '';
        $rss_urls = array_unique($matches[1]);
        shuffle($rss_urls);
        foreach ($rss_urls as $url) {
            try {
                $content = Crawler::getBody('https://udn.com' . $url);
            } catch (Exception $e) {
                error_log($url . ' failed');
                continue;
            }
            preg_match_all('#https?://udn.com/news/story/[0-9]*/[0-9]*#', $content, $matches);
            foreach ($matches[0] as $link) {
                $update ++;
                $insert += News::addNews($link, 8);
                if ($insert_limit <= $insert) {
                    break 2;
                }
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
        } elseif (false !== strpos($body, "<meta content='0; url=http://udn.com/news/e404' http-equiv='refresh'>")) {
            $ret->title = $ret->body = 404;
            return $ret;
        } elseif (false !== strpos($body, '<link rel="canonical" href="20150422_e404"/>')) {
            $ret->title = $ret->body = 404;
            return $ret;
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        @$doc->loadHTML($body);
        $ret->title = trim($doc->getElementById('story_art_title')->nodeValue);
        $ret->body = Crawler::getTextFromDOM($doc->getElementById('story_bady_info'));
        $dom = $doc->getElementById('story_bady_info');
        while ($dom = $dom->nextSibling) {
            $ret->body = trim($ret->body) . "\n" . trim(Crawler::getTextFromDOM($dom));
        }

        if (!$ret->body) {
            throw new Exception('not found');
        }
        return $ret;
    }
}
