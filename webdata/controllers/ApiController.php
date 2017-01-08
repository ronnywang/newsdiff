<?php

class ApiController extends Pix_Controller
{
    public function newsAction()
    {
        $url = $_GET['url'];

        if (preg_match('#^[0-9]+$#', $url)) {
            if (!$news = News::find($url)) {
                return $this->json(array('error' => true, 'message' => '找不到這則新聞'));
            }
        } else if (!$news = News::findByURL($url)) {
            return $this->json(array('error' => true, 'message' => '找不到這則新聞'));
        }

        $ret = $news->toArray();
        $ret['infos'] = array();
        foreach ($news->infos->order('time ASC') as $news_info) {
            $info = new StdClass;
            $info->time = intval($news_info->time);
            $info->title = strval($news_info->title);
            $info->body = strval($news_info->body);
            $ret['infos'][] = $info;
        }
        if ($_GET['raw']) {
            $raws = $news->getRaws();
            $raws = array_map(function($raw) {
                $raw->raw = iconv('UTF-8', 'UTF-8//IGNORE', $raw->raw);
                return $raw;
            }, $raws);
            $ret['raws'] = $raws;
        }
        return $this->json($ret);
    }
}
