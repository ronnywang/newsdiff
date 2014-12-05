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
        $db->query(sprintf("INSERT INTO %s (`news_id`, `time`, `raw`) VALUES (%d, %d, %s)",
            $table_name,
            $data['news_id'],
            $data['time'],
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

        switch ($host) {
        case 'www.chinatimes.com':
            $ret = Crawler_Chinatimes::parse($raw);
            break;

        case 'www.appledaily.com.tw':
            $ret = Crawler_Appledaily::parse($raw);
            break;

        case 'www.nownews.com':
            $ret = Crawler_Nownews::parse($raw);
            break;

        case 'www.ettoday.net':
            $ret = Crawler_Ettoday::parse($raw);
            break;

        case 'newtalk.tw':
            $ret = Crawler_Newtalk::parse($raw);
            break;

        case 'iservice.libertytimes.com.tw':
        case 'news.ltn.com.tw':
            $ret = Crawler_Libertytimes::parse($raw);
            break;

        case 'www.libertytimes.com.tw':
            $ret = Crawler_Libertytimes::parse2($raw);
            break;

        case 'www.cna.com.tw':
            $ret = Crawler_CNA::parse($raw);
            break;

        case 'udn.com':
            $ret = Crawler_UDN::parse($raw);
            break;

        case 'news.tvbs.com.tw':
            $ret = Crawler_TVBS::parse($raw);
            break;

        case 'www.bcc.com.tw':
            $ret = Crawler_BCC::parse($raw);
            break;

        case 'news.pts.org.tw':
            $ret = Crawler_PTS::parse($raw);
            break;

        case 'www.ttv.com.tw':
            $ret = Crawler_TTV::parse($raw);
            break;

        case 'news.cts.com.tw':
            $ret = Crawler_CTS::parse($raw);
            break;

        case 'news.ftv.com.tw':
            $ret = Crawler_FTV::parse($raw);
            break;

        case 'www.setnews.net':
            $ret = Crawler_SETNews::parse($raw);
            break;

        case 'www.stormmediagroup.com':
            $ret = Crawler_StormMediaGroup::parse($raw);
            break;

        default:
            throw new Exception('unknown host: ' . $url);
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
