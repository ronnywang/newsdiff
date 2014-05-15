#!/usr/bin/env php
<?php
if (!file_exists('news-list.csv.gz')) {
    include(__DIR__ . '/../init.inc.php');
    Pix_Table::$_save_memory = true;

    $fp1 = gzopen('news-list.csv.gz', 'w');
    $fp2 = gzopen('news-content.csv.gz', 'w');

    fputcsv($fp1, array(
        'news_id',
        'url',
        'source',
        'created_at',
    ));

    fputcsv($fp2, array(
        'news_id',
        'time',
        'title',
        'body',
    ));

    $start = 0;
    while (true) {
        $news_pool = array();
        foreach (News::search(1)->order('id ASC')->after(array('id' => $start))->limit(100) as $news) {
            $news_pool[] = $news->id;
            error_log($news->id);
            fputcsv($fp1, array(
                $news->id,
                $news->url,
                $news->source,
                $news->created_at,
            ));
        }
        if (!count($news_pool)) {
            break;
        }

        foreach (NewsInfo::search(1)->searchIn('news_id', $news_pool)->order(array('news_id', 'time')) as $info) {
            fputcsv($fp2, array(
                $info->news_id,
                $info->time,
                $info->title,
                str_replace("\n", '\n', $info->body),
            ));
        }
        $start = $news->id;
    }
}
