<?php
/**
 * author      : Administrator
 * creatTime   : 2022/11/15 22:40
 * description :
 */

use Ibazhe\PhpCurl\Curl;
require "../vendor/autoload.php";


$curl = new curl();
$curl->setProxy('127.0.0.1:8888');
$curl->open("GET","http://www.baidu.com");
$curl->setXMLHttpRequest();
$curl->send();
echo $curl->getResponseBody();

//上下两段代码实现相同功能，静态createInstance仅仅是为了实现链式调用

echo Curl::createInstance()->setProxy('127.0.0.1:8888')->open("GET","http://www.baidu.com")->setXMLHttpRequest()->send()->getResponseBody();