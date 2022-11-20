<?php
/**
 * author      : Administrator
 * creatTime   : 2022/11/19 22:54
 * description :
 */

use Ibazhe\PhpCurl\Curl;
use Ibazhe\PhpCurl\MultiCurl;

require "../vendor/autoload.php";
$multi = new MultiCurl();
$urls  = [
    'http://acs.m.taobao.com/gw/mtop.common.getTimestamp/',
    'http://acs.m.taobao.com/gw/mtop.common.getTimestamp/',
    'http://acs.m.taobao.com/gw/mtop.common.getTimestamp/',
    'http://acs.m.taobao.com/gw/mtop.common.getTimestamp/',
    'http://acs.m.taobao.com/gw/mtop.common.getTimestamp/',
    'http://acs.m.taobao.com/gw/mtop.common.getTimestamp/',
    'http://acs.m.taobao.com/gw/mtop.common.getTimestamp/',
    'http://acs.m.taobao.com/gw/mtop.common.getTimestamp/',
];
foreach ($urls as $key => $url) {
    /*$curl = new \Ibazhe\PhpCurl\Curl();
    $curl->setProxy('127.0.0.1:8888');
    $curl->open('GET',$url);
    $curl->setXMLHttpRequest();
    $multi->push($curl);*/
    $multi->push(Curl::createInstance()->setProxy('127.0.0.1:8888')->setRemarks($key)->open('GET', $url)->setXMLHttpRequest());
}

$multi->exec();

foreach ($multi->curls as $curl) {
    echo $curl->remarks.' - '.$curl->getResponseBody().PHP_EOL;
}
