<?php
/**
 * author      : Administrator
 * creatTime   : 2022/11/19 23:30
 * description :
 */
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
