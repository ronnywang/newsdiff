<?php

class IndexController extends Pix_Controller
{
    public function indexAction()
    {
        $this->view->news_array = News::search("diff_count > 2")->order('diff_count DESC')->limit(30);
    }

    public function logAction()
    {
        list(, /*index*/, /*log*/, $news_id, $time) = explode('/', $this->getURI());

        $this->view->newsraw = NewsRaw::find(array(intval($news_id), intval($time)));
        if (!$this->view->newsraw) {
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

        $this->view->news_array = News::search("diff_count > 2")->search(array('source' => intval($source_id)))->order('diff_count DESC')->limit(30);
        return $this->redraw('/index/index.phtml');
    }
}
