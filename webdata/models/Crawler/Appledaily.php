<?php

class Crawler_Appledaily
{
    public static function getStdURL($url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);

        if ($host == 'tw.news.appledaily.com') {
            $host = 'tw.appledaily.com';
        }

        if (in_array($host, array(
            'tw.entertainment.appledaily.com',
            'tw.sports.appledaily.com',
            'tw.finance.appledaily.com',
        )) and strpos($path, '/realtime') === 0) {
            $url = 'https://tw.appledaily.com/new' . $path;
        } elseif ($host == 'tw.lifestyle.appledaily.com' and preg_match('#^/(gadget|lifestyle)/realtime#', $path)) {
            $url = 'https://tw.appledaily.com/new' . preg_replace('#^/(gadget|lifestyle)#', '', $path);
        } elseif (preg_match('#^/(micromovie|local|new|forum|politics|life|international|recommend)(/realtime.*)#', $path, $matches) and 'tw.appledaily.com' == $host) {
            $url = 'https://tw.appledaily.com/new' . $matches[2];
        } elseif (preg_match('#^/(international|headline)/daily/#', $path)) {
            $url = 'https://tw.appledaily.com' . $path;
        } elseif (in_array($host, array(
            'tw.entertainment.appledaily.com',
            'tw.finance.appledaily.com',
            'tw.sports.appledaily.com',
        )) and strpos($path, '/daily/') === 0) {
            $url = 'https://tw.appledaily.com/' . explode('.', $host)[1] . $path;
        } elseif ($host == 'tw.appledaily.com' and preg_match('#^/(entertainment|finance|sports|)/daily#', $path)) {
            $url = 'https://tw.appledaily.com' . $path;
        } else {
            throw new Exception("unknown url:" . $url);
        }
        return $url;
    }

    public static function crawl($insert_limit)
    {
        $link = array(
            'https://tw.appledaily.com/recommend/realtime/',
            'https://tw.appledaily.com/new/realtime/',
            'https://tw.appledaily.com/micromovie/realtime/',
            'https://tw.entertainment.appledaily.com/realtime/',
            'https://tw.finance.appledaily.com/realtime/',
            'https://tw.news.appledaily.com/local/realtime/',
            'https://tw.news.appledaily.com/international/realtime/',
            'https://tw.news.appledaily.com/politics/realtime/',
            'https://tw.news.appledaily.com/life/realtime/',
            'https://tw.lifestyle.appledaily.com/gadget/realtime/',
            'https://tw.lifestyle.appledaily.com/lifestyle/realtime/',
            'https://tw.sports.appledaily.com/realtime/',
            'https://tw.appledaily.com/complainevent/',
            'https://tw.news.appledaily.com/forum/realtime/',
        );

        $insert = $update = 0;
        $added = array();
        foreach ($link as $index_url) {
            $index_host = parse_url($index_url, PHP_URL_HOST);
            for ($i = 1; $i < 2; $i ++) {
                try {
                    error_log("loading news from {$index_url}{$i}");
                    $content = Crawler::getBody($index_url . $i);
                    preg_match_all('#"(https:)?/[^"]*(realtime|daily)/\d{8,8}/\d{3,}#', $content, $matches);
                    foreach ($matches[0] as $url) {
                        $url = trim($url, '"');
                        if (strpos($url, 'https://') === false) {
                            $url = 'https://' . $index_host . $url;
                        }
                        $url = self::getStdURL($url);

                        if (array_key_exists($url, $added)) {
                            continue;
                        }
                        $added[$url] = true;
                        $update ++;
                        $insert += News::addNews($url, 1);
                        if ($insert_limit <= $insert) {
                            break 3;
                        }
                    }
                } catch (Exception $e) {
                    error_log("Crawler_Appledaily {$url} failed: {$e->getMessage()}");
                }
            }
        }

        return array($update, $insert);
    }

    public static function parse($body)
    {
        $ret = new StdClass;

        $doc = new DOMDocument;
        @$doc->loadHTML($body);

        $h1_dom = $doc->getElementsByTagName('h1')->item(0);
        if (!$h1_dom) {
            $ret->title = $ret->body = 404;
            return $ret;
        }

        $ret->title = $h1_dom->nodeValue;
        $ret->body = '';

        // 出版時間
        foreach ($doc->getElementsByTagName('div') as $div_dom) {
            if ($div_dom->getAttribute('class') == 'ndArticle_creat') {
                $ret->body .= $div_dom->nodeValue . "\n";
                break;
            }
        }

        // 內文
        foreach ($doc->getElementsByTagName('div') as $div_dom) {
            if ($div_dom->getAttribute('class') == 'ndArticle_margin') {
                $ret->body .= Crawler::getTextFromDom($div_dom->getElementsByTagName('p')->item(0));
                break;
            }
        }

        return $ret;
    }


}
