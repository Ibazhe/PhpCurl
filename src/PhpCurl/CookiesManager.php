<?php

/**
 * author      : Administrator
 * creatTime   : 2022/11/15 21:53
 * description :
 */
namespace Ibazhe\PhpCurl;

class CookiesManager
{
    protected $cookies_arr = [];

    public function upH($headers, $url = null) {
        $parse       = parse_url($url);
        $domain      = $parse['host'];
        $headers_arr = explode("\r\n", $headers);
        foreach ($headers_arr as $header) {
            $header_name_offset = stripos($header, ":");
            $header_name        = substr($header, 0, $header_name_offset);
            $header_value       = substr($header, $header_name_offset + 1);
            if (self::equal($header_name, 'Set-Cookie')) {
                //var_dump($header_value);
                $set_cookie_arr = explode(";", $header_value);
                //var_dump($set_cookie_arr);
                $temp_cookie         = new Cookie();
                $temp_cookie->Domain = $domain;
                foreach ($set_cookie_arr as $index => $attributes) {
                    $attributes     = trim($attributes);
                    $attributes_arr = explode("=", $attributes);
                    if (count($attributes_arr) === 2) {
                        //print_r($attributes_arr);
                        $attributes_key   = trim($attributes_arr[0]);
                        $attributes_value = trim($attributes_arr[1]);
                        //只有第一个键值对才是cookie
                        if ($index === 0) {
                            $temp_cookie->Name  = $attributes_key;
                            $temp_cookie->Value = $attributes_value;
                        } else {
                            if (self::equal($attributes_key, 'Expires')) {
                                //var_dump($attributes_value);
                                $temp_cookie->Expires = strtotime($attributes_value);
                            } elseif (self::equal($attributes_key, 'Domain')) {
                                $temp_cookie->Domain = $attributes_value;
                            } elseif (self::equal($attributes_key, 'Path')) {
                                $temp_cookie->Path = $attributes_value;
                            } elseif (self::equal($attributes_key, 'SameSite')) {
                                $temp_cookie->SameSite = $attributes_value;
                            }
                        }
                    } else {
                        if (self::equal($attributes, 'Secure')) {
                            $temp_cookie->Secure = true;
                        } elseif (self::equal($attributes, 'HttpOnly')) {
                            $temp_cookie->HttpOnly = true;
                        }
                    }
                }
                $this->up($temp_cookie);
            }
        }
    }

    /**
     * @param $url      string 欲使用此cookie访问的url
     * @param $is_xhr   bool 是否为xhr/ajax/js请求
     * @return string
     */
    public function getCookies($url, $is_xhr = false) {
        $parse  = parse_url($url);
        $domain = $parse['host'];
        $path   = $parse['path'];
        $secure = $parse['scheme'] == 'https';
        //var_dump($parse);
        //print_r($this->cookies_arr);//exit();
        $res_ck = '';
        /**
         * @var $cookie    Cookie
         */

        foreach ($this->cookies_arr as $index => $cookie) {
            if (!(self::equal($cookie->Domain, $domain) || self::endWith('.' . $domain, $cookie->Domain))) {
                //var_dump('Domain' . $domain . '|' . $cookie->Domain);
                continue;
            }
            //如果cookie的路径在请求的路径首部，代表通过
            if (!self::startWith($path, $cookie->Path)) {
                //var_dump('Path' . $path . '|' . $cookie->Path);
                continue;
            }
            //如果是xhr请求的话，HttpOnly不能是true,不然不要这个ck。如果不是的话，就无所谓了
            if ($is_xhr && $cookie->HttpOnly) {
                //var_dump($cookie->Name);
                continue;
            }

            if ($cookie->Secure != $secure) {
                //var_dump('Secure' . $secure . '|' . $cookie->Secure);
                continue;
            }
            if ($cookie->Expires < time()) {
                //var_dump('time');
                continue;
            }
            $res_ck .= $cookie->Name . '=' . $cookie->Value . ';';
        }
        //var_dump($res_ck);
        return $res_ck;
    }

    /**
     * 更新/添加cookie
     * @param $up_cookie Cookie|string   cookies文本或者cookie对象或者cookie对象数组
     * @return void
     */
    public function up($up_cookie) {
        $up_cookie_arr = [];
        //把cookies文本转成cookie对象数组
        if (is_string($up_cookie)) {
            $cookie_str_arr = explode(';', $up_cookie);
            foreach ($cookie_str_arr as $cookie_str) {
                $temp_arr           = explode('=', $cookie_str);
                $temp_cookie        = new Cookie();
                $temp_cookie->Name  = trim($temp_arr[0]);
                $temp_cookie->Value = trim($temp_arr[1]);
                $up_cookie_arr[]    = $temp_cookie;
            }
        }
        //把cookie对象转成cookie对象数组
        if ($up_cookie instanceof Cookie) {
            $up_cookie_arr = [$up_cookie];
        }
        /**
         * @var $temp_up_cookie    Cookie
         * @var $cookie            Cookie
         */
        //遍历需要添加的cookie
        foreach ($up_cookie_arr as $temp_up_cookie) {
            //以防重复，遍历现有的cookie，先把之前添加进来的同名cookie删除
            foreach ($this->cookies_arr as $index => $my_cookie) {

                if ($my_cookie->Name == $temp_up_cookie->Name) {
                    unset($this->cookies_arr[$index]);
                }
            }
            //如果这个cookie值是空的话就不添加。为啥不写再方法首部呢，是因为上面的代码可以正好把同名的ck直接删除掉
            if (empty($temp_up_cookie->Value)) {
                return;
            }
            $this->cookies_arr[] = $temp_up_cookie;
        }
    }

    /**
     * 判断字符串是否在尾部
     * @param $str
     * @param $suffix
     * @return bool
     */
    protected static function endWith($str, $suffix) {
        $length = strlen($suffix);
        if ($length == 0) {
            return true;
        }
        return (substr($str, -$length) === $suffix);
    }

    /**
     * 判断字符串是否在首部
     * @param $str
     * @param $suffix
     * @return bool
     */
    protected static function startWith($str, $suffix) {
        $length = strlen($suffix);
        if ($length == 0) {
            return true;
        }
        return (substr($str, 0, $length) === $suffix);
    }

    protected static function equal($str1, $str2) {
        return strcasecmp($str1, $str2) == 0;
    }
}