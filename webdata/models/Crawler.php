<?php

class Crawler
{
    protected static $_last_fetch = null;

    public static function getBody($url)
    {
        $url = preg_replace_callback('/[^\x00-\xff]*/u', function($m) { return urlencode($m[0]); }, $url);
        // 0.5 秒只抓一個網頁，以免太快被擋
        while (!is_null(self::$_last_fetch) and (microtime(true) - self::$_last_fetch) < 0.5) {
            usleep(1000);
        }

        self::$_last_fetch = microtime(true);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        $content = curl_exec($curl);
        $info = curl_getinfo($curl);
        if (200 !== $info['http_code']) {
            throw new Exception('not 200', $info['http_code']);
        }
        curl_close($curl);
        return $content;
    }

    public static function fetchRaw($news)
    {
        try {
            $content = self::getBody($news->url);
            if (preg_match('/content="text\/html; charset=big5/', $content)) {
                $content = iconv('big5', 'utf-8', $content);
            }

            NewsRaw::insert(array(
                'news_id' => $news->id,
                'time' => time(),
                'raw' => $content,
            ));
            $news->update(array('last_fetch_at' => time()));
        } catch (Exception $e) {
            NewsRaw::insert(array(
                'news_id' => $news->id,
                'time' => time(),
                'raw' => $e->getCode(),
            ));
            $news->update(array('last_fetch_at' => time()));
            error_log($e->getCode() . ' ' . $news->url);
        }
    }

    public static function updateAllRaw()
    {
        foreach (News::search(array('last_fetch_at' => 0)) as $news) {
            self::fetchRaw($news);
        }

        $now = time();
        foreach (News::search("last_fetch_at > $now - 86400 AND last_fetch_at < $now - 3600") as $news) {
            self::fetchRaw($news);
        }
    }
}
