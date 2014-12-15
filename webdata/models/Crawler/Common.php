<?php

interface Crawler_Common
{

    // return HTML string of the index page(s)
    public static function crawlIndex();

    // search all target links in the index page(s) HTML
    public static function findLinksIn($content);

    // parse a news article page
    // @return object represents the article with 2 fields:
    //    1. title
    //    2. body
    // or return NULL if page cannot be parsed
    public static function parse($content);

}