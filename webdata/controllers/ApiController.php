<?php

class ApiController extends Pix_Controller
{
    public function newsAction()
    {
        $url = $_GET['url'];

        if (!$news = News::findByURL($url)) {
            return $this->json(array('error' => true, 'message' => '找不到這則新聞'));
        }

        $infos = array();
        foreach ($news->infos->order('time ASC') as $news_info) {
            $info = new StdClass;
            $info->time = intval($news_info->time);
            $info->title = strval($news_info->title);
            $info->body = strval($news_info->body);
            $infos[] = $info;
        }
        return $this->json($infos);
    }
}
