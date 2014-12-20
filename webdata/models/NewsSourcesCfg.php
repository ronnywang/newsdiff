<?php

/**
 * 新聞來源定義
 *
 * 一個統一的類型，供存取所有新聞來源定義
 */
class NewsSourcesCfg
{
    static $cfg = array();
    static $parser_callbacks = array();

    /**
     * setAll
     * @param mixed[] $cfg  新聞來源定義清單
     */
    static function setAll($cfg)
    {
        self::validateAll($cfg);
        self::$cfg = $cfg;
        self::$parser_callbacks = array(); // 重設 cache
    }

    /**
     * getAll
     * @return mixed[] 新聞來源定義清單
     */
    static function getAll()
    {
        return self::$cfg;
    }

    /**
     * get
     * @param integer $id 新聞來源的 id
     * @return mixed[] 新聞來源定義
     */
    static function get($id)
    {
        if (!isset(self::$cfg[$id])) return NULL;
        return self::$cfg[$id];
    }

    /**
     * get
     * @param mixed[] $cfg 新聞來源定義清單
     * @return boolean 是否全部 $cfg 所定義的新聞類型都及格
     */
    static function validateAll($cfg)
    {
        foreach ($cfg as $id => $def) {
            // 檢模新聞來源的 class，必需為實現 Crawler_Common 的類型
            // 以確保它包含 Crawler::crawl 所需用到的方法
            if (!is_subclass_of($def['class'], 'Crawler_Common')) {
                throw new Exception('Parameter "class"  '.$def['class'].
                    ' (news id='.$id.') is not implementing interface Crawler_Common');
                return FALSE;
            }
        }
        return TRUE;
    }

    /**
    * getHostParser
    * @param string $host 文章 url 的 host 部份
     * @return mixed 文章解析器的 callback。若果沒有找到對應 $host 的解析器，傳回 False
     */
    static function getHostParser($host)
    {
        if (isset(self::$parser_callbacks[$host]))
        {
            return self::$parser_callbacks[$host];
        }
        foreach (self::$cfg as $id => $source )
        {
            foreach ((array) $source['parsers'] as $parser_host => $parser_method)
            {
                if ($parser_host == $host)
                {
                    self::$parser_callbacks[$host] = array($source['class'], $parser_method);
                    break;
                }
            }
            if (isset(self::$parser_callbacks[$host])) return self::$parser_callbacks[$host];
        }
        return FALSE; // there is no match
    }

    /**
     * getCrawlers
     * @return string[] 新聞來源的 Crawler 類型名稱清單，key 為新聞來源的 id
     */
    static function getCrawlers()
    {
        $crawlers = array();
        foreach (self::$cfg as $id => $source)
        {
            $crawlers[$id] = $source['class'];
        }
        return $crawlers;
    }

    /**
     * getNames
     * @return string[] 新聞來源的名稱清單，key 為新聞來源的 id
     */
    static function getNames()
    {
        $crawlers = array();
        foreach (self::$cfg as $id => $source)
        {
            $crawlers[$id] = $source['name'];
        }
        return $crawlers;
    }

}