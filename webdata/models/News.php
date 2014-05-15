<?php

class NewsRow extends Pix_Table_Row
{
    public function getFirstRaw()
    {
        return NewsRaw::search(array('news_id' => $this->id))->order('time ASC')->first();
    }

    public function regenerateInfo()
    {
        //$this->infos->delete();
        $start_month = mktime(0, 0, 0, date('m', $this->created_at), 1, date('Y', $this->created_at));
        $end_month = mktime(0, 0, 0, date('m', $this->last_fetch_at), 1, date('Y', $this->last_fetch_at));

        $last_changed_at = 0;

        $diff_infos = array();

        for ($time = $start_month; $time <= $end_month; $time = strtotime('+1 month', $time)) {
            $table_name = 'news_raw_' . date('Ym', $time);
            $table = NewsRaw::getTable();
            $db = NewsRaw::getDb();
            $res = $db->query("SELECT * FROM {$table_name} WHERE `news_id` = {$this->id} ORDER BY `time` ASC");
            while ($row = $res->fetch_object()) {
                try {
                    $ret = NewsRaw::getInfo($row->raw, $this->url);
                } catch (Exception $e) {
                    error_log("處理 {$this->url} 錯誤: " . $e->getMessage());
                    throw $e;
                }
                if (count($diff_infos) and $diff_infos[0]['title'] == $diff_infos[0]['body'] and in_array($diff_infos[0]['title'], array('', 0, 404, '無法判斷的內容'))) {
                    array_shift($diff_infos);
                }

                if (!count($diff_infos) or $ret->title != $diff_infos[0]['title'] or $ret->body != $diff_infos[0]['body']) {
                    array_unshift($diff_infos, array(
                        'news_id' => $this->id,
                        'time' => $row->time,
                        'title' => $ret->title,
                        'body' => $ret->body,
                    ));
                }
            }
        }

        if (!count($diff_infos)) {
            // 沒有任何資料表示 NewsRaw 可能已經被砍了，那就不要做事了
            error_log('too old: ' . $this->id);
            return;
        }

        $this->infos->delete();
        foreach ($diff_infos as $diff_info) {
            NewsInfo::insert($diff_info);
        }
        $this->update(array('last_changed_at' => count($diff_infos) > 1 ? $diff_infos[0]['time'] : 0));
    }
}

class News extends Pix_Table
{
    public function init()
    {
        $this->_name = 'news';
        $this->_primary = 'id';
        $this->_rowClass = 'NewsRow';

        $this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['url'] = array('type' => 'varchar', 'size' => 255);
        $this->_columns['normalized_id'] = array('type' => 'varchar', 'size' => 64);
        $this->_columns['normalized_crc32'] = array('type' => 'int', 'unsigned' => true);
        // 新聞來源
        $this->_columns['source'] = array('type' => 'tinyint');
        $this->_columns['created_at'] = array('type' => 'int');
        $this->_columns['last_fetch_at'] = array('type' => 'int');
        $this->_columns['last_changed_at'] = array('type' => 'int', 'default' => 0);
        $this->_columns['error_count'] = array('type' => 'tinyint', 'default' => 0);

        $this->_relations['infos'] = array('rel' => 'has_many', 'type' => 'NewsInfo', 'foreign_key' => 'news_id', 'delete' => true);

    }

    public function findByURL($url)
    {
        $ret = URLNormalizer::query($url);
        if (!$ret) {
            return;
        }
        return News::find_by_normalized_crc32(crc32($ret->normalized_id));
    }

    public function addNews($url, $source)
    {
        $ret = URLNormalizer::query($url);
        if (!$ret) {
            error_log("URLNormalizer 失敗: {$url}");
            return 0;
        }

        if (News::find_by_normalized_crc32(crc32($ret->normalized_id))) {
            return 0;
        }

        try {
            News::insert(array(
                'url' => $url,
                'normalized_id' => $ret->normalized_id,
                'normalized_crc32' => crc32($ret->normalized_id),
                'source' => $source,
                'created_at' => time(),
                'last_fetch_at' => 0,
            ));
        } catch (Pix_Table_DuplicateException $e) {
        }

        return 1;
    }

    public static function getSources()
    {
        return array(
            1 => '蘋果',
            2 => '中時',
            3 => '中央社',
            4 => '東森',
            5 => '自由',
            6 => '新頭殼',
            7 => 'NowNews',
            8 => '聯合',
            9 => 'TVBS',
            10 => '中廣新聞網',
            11 => '公視新聞網',
            12 => '台視',
            13 => '華視',
            14 => '民視',
//            15 => '三立',
        );
    }
}
