<?php

class NewsRaw extends Pix_Table
{
    public function init()
    {
        $this->_name = 'news_raw';
        $this->_primary = array('news_id', 'time');

        $this->_columns['news_id'] = array('type' => 'int');
        $this->_columns['time'] = array('type' => 'int');
        $this->_columns['raw'] = array('type' => 'text');
    }
}
