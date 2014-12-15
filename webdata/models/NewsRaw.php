<?php

class NewRawRow extends Pix_Table_Row
{
    public function getInfo()
    {
        $news = News::find($this->news_id);
        $url = $news->url;

        return NewsRaw::getInfo($this->raw, $url);
    }
}

class NewsRaw extends Pix_Table
{
    public function init()
    {
        $this->_name = 'news_raw';
        $this->_primary = array('news_id', 'time');
        $this->_rowClass = 'NewRawRow';

        $this->_columns['news_id'] = array('type' => 'int');
        $this->_columns['time'] = array('type' => 'int');
        $this->_columns['header'] = array('type' => 'text');
        $this->_columns['raw'] = array('type' => 'longtext');
    }

    public static function insertNew($data)
    {
        $table_name = "news_raw_" . date('Ym', $data['time']);
        $table = NewsRaw::getTable();
        $db = NewsRaw::getDb();
        $db->query(sprintf("INSERT INTO %s (`news_id`, `time`, `header`, `raw`) VALUES (%d, %d, %s, %s)",
            $table_name,
            $data['news_id'],
            $data['time'],
            $db->quoteWithColumn($table, $data['header'], 'header'),
            $db->quoteWithColumn($table, $data['raw'], 'raw')
        ));
    }

    public static function getInfo($raw, $url)
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (strlen($raw) < 10) {
            $ret = new StdClass;
            $ret->title = $ret->body = $raw;
            return $ret;
        }

        if (($callback = NewsSourcesCfg::getHostParser($host)) === FALSE) {
            throw new Exception('unknown host: ' . $host);
        } else {
            if (!is_callable($callback)) {
                throw new Exception('callback error:  ' . var_export($callback, TRUE) .
                    ' is not callable (for '. $host . ')');
            }
            $ret = call_user_func($callback, $raw);
        }

        if (!$ret->title or !$ret->body) {
            $ret = new StdClass;
            $ret->title = $ret->body = '無法判斷的內容';
            error_log('找不到內容:' . $url);
        }

        // XXX: 濾掉 4byte Unicode，現在用的 MySQL 版本寫入 4byte unicode 會失敗
        $ret->title = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $ret->title);
        $ret->body = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $ret->body);

        return $ret;
    }
}
