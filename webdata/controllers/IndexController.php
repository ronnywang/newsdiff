<?php

class IndexController extends Pix_Controller
{
    public function indexAction()
    {
        $this->view->news_array = News::search(1)->order('last_changed_at DESC')->limit(30);
    }

    public function logAction()
    {
        list(, /*index*/, /*log*/, $news_id) = explode('/', $this->getURI());

        $this->view->news = News::find(intval($news_id));
        if (!$this->view->news) {
            return $this->redirect('/');
        }
    }

    public function sourceAction()
    {
        list(, /*index*/, /*source*/, $source_id) = explode('/', $this->getURI());
        $sources = News::getSources();
        if (!array_key_exists(intval($source_id), $sources)) {
            return $this->redirect('/');
        }

        $this->view->news_array = News::search(array('source' => intval($source_id)))->order('last_changed_at DESC')->limit(30);
        return $this->redraw('/index/index.phtml');
    }
}
