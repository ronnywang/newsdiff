<?php

class Crawler_CNA implements Crawler_Common
{
    public static function crawlIndex()
    {
        // http://www.cna.com.tw/News/aCN/201308130087-1.aspx
        // http://www.cna.com.tw/Topic/Popular/3907-1/201308130021-1.aspx
        $content = Crawler::getBody('http://www.cna.com.tw/');
        for ($i = 1; $i < 10; $i ++) {
            $content .= Crawler::getBody('http://www.cna.com.tw/list/aall-' . $i . '.aspx');
        }
        return $content;
    }

    public static function findLinksIn($content)
    {
        preg_match_all('#/(News|Topic/Popular)/[^/]*/\d+-\d+\.aspx#i', $content, $matches);
        array_walk($matches[0], function(&$link) { $link = 'http://www.cna.com.tw' . $link; });
        return array_unique($matches[0]);
    }

    public static function parse($body)
    {
        // add \n after end of paragraphs, ease to separate paragrahs later
        $body = preg_replace('/(\<br\>|\<br[ ]*\/\>|\<\/p\>|\<\/div\>)/', "$1\n", $body);

        $doc = new DOMDocument('1.0', 'UTF-8');
        @$doc->loadHTML($body);
        $ret = new StdClass;

        // get data, if exists
        $item = $doc->getElementById('ctl00_ctl00_cph_container_cph_primary_View2014_PnlCont');
        if ($item === NULL) {
            return NULL;
        }

        // parse title
        $og_title = FALSE;
        foreach ($doc->getElementsByTagName('meta') as $meta) {
            if ($meta->getAttribute('property') == 'og:title') {
                $og_title = $meta->getAttribute('content');
            }
        }
        $ret->title = preg_replace('/^(.+?)[ ]*\|.*$/', '$1', $og_title);

        // parse body
        $ret->body = $item->nodeValue;
        $ret->body = preg_replace('/[\n\r\t ]*(\n|\r)[\n\r\t ]*/', "\n\n", $ret->body); // fix line breaks
        $ret->body = trim($ret->body);
        return $ret;
    }
}
