<?php

class Crawler_FTV
{
    public static function crawl($insert_limit)
    {
        // https://ftvapi.azurewebsites.net/api/FtvGetNewsCate 抓群組
        $categories = json_decode(file_Get_contents('https://ftvapi.azurewebsites.net/api/FtvGetNewsCate'));
        $insert = $update = 0;
        foreach ($categories as $category) {
            // https://ftvapi.azurewebsites.net/api/FtvGetNewsWeb?Cate=LIV&Page=1&Sp=12
            $url = sprintf("https://ftvapi.azurewebsites.net/api/FtvGetNewsWeb?Cate=%s&Page=1&Sp=100", $category->ID);
            $obj = json_decode(file_get_contents($url));
            foreach ($obj->ITEM as $item) {
                $update ++;
                $insert += News::addNews($item->WebLink, 14);
                if ($insert_limit <= $insert) {
                    break;
                }
            }
        }
        return array($update, $insert);
    }

    public static function parse($body, $url)
    {
        if (!preg_match('#https://news.ftv.com.tw/news/detail/([^/]*)#', $url, $matches)) {
            return null;
        }

        $id = $matches[1];
        $url = "https://ftvapi.azurewebsites.net/api/FtvGetNewsContent?id=" . urlencode($id);
        $obj = json_decode(file_get_contents($url));

        $ret = new StdClass;
        $ret->title = $obj->ITEM[0]->Title;
        $ret->body = $obj->ITEM[0]->Content;

        return $ret;
    }
}
