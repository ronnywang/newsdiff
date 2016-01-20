<?php

class Crawler_TVBS
{
    public static function crawl($insert_limit)
    {
        $urls = array(
            'http://news.tvbs.com.tw/opencms/system/modules/com.thesys.project.tvbs/pages/scheduler/ranking-news-daily.jsp',
            'http://news.tvbs.com.tw/opencms/system/modules/com.thesys.project.tvbs/pages/scheduler/ranking-news-weekly.jsp',
            'http://news.tvbs.com.tw/opencms/system/modules/com.thesys.project.tvbs/pages/scheduler/ranking-news-choice.jsp',
            'http://news.tvbs.com.tw/opencms/system/modules/com.thesys.project.tvbs/pages/scheduler/ranking-forum.jsp',
            'http://news.tvbs.com.tw/opencms/system/modules/com.thesys.project.tvbs/pages/scheduler/ranking-video.jsp',
            'http://news.tvbs.com.tw/opencms/system/modules/com.thesys.project.tvbs/pages/news/ajax-news-time-list.jsp?dataFolder=%2Fnews%2F&date=' . date('Y-m-d', time() - 3600),
        );
        foreach (array('photos', 'politics', 'local', 'money', 'life', 'sports', 'entertainment', 'china', 'world', 'tech', 'travel', 'fun') as $type) {
            $urls[] = 'http://news.tvbs.com.tw/' . $type;
        }

        $content = '';
        foreach ($urls as $url) {
            $content .= Crawler::getBody($url);
        }
        preg_match_all('#href="(/opencms/news/.*/news-[0-9]*)/"]*#', $content, $matches);
        $links = array_unique($matches[1]);
        $insert = $update = 0;
        foreach ($links as $link) {
            $update ++;
            $link = 'http://news.tvbs.com.tw' . $link;
            $insert += News::addNews($link, 9);
            if ($insert_limit <= $insert) {
                break;
            }
        }
        return array($update, $insert);
    }

    public static function parse($body)
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $body = str_replace('<meta charest="utf-8">', '<meta charest="utf-8"><meta http-equiv="Content-Type" content="text/html; charset=utf-8">', $body);

        @$doc->loadHTML($body);
        $ret = new StdClass;
        if ($article_dom = $doc->getElementsByTagName('article')->item(0)) {
            $ret->title = trim($article_dom->getElementsByTagName('h1')->item(0)->nodeValue);
            $ret->body = Crawler::getTextFromDom($doc->getElementById('news_contents'));
        }

        if (!$ret->title) {
            foreach ($doc->getElementsByTagName('div') as $div_dom) {
                if ($div_dom->getAttribute('class') == 'reandrBox') {
                    if ($div_dom->getElementsByTagName('h2')->length == 1) {
                        $ret->title = $div_dom->getElementsByTagName('h2')->item(0)->nodeValue;
                    }
                    $ret->body = '';
                    foreach (array('Update_time', 'textContent') as $class) {
                        foreach ($div_dom->getElementsByTagName('div') as $sub_div_dom) {
                            if (in_array($class, explode(' ', $sub_div_dom->getAttribute('class')))) {
                                $ret->body .= trim(Crawler::getTextFromDom($sub_div_dom)) . "\n";
                            }
                        }
                    }
                    $ret->body = trim($ret->body);
                    break;
                }
            }
        }
        return $ret;
    }
}
