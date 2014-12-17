NewsDiff
========

找出新聞修改的記錄

成果 http://newsdiff.g0v.ronny.tw/

以上程式碼以 BSD License 公開

程式說明
========
* webdata/scripts/table-build.php
 * 建立資料表
* webdata/scripts/crawler-new.php
 * 從所有新聞來源取得個別新聞的網址
* webdata/scripts/crawler-one.php
 * 逐一取得新聞內容，因為執行效率不好所以不再使用
* webdata/scripts/crawler-part.php
 * 同時執行多個程序來取得新聞內容，第二個參數是總數（分母），第一個參數是餘數。假設要同時執行兩個程序，參數就是 0 2 與 1 2
* webdata/scripts/exporter.php
 * 打包新聞內容

執行範例
========

<pre>
*/10 * * * * php webdata/scripts/crawler-new.php
* * * * * php webdata/scripts/crawler-part.php 0 2
* * * * * php webdata/scripts/crawler-part.php 1 2
</pre>
