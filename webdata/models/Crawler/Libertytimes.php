<?php

class Crawler_Libertytimes
{
    public static function crawl($insert_limit)
    {
        // http://www.libertytimes.com.tw/2013/new/aug/13/today-t3.htm
        // http://iservice.libertytimes.com.tw/liveNews/news.php?no=852779&type=%E7%A4%BE%E6%9C%83
        // http://news.ltn.com.tw/list/BreakingNews 即時新聞

        $categories = array('即時新聞', '政治', '社會', '科技', '國際', '財經', '生活', '體育', '影劇', '趣聞');

        $content = '';
        $url = 'http://news.ltn.com.tw/list/BreakingNews'; // 即時新聞
        $content .= Crawler::getBody($url, 0.5, false);

        $url = 'http://news.ltn.com.tw/newspaper'; // 報紙
        $content .= Crawler::getBody($url, 0.5, false);

        $categories = array('focus', 'politics', 'society', 'local', 'life', 'opinion', 'world', 'business', 'sports', 'entertainment', 'consumer', 'supplement');
        foreach ($categories as $category) {
            $url = 'http://news.ltn.com.tw/section/' . $category;
            $content .= Crawler::getBody($url, 0.5, false);
        }

        preg_match_all('#/news/[a-z]*/[a-z]*/[0-9]*#', $content, $matches);
        $insert = $update = 0;
        foreach ($matches[0] as $link) {
            $update = 0;
            $url = Crawler::standardURL('http://news.ltn.com.tw' . $link);
            $update ++;
            $insert += News::addNews($url, 5);
            if ($insert_limit <= $insert) {
                break;
            }
        }
        return array($update, $insert);
    }

    public static function parse($body)
    {
        if (strpos($body, '<div class="newsbox"><ul><li>網址錯誤</li></ul></div>')) {
            $ret = new StdClass;
            $ret->title = $ret->body = 404;
            return $ret;
        }

        if ("<script>alert('無這則新聞');location='index.php';</script>" == trim($body)) {
            $ret = new StdClass;
            $ret->title = $ret->body = 404;
            return $ret;
        }

        if (strpos($body, '<div class="newsbox"><ul><li>無這則新聞</li></ul></div>')) {
            $ret = new StdClass;
            $ret->title = $ret->body = 404;
            return $ret;
        }

        $body = str_replace('<meta charset="utf-8">', '<meta charset="utf-8"><meta http-equiv="Content-Type" content="text/html; charset=utf-8">', $body);

        $doc = new DOMDocument('1.0', 'UTF-8');
        @$doc->loadHTML($body);
        $ret = new StdClass;

        $content_dom = null;
        foreach ($doc->getElementsByTagName('div') as $div_dom) {
            if (in_array('content', explode(' ', $div_dom->getAttribute('class')))) {
                $content_dom = $div_dom;
                break;
            }
        }

        if (!is_null($content_dom)) {
            $cont_dom = $btitle_dom = null;
            foreach ($content_dom->getElementsByTagName('div') as $div_dom) {
                if ($div_dom->getAttribute('class') == 'cont') {
                    $cont_dom = $div_dom;
                    break;
                }

                if ($div_dom->getAttribute('class') == 'Btitle') {
                    $btitle_dom = $div_dom;
                    break;
                }
            }
            if ($content_dom->getElementsByTagName('h1')->item(0) and $cont_dom) {
                $ret->title = $content_dom->getElementsByTagName('h1')->item(0)->nodeValue;
                $ret->body = Crawler::getTextFromDom($cont_dom);
                return $ret;
            }

            if ($btitle_dom and $doc->getElementById('fb-root')) {
                $dom = $doc->getElementById('fb-root');
                $ret->title = $btitle_dom->nodeValue;
                while ($dom = $dom->nextSibling) {
                    if ($dom->nodeType == XML_ELEMENT_NODE and $dom->getAttribute('class') == 'share boxTitle') { 
                        continue;
                    }
                    if ($dom->nodeType == XML_ELEMENT_NODE and $dom->getAttribute('class') == 'elselist boxTitle') { 
                        continue;
                    }
                    $ret->body .= Crawler::getTextFromDom($dom);
                }
                return $ret;
            }
        }

        if (!$doc->getElementById('newsti')){
            // 新版
            if (strpos($body, '無此則新聞') and $doc->getElementsByTagName('title')->item(0)->nodeValue == '自由時報電子報') {
                $ret = new StdClass;
                $ret->title = $ret->body = 404;
                return $ret;
            }

            if ($doc->getElementById('newstext') and $doc->getElementsByTagName('h1')->length == 1) {
                $body = '';
                foreach ($doc->getElementById('newstext')->childNodes as $child_node) {
                    if ($child_node->nodeName == 'p') {
                        $body = $body . "\n" . $child_node->nodeValue;
                    }
                }
                $title = $doc->getElementsByTagName('h1')->item(0)->nodeValue;
                $ret->title = $title;
                $ret->body = $body;
                return $ret;
            }

            // ex: http://ent.ltn.com.tw/news/breakingnews/1468862
            foreach ($doc->getElementsByTagName('div') as $div_dom) {
                if ($div_dom->getAttribute('class') == 'news_content' and $div_dom->getElementsByTagName('h1')->length == 1) {
                    $ret->title = $div_dom->getElementsByTagName('h1')->item(0)->nodeValue;
                    $dom = $div_dom->getElementsByTagName('h1')->item(0);
                    while ($dom = $dom->nextSibling) {
                        if ($dom->nodeType !== XML_ELEMENT_NODE) {
                            continue;
                        }
                        $class = $dom->getAttribute('class');
                        $id = $dom->getAttribute('id');
                        //error_log("<{$dom->nodeName} id=\"{$id}\" class=\"{$class}\">");

                        if ($dom->nodeName == 'div') {
                            if (in_array($id, array('fb-root'))) {
                                continue;
                            } elseif (in_array($class, array('share boxTitle', 'fb_like'))) {
                                continue;
                            } elseif ($dom->getAttribute('class') == 'date') {
                                $ret->body = $dom->nodeValue;
                                continue;
                            } elseif (in_array($class, array('elselist box_ani boxTitle', 'fb_mask'))) {
                                break;
                            } elseif ('fb-post' == $class) {
                                $ret->body = trim($ret->body . "[fb-post:" . $dom->getAttribute('data-href') . "]");
                                continue;
                            } elseif ('fb-video' == $class) {
                                $ret->body = trim($ret->body . "[fb-video:" . $dom->getAttribute('data-href') . "]");
                                continue;
                            }
                        } elseif ($dom->nodeName == 'script') {
                            continue;
                        } elseif ($dom->nodeName == 'p' or ($dom->nodeName == 'span' and $class == 'ph_b')) {
                            $ret->body = trim($ret->body . "\n" . trim(Crawler::getTextFromDom($dom)));
                            continue;
                        } elseif ('h4' == $dom->nodeName) {
                            $ret->body = trim($ret->body . "\n" . trim(Crawler::getTextFromDom($dom)));
                            continue;
                        } elseif ('ul' == $dom->nodeName and 'ad_double' == $class) {
                            continue;
                        } elseif ('img' == $dom->nodeName and 'display:none;' == $dom->getAttribute('style')) {
                            continue;
                        }
                        error_log("unknown tag '{$dom->nodeName}', class=\"{$class}\", id=\"{$id}\"");
                        $ret->body = trim($ret->body . "\n" . trim(Crawler::getTextFromDom($dom)));
                    }
                    return $ret;
                }

                if ($div_dom->getAttribute('class') == 'conbox' and $div_dom->getElementsByTagName('h2')->length == 1) {
                    $ret->title = $div_dom->getElementsByTagName('h2')->item(0)->nodeValue;
                    foreach ($div_dom->getElementsByTagName('div') as $dom) {
                        if ($dom->getAttribute('class') == 'cont') {
                            $ret->body = trim(Crawler::getTextFromDom($dom));
                            return $ret;
                        }
                    }
                }
            }
            throw new Exception('newsti not found');
        }
        $ret->title = trim($doc->getElementById('newsti')->childNodes->item(0)->nodeValue);

        foreach ($doc->getElementsByTagName('div') as $div_dom) {
            if ($div_dom->getAttribute('class') == 'news_content') {
                $ret->body = trim(Crawler::getTextFromDom($div_dom));
            }
        }

        return $ret;
    }

    public static function parse2($body)
    {
        $body = str_replace('<meta http-equiv="Content-Type" content="text/html; charset=big5" />', '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">', $body);
        $doc = new DOMDocument('1.0', 'UTF-8');
        @$doc->loadHTML($body);
        $ret = new StdClass;
        $ret->title = trim($doc->getElementById('newtitle')->nodeValue);

        foreach ($doc->getElementById('newsContent')->childNodes as $node) {
            if ($node->nodeName == 'span' and $node->getAttribute('id') != 'newtitle') {
                $ret->body = trim(Crawler::getTextFromDom($node));
                break;
            }
        }

        if (!$ret->title) {
            $ret->title = $ret->body = '';
            // http://www.libertytimes.com.tw/2013/new/aug/13/today-o13.htm
            foreach ($doc->getElementById('newsContent')->childNodes as $node) {
                if ($node->nodeName == 'span' and $node->getAttribute('class') == 'insubject1') {
                    $ret->title = $node->nodeValue;
                }
                if ($node->nodeName == 'table') {
                    $ret->body = trim(Crawler::getTextFromDom($node));
                }
            }
        }

        return $ret;
    }
}
