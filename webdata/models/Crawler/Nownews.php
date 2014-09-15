<?php

class Crawler_Nownews
{
    public static function crawl($insert_limit)
    {
        $content = Crawler::getBody('http://www.nownews.com');
        $content .= Crawler::getBody('http://feeds.feedburner.com/nownews/realtime');

        preg_match_all('#http://www\.nownews\.com\/n/\d\d\d\d/\d\d/\d\d/\d+#', $content, $matches);
        $insert = $update = 0;
        foreach ($matches[0] as $link) {
            $link = Crawler::standardURL($link);
            $update ++;
            $insert += News::addNews($link, 7);
            if ($insert_limit <= $insert) {
                break;
            }
        }
        return array($update, $insert);
    }
    public static function parse($body)
    {
        $body = mb_convert_encoding($body, 'HTML-ENTITIES', "UTF-8");
        $doc = new DOMDocument('1.0', 'UTF-8');
        // 移除掉關鍵字的部份
        $body = preg_replace('#<p class="bzkeyword">.*?</p>#s', '', $body);
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
                $ret->body = '';
                foreach ($div_dom->childNodes as $childNode) {
                    if ($childNode->nodeType == XML_ELEMENT_NODE and $childNode->nodeName == 'p' and $childNode->getAttribute('class') == 'bzkeyword') {
                        break;
                    }
                    $ret->body .= Crawler::getTextFromDom($childNode);
                }
                $ret->body = trim($ret->body);
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

        if ((!$ret->title or !$ret->body) and $div_dom = $doc->getElementById('report_file_story')) {
            $ret->title = $div_dom->getElementsByTagName('h1')->item(0)->nodeValue;
            foreach ($div_dom->getElementsByTagName('div') as $child_div_dom) {
                if ($child_div_dom->getAttribute('class') == 'story_content') {
                    $ret->body = '';
                    foreach ($child_div_dom->childNodes as $childNode) {
                        if ($childNode->nodeType == XML_ELEMENT_NODE and $childNode->getAttribute('class') == 'operate_0') {
                            continue;
                        }
                        $ret->body .= Crawler::getTextFromDom($childNode);
                    }
                    $ret->body = trim($ret->body);
                }
            }
        }

        $ret->body = preg_replace_callback('#http://[0-9]-ps.googleusercontent.com/([xh])/([^" \n]*).pagespeed.[a-z]*\.[\-_A-Za-z0-9.]*\n?#', function($m) {
            if ($m[1] == 'x') {
                $url = str_replace('www.nownews.com/', '', $m[2]);
            } else {
                $url = $m[2];
            }
            return 'http://' . $url . "\n";

        }, $ret->body);

        $ret->body = preg_replace_callback('#/[^/]*x[_0-9a-z]*\.jpg#i', function($m){
            return preg_replace('#/[^/]*x#', '/', $m[0]);
        }, $ret->body);

        $ret->body = trim($ret->body);
        return $ret;
    }
}
