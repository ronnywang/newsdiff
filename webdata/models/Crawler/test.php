<?php

include(__DIR__ . '/../../init.inc.php');
$class = 'Crawler_' . $_SERVER['argv'][1];

class News
{
    protected static $url_pool = array();

    public static function addNews($url, $count)
    {
        if (in_array($url, self::$url_pool)) {
            return 0;
        }

        self::$url_pool[] = $url;
        error_log(sprintf("adding %d %s", count(self::$url_pool), $url));

        return 1;
    }

    public static function getRandomNews($count = 100)
    {
        shuffle(self::$url_pool);
        return array_slice(self::$url_pool, 0, 100);
    }
}

call_user_func(array($class, 'crawl'), 100);
$news = News::getRandomNews(100);
readline("try random 100 news (press enter to continue)");
foreach ($news as $url) {
    error_log("try {$url}");
    $content = Crawler::getBody($url);
    $ret = call_user_func(array($class, 'parse'), $content, $url);
    echo ("Title: " . $ret->title . "\n");
    echo $ret->body . "\n";
    echo "{$url}\n";
    readline("continue");

}
