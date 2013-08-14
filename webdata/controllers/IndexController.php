<?php

class IndexController extends Pix_Controller
{
    public function indexAction()
    {
        $this->view->news_array = News::search(1)->order('diff_count DESC')->limit(30);
    }

    public function logAction()
    {
        list(, /*index*/, /*log*/, $news_id, $time) = explode('/', $this->getURI());

        $this->view->newsraw = NewsRaw::find(array(intval($news_id), intval($time)));
        if (!$this->view->newsraw) {
            return $this->redirect('/');
        }
    }
}
