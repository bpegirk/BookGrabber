<?php

include 'vendor/autoload.php';
require_once 'vendor/shuber/curl/curl.php';
include "classes/Book.php";;


$urls = file_get_contents('download-list.txt');


if (empty($urls)) {
    echo "To grab book put URL to download-list.txt\n";
    return;
}
$urlList = explode("\n", $urls);

foreach ($urlList as $url) {
    echo "### Work with url " . $url . "\n";
    $grab = new classess\Book($url);
    $grab->grab();
}
