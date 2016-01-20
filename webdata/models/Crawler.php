<?php

class Crawler
{
    public static function standardURL($url)
    {
        $url = preg_replace_callback('/[^\x00-\xff]*/u', function($m) { return urlencode($m[0]); }, $url);
        return $url;
    }

    protected static $_record_cache = array();

    public static function dns_get_record($host)
    {
        if (!array_key_exists($host, self::$_record_cache)) {
            self::$_record_cache[$host] = dns_get_record($host, DNS_A);
        }
        return self::$_record_cache[$host];
    }

    public static function roundRobinURL($url)
    {
        if (!preg_match('#(https?://)([^/]*)(.*)#', $url, $matches)) {
            return array($url, null);
        }
        $host = $matches[2];
        $records = self::dns_get_record($host, DNS_A);
        if (!$records) {
            return array($url, null);
        }
        shuffle($records);
        return array($matches[1] . $records[0]['ip'] . $matches[3], $host);
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
        list($url, $host) = self::roundRobinURL($url);

        $curl = curl_init($url);
        if (!is_null($host)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Host: ' . $host));
        }
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
                throw new Exception('not 200: ' . $url, $info['http_code']);
            } else {
                error_log('not 200: ' . $url);
                return '';
            }
        }
        return $content;
    }

    public static function updateContent($news, $content, $header)
    {
        $now = time();

        if (preg_match('/content="text\/html; charset=big5/', $content)) {
            if ($iconved_content = iconv('big5', 'utf-8', $content)) {
                $content = $iconved_content;
            }
            $content = str_replace('charset=big5', 'charset=utf-8', $content);
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
                'header' => $header,
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

    protected static $_valid_cache = array();
    public static function validSource($source_id)
    {
        if (!array_key_exists($source_id, self::$_valid_cache)) {
            self::$_valid_cache[$source_id] = KeyValue::get('stop-source-' . $source_id);
        }
        $t = self::$_valid_cache[$source_id];
        if ($t > time()) {
            return false;
        }
        return true;
    }

    public static function updateAllRaw()
    {
        self::updatePart(1, 1);
    }

    public static function updatePart($part, $total)
    {
        $now = time();
        $fetching_news = array();
        $count = 0;
        $update_limit = 300;
        $invalid_count = 0; // 每次最多找 50 筆
        // 以下這些來源如果 multi thread 抓很容易失敗，因此改成單一 thread 來抓
        $alone_sources = array(
            10 => true, // BCC 中廣新聞
            14 => true, // 民視新聞
            2 => true,  // 中時
        );
        $total = intval($total);
        $part = intval($part);
        foreach (News::search("created_at > $now - 86400 AND last_fetch_at < $now - 3600")->order('last_fetch_at ASC') as $news) {
            if ($total !== 1 and intval($news->source) % $total !== $part) {
                continue;
            }
            if (!self::validSource($news->source)) {
                if ($invalid_count >= 50) {
                    continue;
                }
                $alone_sources[$news->source] = true;
                $fetching_news[] = $news;
                $invalid_count ++;
                $count ++;
                continue;
            }
            $fetching_news[] = $news;
            if (count($fetching_news) >= $update_limit) {
                break;
            }
            $count ++;
        }
        if (!$count) {
            return;
        }
        error_log("fetching $count news...");
        $start = microtime(true);

        $handles = array();
        $mh = curl_multi_init();
        curl_multi_setopt($mh, CURLMOPT_PIPELINING, true);
        curl_multi_setopt($mh, CURLMOPT_MAXCONNECTS, 10);

        $alone_handles = array();
        foreach ($fetching_news as $id => $news) {
            $url = self::standardURL($news->url);
            list($url, $host) = self::roundRobinURL($url);

            $curl = curl_init($url);
            if (!is_null($host)) {
                curl_setopt($curl, CURLOPT_HTTPHEADER, array('Host: ' . $host));
            }
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.63 Safari/537.36');
            curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
            curl_setopt($curl, CURLOPT_HEADER, true);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);

            if (array_key_exists($news->source, $alone_sources)) {
                $alone_handles[] = $curl;
            } else {
                curl_multi_add_handle($mh, $curl);
            }
            $handles[$id] = $curl;
        }

        KeyValue::set('crawling', "curl_multi_exec ing : {$count}");
        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        $skip = false;
        while ($active and $mrc == CURLM_OK) {
            $delta = microtime(true) - $start;
            if ($delta > 60) { // 最多三分鐘
                error_log("updateContent too long... skip");
                $skip = true;
                break;
            }
            if (curl_multi_select($mh) != -1) {
                do {
                    $mrc = curl_multi_exec($mh, $active);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }

        KeyValue::set('crawling', "curl_multi_getcontent : {$count}");

        $status_count = array();
        foreach ($fetching_news as $index => $news) {
            $curl = $handles[$index];
            if (array_key_exists($news->source, $alone_sources)) {
                $content = curl_exec($curl);
            } else {
                $content = curl_multi_getcontent($curl);
            }
            $info = curl_getinfo($curl);
            list($header, $body) = explode("\r\n\r\n", $content, 2);

            if ($info['http_code'] != 200 or curl_errno($curl) == 28) {
                $code = ($info['http_code'] != 200) ? $info['http_code'] : 'timeout';
                if ($skip) {
                    $status_count[$news->source . '-' . $code] ++;
                    continue;
                }
                if ($news->error_count > 3) {
                    if (!in_array($info['http_code'], array(404))) { // 404 不用 log
                        error_log("{$news->url} {$info['http_code']}");
                    }
                    self::updateContent($news, $info['http_code'], $header);
                    continue;
                }
                if (curl_errno($curl) != 28) {
                    $news->update(array('error_count' => $news->error_count + 1));
                }
                $status_count[$news->source . '-' . $code] ++;
                continue;
            }
            $status_count[$news->source . '-' . intval($info['http_code'])] ++;
            try {
                self::updateContent($news, $body, $header);
            } catch (Exception $e) {
                error_log("處理 {$news->url} 錯誤: " . $e->getMessage());
            }
        }
        $spent = microtime(true) - $start;
        error_log('finish: ' . json_encode($status_count) . ', spent: ' . $spent);
        foreach ($status_count as $source_code => $count) {
            list($source, $code) = explode('-', $source_code);
            if ($code == 0 and $count > 100) { // 如果有超過 100 個失敗， 10 分鐘內不要抓這個來源
                KeyValue::set('stop-source-' . $source, time() + 600);
                error_log("skip source {$source} 10 minute");
            }
        }
    }

    public static function getDomByNameAndClass($node, $name, $class)
    {
        foreach ($node->getElementsByTagName($name) as $dom) {
            if (in_array($class, explode(' ', $dom->getAttribute('class')))) {
                return $dom;
            }
        }
        return null;
    }

    public static function getTextFromDom($node)
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
            if ($node->getElementsByTagName('img')->item(0)) {
                $ret .= $node->getElementsByTagName('img')->item(0)->getAttribute('src') . "\n";
            }
        } elseif (in_array(strtolower($node->nodeName), array('iframe', 'hr', 'script', 'audio', 'object', 'embed'))) {
            return '';
        } else {
            // array('table', 'td', 'span', 'strong', 'font', 'em', 'b', 'big', 'small', 'u', 'cite', 'h1', 'h2', 'h3', 'h4', 'h5', 'wbr'))) {
            // 其他 tag 都視為 inline tag
            if ($node->childNodes) {
                foreach ($node->childNodes as $child_node) {
                    $ret .= self::getTextFromDom($child_node);
                }
            }
        }
        return $ret;
    }
}
