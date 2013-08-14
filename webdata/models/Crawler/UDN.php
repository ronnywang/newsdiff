<?php

class Crawler_UDN
{
    public static function crawl()
    {
        $content = Crawler::getBody('http://udn.com/NEWS/hierArrays.js');
        preg_match_all('#http://udn.com/NEWS/[^\.]*\.js#', $content, $matches);
        $jslinks = $matches[0];
        foreach ($jslinks as $jslink) {
            $content = Crawler::getBody($jslink);
            preg_match_all('#http://udn.com/NEWS/[^/"\']*/[^/"\']*/[0-9]*\.shtml#', $content, $matches);
            foreach ($matches[0] as $link) {
                News::addNews($link, 8);
            }
        }
    }
}
