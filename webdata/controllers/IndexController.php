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

    public function searchAction()
    {
        if ($news = News::findByURL($_GET['q'])) {
            return $this->redirect('/index/log/' . $news->id);
        }

        // 處理 http://foo.com/news/2013/1/23/我是中文標題-123456 這種網址
        $terms = explode('/', $_GET['q']);
        $last_term = array_pop($terms);
        array_push($terms, urlencode($last_term));
        if ($news = News::findByURL(implode('/', $terms))) {
            return $this->redirect('/index/log/' . $news->id);
        }

        // 處理 http://foo.com/news/2013/1/23/news.php?category=中文分類&id=12345
        $url = $_GET['q'];
        $url = preg_replace_callback('/=([^&]*)/', function($m){
            return '=' . urlencode($m[1]);
        }, $url);
        if ($news = News::findByURL($url)) {
            return $this->redirect('/index/log/' . $news->id);
        }

        return $this->alert('not found', '/');
    }
}
