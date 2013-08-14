<?php

class NewRawRow extends Pix_Table_Row
{
    public function getInfo()
    {
        $news = News::find($this->news_id);
        $url = $news->url;
        $host = parse_url($url, PHP_URL_HOST);

        if (strlen($this->raw) < 10) {
            $ret = new StdClass;
            $ret->title = $ret->body = $this->raw;
            return $ret;
        }

        switch ($host) {
        case 'www.chinatimes.com':
            $ret = Crawler_Chinatimes::parse($this->raw);
            break;

        case 'www.appledaily.com.tw':
            $ret = Crawler_Appledaily::parse($this->raw);
            break;

        case 'www.nownews.com':
            $ret = Crawler_Nownews::parse($this->raw);
            break;

        case 'www.ettoday.net':
            $ret = Crawler_Ettoday::parse($this->raw);
            break;

        case 'newtalk.tw':
            $ret = Crawler_Newtalk::parse($this->raw);
            break;

        case 'iservice.libertytimes.com.tw':
            $ret = Crawler_Libertytimes::parse($this->raw);
            break;

        case 'www.libertytimes.com.tw':
            $ret = Crawler_Libertytimes::parse2($this->raw);
            break;

        case 'www.cna.com.tw':
            $ret = Crawler_CNA::parse($this->raw);
            break;

        default:
            throw new Exception('unknown host: ' . $url . ' ' . $this->time);
        }

        if (!$ret->title or !$ret->body) {
            throw new Exception('找不到內容:' . $url . ' ' . $this->time);
        }

        return $ret;
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
        $this->_columns['raw'] = array('type' => 'text');
        $this->_columns['converted_at'] = array('type' => 'int');
    }
}
