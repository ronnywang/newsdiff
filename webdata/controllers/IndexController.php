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

    public function healthAction()
    {
        header('Content-Type: text/plain');

        $ret = array();
        $check_time = 30; // 幾分鐘沒有從列表抓到任何新聞就要警告

        $sources = News::getSources();
        foreach ($sources as $id => $name) {
            if (date('H') > 8 and KeyValue::get('source_update-' . $id) < time() - $check_time * 60) {
                // 早上八點以後才會確認這個
                $ret[] = "{$name}({$id}) 超過 {$check_time} 分鐘沒有抓到新聞";
                continue;
            }

            if (KeyValue::get('source_insert-' . $id) < time() - 86400) {
                $ret[] = "{$name}({$id}) 超過一天沒有抓到新的新聞";
                continue;
            }
        }
        echo implode("\n", $ret);

        $now = time();
        $source_ids = News::search("created_at > $now - 86400 AND last_fetch_at < $now - 3600")->toArray('source');
        $source_ids = array_count_values($source_ids);
        $count = array_sum($source_ids);
        if ($count > 1000 or (array_key_exists('test', $_GET) and $_GET['test'])) {
            $new_count = count(News::search("created_at > $now - 86400 AND last_fetch_at = 0"));
            echo "\n目前累積要更新新聞數有 {$count} 則(新資料: {$new_count} 筆)\n";
            foreach ($source_ids as $source => $source_count) {
                echo "{$sources[$source]}: {$source_count}\n";
            }
            echo "正在抓: " . KeyValue::get('Crawling');
        }
        exit;
    }
}
