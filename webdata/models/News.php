<?php

class NewsRow extends Pix_Table_Row
{
    public function getFirstRaw()
    {
        return NewsRaw::search(array('news_id' => $this->id))->order('time ASC')->first();
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
        $this->_columns['url_crc32'] = array('type' => 'int');
        $this->_columns['normalized_id'] = array('type' => 'varchar', 'size' => 64);
        $this->_columns['normalized_crc32'] = array('type' => 'int', 'unsigned' => true);
        // 新聞來源
        $this->_columns['source'] = array('type' => 'tinyint');
        $this->_columns['created_at'] = array('type' => 'int');
        $this->_columns['last_fetch_at'] = array('type' => 'int');
        $this->_columns['last_changed_at'] = array('type' => 'int');
        $this->_columns['last_diff_at'] = array('type' => 'int');

        $this->_relations['infos'] = array('rel' => 'has_many', 'type' => 'NewsInfo', 'foreign_key' => 'news_id', 'delete' => true);

        $this->addIndex('url_crc32', array('url_crc32'), 'unique');
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
            return;
        }

        if (News::find_by_normalized_crc32(crc32($ret->normalized_id))) {
            return;
        }

        try {
            News::insert(array(
                'url' => $url,
                'url_crc32' => $url_crc32,
                'normalized_id' => $ret->normalized_id,
                'normalized_crc32' => crc32($ret->normalized_id),
                'source' => $source,
                'created_at' => time(),
                'last_fetch_at' => 0,
            ));
        } catch (Pix_Table_DuplicateException $e) {
        }
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
        );
    }
}
