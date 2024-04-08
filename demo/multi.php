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
for($i=0;$i<100;$i++){
    $urls[] = 'http://acs.m.taobao.com/gw/mtop.common.getTimestamp/';
}
for($i=0;$i<100;$i++){
    foreach ($urls as $key => $url) {
        /*$curl = new \Ibazhe\PhpCurl\Curl();
        $curl->setProxy('127.0.0.1:8888');
        $curl->open('GET',$url);
        $curl->setXMLHttpRequest();
        $multi->push($curl);*/
        $multi->push((new Curl())->setRemarks($key)->open('GET', $url)->setXMLHttpRequest());
    }

    $multi->exec();

    foreach ($multi->curls as $curl) {
        echo $curl->remarks.' - '.$curl->getResponseBody().PHP_EOL;
    }
    $multi->reset();
    sleep(1);
}