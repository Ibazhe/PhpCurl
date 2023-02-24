<?php
/**
 * author      : Administrator
 * creatTime   : 2022/11/19 23:30
 * description :
 */
var_dump(stripos('<!-- Copyright (C) Microsoft Corporation. All rights reserved. -->
<!DOCTYPE html>
<html>
<head>
    <title>正在重定向</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible','正在重定向'));

exit();
for($i=0;$i<10;$i++){
    echo $i;
    echo $i;
    echo $i;
    echo "\r";
    sleep(1);
    echo "\033[k";
}
exit();
echo strtotime('2022-11-22 18:39:27')- strtotime('2022-11-22 18:35:47');

class a{
    public $aa;
}
$a = new a();
$a->aa=1;
$arr[]='$a';

$b = new a();
$b->aa=2;
$arr[]=$b;

/*$c = $a;
$arr[]=$c;*/


var_dump(in_array($a,$arr,true));
