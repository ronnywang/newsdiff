<?php

class NewsRow extends Pix_Table_Row
{
    public function generateDiff()
    {
        $diff = new diff_match_patch;
        mb_internal_encoding('UTF-8');

        $old_title = '';
        $old_body = '';
        foreach (NewsRaw::search(array('news_id' => $this->id))->order('time ASC') as $raw) {
            $ret = $raw->getInfo();

            if ($ret->title == '0' and $ret->body == '0') {
                continue;
            }

            $diffs = $diff->diff_main($old_title, $ret->title);
            $patches = $diff->patch_make($diffs);
            $text = $diff->patch_toText($patches);
            if ($text) {
                try {
                    NewsDiff::insert(array(
                        'news_id' => $this->id,
                        'time' => $raw->time,
                        'column' => 0,
                        'diff' => $text,
                    ));
                } catch (Pix_Table_DuplicateException $e) {
                }
            }

            $diffs = $diff->diff_main($old_body, $ret->body);
            $patches = $diff->patch_make($diffs);
            $text = $diff->patch_toText($patches);
            if ($text) {
                try {
                    NewsDiff::insert(array(
                        'news_id' => $this->id,
                        'time' => $raw->time,
                        'column' => 1,
                        'diff' => $text,
                    ));
                } catch (Pix_Table_DuplicateException $e) {
                }
            }

            $old_body = $ret->body;
            $old_title = $ret->title;
        }
        $this->update(arraY('diff_count' => count($this->diffs)));
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
        $this->_columns['created_at'] = array('type' => 'int');
        $this->_columns['last_fetch_at'] = array('type' => 'int');
        $this->_columns['diff_count'] = array('type' => 'int');

        $this->_relations['raws'] = array('rel' => 'has_many', 'type' => 'NewsRaw', 'foreign_key' => 'news_id', 'delete' => true);
        $this->_relations['diffs'] = array('rel' => 'has_many', 'type' => 'NewsDiff', 'foreign_key' => 'news_id', 'delete' => true);

        $this->addIndex('url_crc32', array('url_crc32'), 'unique');
    }
}
