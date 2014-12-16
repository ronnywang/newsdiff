<?php

class Crawler_PTS implements Crawler_Common
{
    public static function crawlIndex()
    {
        return Crawler::getBody('http://news.pts.org.tw/top_news.php');
    }

    public static function findLinksIn($content)
    {
        preg_match_all('#detail\.php\?NEENO=[0-9]*#', $content, $matches);
        array_walk($matches[0], function(&$link) { $link = 'http://news.pts.org.tw/' . $link; });
        return array_unique($matches[0]);
    }

    public static function parse($body)
    {
        // add \n after end of paragraphs, ease to separate paragrahs later
        $body = preg_replace('/(\<br\>|\<br[ ]*\/\>|\<\/p\>|\<\/div\>)/', "$1\n", $body);

        $doc = new DOMDocument('1.0', 'UTF-8');
        @$doc->loadHTML($body);
        $ret = new StdClass;

        // read content
        $content = '';
        $finder = new DomXPath($doc);

        // find title
        $itemList = $finder->query("//td[@class='News_page_tittle']/table/tr/table/td");
        if ($itemList->length == 0) {
            return NULL;
        }
        $ret->title = trim($itemList->item(0)->nodeValue);

        // find body
        $content = '';
        $itemList = $finder->query("//p[@class='Page']");
        foreach ($itemList as $item) {
            $content .= $item->nodeValue;
        }
        $ret->body = $content;
        $ret->body = preg_replace('/[\n\r\t ]*(\n|\r)[\n\r\t ]*/', "\n\n", $ret->body); // fix line breaks
        $ret->body = trim($ret->body);
        if (empty($ret->body)) {
            return NULL;
        }

        return $ret;
    }
}
