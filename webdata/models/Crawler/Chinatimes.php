<?php

class Crawler_Chinatimes
{
    public static function crawl($insert_limit)
    {
        $content = Crawler::getBody('http://www.chinatimes.com');
        $content .= Crawler::getBody('http://www.chinatimes.com/newspapers/'); // 日報精選
        $content .= Crawler::getBody('http://www.chinatimes.com/newspapers/2601'); // 中國時報
        $content .= Crawler::getBody('http://www.chinatimes.com/newspapers/2602'); // 工商時報
        $content .= Crawler::getBody('http://www.chinatimes.com/newspapers/2603'); // 旺報
        $content .= Crawler::getBody('http://www.chinatimes.com/newspapers/260109'); // 時論廣場
        $content .= Crawler::getBody('http://www.chinatimes.com/newspapers/ctw'); // 時周精選
        $content .= Crawler::getBody('http://www.chinatimes.com/rss/focus.xml');
        for ($i = 1; $i < 10; $i ++) {
            $content .= Crawler::getBody('http://www.chinatimes.com/realtimenews/?page=' . $i);
        }

        preg_match_all('#/(newspapers|realtimenews)/([^"\#<]*-)?\d+-\d+["<]?#', $content, $matches);
        $insert = $update = 0;
        foreach (array_unique($matches[0]) as $link) {
            $update ++;
            $url = Crawler::standardURL('http://www.chinatimes.com' . rtrim($link, '"<'));
            $insert += News::addNews($url, 2);
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
        if (!$pagecontainer_dom= Crawler::getDomByNameAndClass($doc, 'div', 'page_container')) {
            return null;
        }
        $header_dom = $pagecontainer_dom->getElementsByTagName('header')->item(0);
        $ret = new StdClass;
        $ret->title = trim($header_dom->getElementsByTagName('h1')->item(0)->nodeValue);

        $content = '';
        if ($article_info_dom = Crawler::getDomByNameAndClass($doc, 'div', 'article_info')) {
            foreach ($article_info_dom->getElementsByTagName('div') as $div_dom) {
                if ($div_dom->getAttribute('class') == 'reporter') {
                    $content .= preg_replace('/[\r\n ]+/', ' ', Crawler::getTextFromDom($div_dom)) . "\n";
                    break;
                }
            }
        }
        $article_dom = $pagecontainer_dom->getElementsByTagName('article')->item(1);

        $body = '';
        foreach ($article_dom->childNodes as $child_node) {
            if ($child_node->nodeName == 'div' and $child_node->getAttribute('id') == 'div-inread-ad') {
                continue;
            } elseif ($child_node->nodeName == '#comment') {
                continue;
            }
            $body .= trim($child_node->nodeValue) . "\n";
        } 
        $content .= trim($body) . "\n";

        if ($dom = Crawler::getDomByNameAndClass($doc, 'div', 'editorthis')) {
            $content .= trim(Crawler::getTextFromDom($dom)) . "\n";
        }
        if ($dom = Crawler::getDomByNameAndClass($doc, 'div', 'a_k')) {
            $content .= trim(Crawler::getTextFromDom($dom)) . "\n";
        }

        $ret->body = trim($content);
        return $ret;
    }
}
