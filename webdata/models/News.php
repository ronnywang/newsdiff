<?php

class News extends Pix_Table
{
    public function init()
    {
        $this->_name = 'news';
        $this->_primary = 'id';

        $this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['url'] = array('type' => 'varchar', 'size' => 255);
        $this->_columns['created_at'] = array('type' => 'int');
        $this->_columns['last_fetch_at'] = array('type' => 'int');

        $this->_relations['raws'] = array('rel' => 'has_many', 'type' => 'NewsRaw', 'foreign_key' => 'news_id');

        $this->addIndex('url', array('url'), 'unique');
    }
}
