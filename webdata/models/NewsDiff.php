<?php

class NewsDiff extends Pix_Table
{
    public function init()
    {
        $this->_name = 'news_diff';
        $this->_primary = array('news_id', 'time', 'column');

        $this->_columns['news_id'] = array('type' => 'int');
        $this->_columns['time'] = array('type' => 'int');
        // 0 title, 1 body
        $this->_columns['column'] = array('type' => 'tinyint');
        $this->_columns['diff'] = array('type' => 'text');
    }
}
