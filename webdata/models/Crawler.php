<?php

class Crawler
{
    public static function standardURL($url)
    {
        $url = preg_replace_callback('/[^\x00-\xff]*/u', function($m) { return urlencode($m[0]); }, $url);
        return $url;
    }

    protected static $_last_fetch = null;

    public static function getBody($url, $wait = 0.5, $throw_exception = true, $retry = 3)
    {
        $url = self::standardURL($url);
        // 0.5 秒只抓一個網頁，以免太快被擋
        while (!is_null(self::$_last_fetch) and (microtime(true) - self::$_last_fetch) < $wait) {
            usleep(1000);
        }

        self::$_last_fetch = microtime(true);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.63 Safari/537.36');
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);
        $content = curl_exec($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);

        if (200 !== $info['http_code']) {
            if ($retry > 0) {
                // 重試三次
                return self::getBody($url, $wait, $throw_exception, $retry - 1);
            }

            if ($throw_exception) {
                throw new Exception('not 200', $info['http_code']);
            } else {
                error_log('not 200: ' . $url);
                return '';
            }
        }
        return $content;
    }

    public static function updateContent($news, $content)
    {
        $now = time();

        if (preg_match('/content="text\/html; charset=big5/', $content)) {
            $content = iconv('big5', 'utf-8', $content);
        }

        $last_info = $news->infos->order('`time` DESC')->first();
        $ret = NewsRaw::getInfo($content, $news->url);
        $news->update(array(
            'last_fetch_at' => time(),
            'error_count' => 0,
        ));

        if (!$last_info or $ret->title != $last_info->title or $ret->body != $last_info->body) {
            NewsRaw::insertNew(array(
                'news_id' => $news->id,
                'time' => $now,
                'raw' => $content,
            ));

            if (in_array($last_info->title, array(0, 404, '', '無法判斷的內容'))) {
                // 如果上一次是 404 這一次卻不是，直接 regenerateInfo() 最快..
                return $news->regenerateInfo();
            }
            NewsInfo::insert(array(
                'news_id' => $news->id,
                'time' => $now,
                'title' => $ret->title,
                'body' => $ret->body,
            ));
            if ($last_info) {
                $news->update(array('last_changed_at' => $now));
            }
        }
    }

    public static function updateAllRaw()
    {
        $now = time();
        $start = microtime(true);
        $fetching_news = array();
        $count = 0;
        $update_limit = 300;
        foreach (News::search("created_at > $now - 86400 AND last_fetch_at < $now - 3600")->order('last_fetch_at ASC')->limit($update_limit) as $news) {
            $fetching_news[] = $news;
            $count ++;
        }
        if ($count) {
            error_log("fetching $count news...");
        }

        $handles = array();
        $mh = curl_multi_init();
        curl_multi_setopt($mh, CURLMOPT_PIPELINING, true);
        curl_multi_setopt($mh, CURLMOPT_MAXCONNECTS, 10);

        foreach ($fetching_news as $id => $news) {
            $curl = curl_init(self::standardURL($news->url));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);

            curl_multi_add_handle($mh, $curl);
            $handles[$id] = $curl;
        }

        KeyValue::set('crawling', "curl_multi_exec ing : {$count}");
        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active and $mrc == CURLM_OK) {
            $delta = microtime(true) - $start;
            if ($delta > 180) { // 最多三分鐘
                error_log("updateContent too long... skip");
                return;
            }
            if (curl_multi_select($mh) != -1) {
                do {
                    $mrc = curl_multi_exec($mh, $active);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }

        KeyValue::set('crawling', "curl_multi_getcontent : {$count}");

        $status_count = array();
        foreach ($handles as $index => $curl) {
            $content = curl_multi_getcontent($curl);
            $info = curl_getinfo($curl);
            $news = $fetching_news[$index];

            if ($info['http_code'] != 200) {
                if ($news->error_count > 3) {
                    error_log("{$news->url} {$info['http_code']}");
                    self::updateContent($news, $info['http_code']);
                    continue;
                }
                $news->update(array('error_count' => $news->error_count + 1));
                $status_count[$news->source . '-' . intval($info['http_code'])] ++;
                continue;
            }
            self::updateContent($news, $content);
        }
        $spent = microtime(true) - $start;
        error_log('finish: ' . json_encode($status_count) . 'spent: ' . $spent);
    }

    public function getTextFromDom($node)
    {
        $ret = '';
        if ($node->nodeType == XML_TEXT_NODE) {
            $ret .= $node->nodeValue;
        } elseif ($node->nodeType == XML_COMMENT_NODE) {
        } elseif ($node->nodeType == XML_ELEMENT_NODE and strtolower($node->nodeName) == 'br') {
            $ret .= "\n";
        } elseif ($node->nodeType == XML_ELEMENT_NODE and strtolower($node->nodeName) == 'img') {
            $ret .= trim($node->getAttribute('src')) . "\n";
        } elseif ($node->nodeType == XML_ELEMENT_NODE and in_array(strtolower($node->nodeName), array('p', 'div', 'tr'))) {
            foreach ($node->childNodes as $child_node) {
                $ret .= self::getTextFromDom($child_node);
            }
            $ret = trim($ret) . "\n";
        } elseif ($node->nodeType == XML_ELEMENT_NODE and strtolower($node->nodeName) == 'a') {
            $ret .= '<a href="' . $node->getAttribute('href') . '">';
            foreach ($node->childNodes as $child_node) {
                $ret .= self::getTextFromDom($child_node);
            }
            $ret = trim($ret) . '</a>';

        } elseif ($node->nodeType == XML_ELEMENT_NODE and strtolower($node->nodeName) == 'figure') {
            $ret .= $node->getElementsByTagName('img')->item(0)->getAttribute('src') . "\n";
        } elseif (in_array(strtolower($node->nodeName), array('iframe', 'hr', 'script', 'audio', 'object', 'embed'))) {
            return '';
        } else {
            // array('table', 'td', 'span', 'strong', 'font', 'em', 'b', 'big', 'small', 'u', 'cite', 'h1', 'h2', 'h3', 'h4', 'h5', 'wbr'))) {
            // 其他 tag 都視為 inline tag
            foreach ($node->childNodes as $child_node) {
                $ret .= self::getTextFromDom($child_node);
            }
        }
        return $ret;
    }
}
