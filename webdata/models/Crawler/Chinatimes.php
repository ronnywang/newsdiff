<?php

class Crawler_Chinatimes
{
    public static function crawl($insert_limit)
    {
        $content = Crawler::getBody('https://www.chinatimes.com');
        for ($i = 1; $i < 10; $i ++) {
            $content .= Crawler::getBody('https://www.chinatimes.com/realtimenews/?page=' . $i);
            $content .= Crawler::getBody('https://www.chinatimes.com/newspapers/2601?page=' . $i);
        }

        preg_match_all('#/(newspapers|realtimenews)/([^"\#<]*-)?\d+-\d+["<]?#', $content, $matches);
        $insert = $update = 0;
        foreach (array_unique($matches[0]) as $link) {
            $update ++;
            $url = Crawler::standardURL('https://www.chinatimes.com' . rtrim($link, '"<'));
            $insert += News::addNews($url, 2);
            error_log($url);
            if ($insert_limit <= $insert) {
                break;
            }
        }
        return array($update, $insert);
    }

    public static function parse($body)
    {
        if (preg_match('/抱歉！您所查詢的資料，目前無法找到任何頁面/', $body)) {
            $ret = new StdClass;
            $ret->title = '404';
            $ret->body = '404';
            return $ret;
        }
        $doc = new DOMDocument;
        @$doc->loadHTML($body);
        $ret = new StdClass;
        $ret->title = trim($doc->getElementsByTagName('h1')->item(0)->nodeValue);

        $content = '';
        if ($article_info_dom = Crawler::getDomByNameAndClass($doc, 'header', 'article-header')) {
            foreach ($article_info_dom->getElementsByTagName('div') as $div_dom) {
                if ($div_dom->getAttribute('class') == 'meta-info') {
                    $content .= preg_replace('/[\r\n ]+/', ' ', Crawler::getTextFromDom($div_dom)) . "\n";
                    break;
                }
            }
        }

        $body = '';
        if ($article_dom = Crawler::getDomByNameAndClass($doc, 'div', 'article-body')) {
            foreach ($article_dom->childNodes as $node) {
                $body = trim($body . "\n" . Crawler::getTextFromDom($node)) . "\n";
            }
        } 
        $content .= trim($body) . "\n";

        $ret->body = trim($content);
        return $ret;
    }
}
