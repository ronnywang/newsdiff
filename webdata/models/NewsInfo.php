<?php

class NewsInfo extends Pix_Table
{
    public function init()
    {
        $this->_name = 'news_info';
        $this->_primary = array('news_id', 'time');

        $this->_columns['news_id'] = array('type' => 'int');
        $this->_columns['time'] = array('type' => 'int');
        $this->_columns['title'] = array('type' => 'text');
        $this->_columns['body'] = array('type' => 'text');
    }
}
