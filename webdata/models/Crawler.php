<?php

class Crawler
{
    protected static $_last_fetch = null;

    public static function getBody($url)
    {
        // 0.5 秒只抓一個網頁，以免太快被擋
        while (!is_null(self::$_last_fetch) and (microtime(true) - self::$_last_fetch) < 0.5) {
            usleep(1000);
        }

        self::$_last_fetch = microtime(true);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $content = curl_exec($curl);
        curl_close($curl);
        return $content;
    }
}
