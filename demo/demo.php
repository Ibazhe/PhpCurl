<?php
/**
 * author      : Administrator
 * creatTime   : 2022/11/15 22:40
 * description :
 */
require "../vendor/autoload.php";
$test = new \Ibazhe\PhpCurl\curl();
echo $test->open("GET","http://www.baidu.com")->send()->getResponseBody();