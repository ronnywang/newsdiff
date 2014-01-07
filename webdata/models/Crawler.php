<?php

class Crawler
{
    protected static $_last_fetch = null;

    public static function standardURL($url)
    {
        $url = preg_replace_callback('/[^\x00-\xff]*/u', function($m) { return urlencode($m[0]); }, $url);
        return $url;
    }

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

    public static function fetchRaw($news, $wait_time = 0)
    {
        $now = time();

        KeyValue::set('crawling', $news->url);
        try {
            $content = self::getBody($news->url, $wait_time);
        } catch (Exception $e) {
            $content = $e->getCode();
        }
        if (preg_match('/content="text\/html; charset=big5/', $content)) {
            $content = iconv('big5', 'utf-8', $content);
        }

        $last_info = $news->infos->order('`time` DESC')->first();
        $ret = NewsRaw::getInfo($content, $news->url);
        if (!$last_info or $ret->title != $last_info->title or $ret->body != $last_info->body) {
            NewsRaw::insertNew(array(
                'news_id' => $news->id,
                'time' => $now,
                'raw' => $content,
            ));

            if (404 == $last_info->title) {
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

        $news->update(array('last_fetch_at' => $now));
    }

    public static function updateAllRaw()
    {
        $now = time();
        $fetching_news = array();
        $count = 0;
        foreach (News::search("created_at > $now - 86400 AND last_fetch_at < $now - 3600") as $news) {
            $fetching_news[$news->source][] = $news;
            $count ++;
        }
        if ($count) {
            error_log("fetching $count news...");
        }

        $last_source = null;
        // 每個 source 輪流抓一次, 比較分散，這樣子也可以不用 sleep
        while (count($fetching_news)) {
            foreach (array_keys($fetching_news) as $source) {
                // 跟上一個同來源才要睡 0.5 秒
                self::fetchRaw(array_pop($fetching_news[$source]), $last_source == $source ? 0.5 : 0);

                if (count($fetching_news[$source]) == 0) {
                    unset($fetching_news[$source]);
                }
                $last_source = $source;
            }
        }
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
