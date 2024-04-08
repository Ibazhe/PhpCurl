<?php
/**
 * author      : Administrator
 * creatTime   : 2022/11/15 22:40
 * description :
 */

use Ibazhe\PhpCurl\Curl;
require "../vendor/autoload.php";

for($i=0;$i<10000;$i++) {
    $curl = new curl();
    //$curl->setProxy('127.0.0.1:8888');
    $curl->open("GET", "http://acs.m.taobao.com/gw/mtop.common.getTimestamp/");
    $curl->setPostData("123");
    $curl->send();
    //var_dump($curl->getRequestHeader());
    var_dump($curl->getResponseBody());
    //var_dump($curl->getResponseHttpCode());
}
//上下两段代码实现相同功能，静态createInstance仅仅是为了实现链式调用

//echo Curl::createInstance()->setProxy('127.0.0.1:8888')->open("GET","http://www.aliyun.com")->setXMLHttpRequest()->send()->getResponseBody();