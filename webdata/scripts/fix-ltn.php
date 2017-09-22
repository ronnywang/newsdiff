<?php

// 處理之前不知道網址有分 /news/xxx/breakingnews/123 和 /news/xxx/paper/123
// 這邊把他分開來
//
include(__DIR__ . '/../init.inc.php');

Pix_Table::$_save_memory = true;
Pix_Table::addStaticResultSetHelper('Pix_Array_Volume');

foreach (News::search(array('source' => 5))->order('id DESC')->volumemode(1000) as $news) {
    if (!preg_match('#://news.ltn.com.tw/news/(.*)/(breakingnews|paper)/([0-9]*)#', $news->url, $matches)) {
        continue;
    }
    $normal_url = "news.ltn.com.tw/news/{$matches[2]}/{$matches[3]}";
    if ($news->normalized_id == $normal_url) {
        continue;
    }
    $news->update(array(
        'normalized_id' => $normal_url,
        'normalized_crc32' => crc32($normal_url),
    ));
    echo $news->id . ',';
}
