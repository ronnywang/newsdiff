<?php

class NewsRow extends Pix_Table_Row
{
    public function getRaws()
    {
        $sources = array();
        foreach ($this->infos as $news_info) {
            $table_name = "news_raw_" . date('Ym', $news_info->time);
            $table = NewsRaw::getTable();
            $db = NewsRaw::getDb();
            $res = $db->query("SELECT * FROM {$table_name} WHERE news_id = {$news_info->news_id} AND `time` = {$news_info->time}");
            while ($row = $res->fetch_object()) {
                $sources[] = $row;
            }
        }

        return $sources;
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
                    if (strpos($e->getMessage(), 'article id 不同') === 0) {
                        continue;
                    }
                    error_log("處理 {$this->url} 錯誤: " . $e->getMessage());
                    throw $e;
                }
                if (count($diff_infos) and $diff_infos[0]['title'] == $diff_infos[0]['body'] and in_array($diff_infos[0]['title'], array('', 0, 404, '無法判斷的內容', '503'))) {
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

    public function updateNews()
    {
        $curl = curl_init($this->url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.63 Safari/537.36');
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        $content = curl_exec($curl);
        $info = curl_getinfo($curl);
        list($header, $body) = explode("\r\n\r\n", $content, 2);
        if ($info['http_code'] != 200 or curl_errno($curl) == 28) {
            $code = (curl_errno($curl) != 28) ? $info['http_code'] : 'timeout';
            error_log("{$this->url} {$code}");
            if ($this->error_count > 3) {
                if (!in_array($info['http_code'], array(404))) { // 404 不用 log
                }
                Crawler::updateContent($newsthis, $info['http_code'], $header);
                throw new Exception('error conunt > 3');
            }
            $this->update(array('error_count' => $this->error_count + 1));
            throw new Exception('error');
        }
        try {
            Crawler::updateContent($this, $body, $header);
        } catch (Exception $e) {
            error_log("處理 {$this->url} 錯誤: " . $e->getMessage());
        }

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

    public function getStdURL($url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        switch ($host) {
        case 'tw.news.appledaily.com':
        case 'tw.appledaily.com':
        case 'tw.entertainment.appledaily.com':
        case 'tw.finance.appledaily.com':
        case 'tw.sports.appledaily.com':
        case 'tw.lifestyle.appledaily.com':
            $ret = new StdClass;
            try {
                $ret->normalized_id = Crawler_Appledaily::getStdURL($url);
            } catch (Exception $e) {
                return null;
            }
            break;
        default:
            $ret = URLNormalizer::query($url);
            break;
        }
        return $ret;
    }

    public function findByURL($url)
    {
        $ret = self::getStdURL($url);
        if (!$ret) {
            return;
        }
        return News::find_by_normalized_crc32(crc32($ret->normalized_id));
    }

    public function addNews($url, $source)
    {
        $ret = self::getStdURL($url);
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
            15 => '三立',
            16 => '風傳媒',
            17 => '關鍵評論網',
        );
    }
}
