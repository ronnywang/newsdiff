<?php

include(__DIR__ . '/../init.inc.php');
Pix_Table::addStaticResultSetHelper("Pix_Array_Volume");
Pix_Table::$_save_memory = true;

// 列出近十天的所有新聞
// 並把昨天起七天的上傳到 dropbox

class Dumper
{
    protected $fps = array();
    protected $fp_time = null;

    public function outputIDS($ids)
    {
        $chunked_ids = array_chunk($ids, 100, true);
        foreach ($chunked_ids as $part_ids) {
            $showed = array();
            foreach (NewsInfo::search(1)->searchIn('news_id', array_keys($part_ids))->order('news_id, time') as $news_info) {
                if ($showed[$news_info->news_id]) {
                    continue;
                }
                $showed[$news_info->news_id] = true;

                $date = date('Ymd', $part_ids[$news_info->news_id]['created_at']);
                if (!$this->fps[$date]) {
                    $fp = gzopen('/tmp/newsdump-' . $date . '.gz', 'w');
                    $this->fps[$date] = $fp;
                }
                $fp = $this->fps[$date];

                fputs($fp, json_encode($part_ids[$news_info->news_id]) . "\n");
                fputs($fp, json_encode($news_info->title, JSON_UNESCAPED_UNICODE) . "\n");
                fputs($fp, json_encode($news_info->body, JSON_UNESCAPED_UNICODE) . "\n");
            }
        }
    }

    public function main()
    {
        $ids = array();

        foreach (News::search(1)->order('id DESC')->volumemode(1000) as $news) {
            if ($news->created_at > strtotime('today')) {
                continue;
            }

            if ($news->created_at < time() - 86400 * 10) {
                break;
            }

            $ids[$news->id] = $news->toArray();

            if (count($ids) > 1000) {
                $this->outputIDS($ids);
                $ids = array();
            }
        }
        $this->outputIDS($ids);

        for ($i = 1; $i < 7; $i ++) {
            $date = date('Ymd', strtotime('today') - 86400 * $i);
            $fp = $this->fps[$date];
            fflush($fp);
            fclose($fp);
            DropboxLib::putFile("/tmp/newsdump-{$date}.gz", "/OpenData/newsdiff/{$date}.txt.gz");
        }

        system("rm /tmp/newsdump*");
    }
}

$d = new Dumper;
$d->main();
