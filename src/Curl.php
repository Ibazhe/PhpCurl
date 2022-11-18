<?php

/**
 * author      : Administrator
 * creatTime   : 2022/11/15 21:53
 * description :
 */

namespace Ibazhe\PhpCurl;

use Exception;
use Ibazhe\Cookies\CookiesManager;

class Curl
{
    protected $url;
    protected $request_headers = array();
    protected $response_headers;
    protected $response_headers_arr = array();
    protected $response_body;
    protected $timeout = 10;
    protected $fake_ip;
    protected $proxy;
    protected $ch;
    protected $redirect_max_num = 0;
    protected $redirect_num = 0;
    protected $ua;
    public $cookies;


    public function __construct($serialize_cookies = '') {
        $this->cookies = new CookiesManager($serialize_cookies);
        return $this;
    }

    /**
     * 开启自动重定向
     * @param $num int 最大连续重定向次数，0则禁止重定向,默认20
     * @return void
     */
    public function setRedirect($num = 20) {
        $this->redirect_max_num = $num;
    }

    /**
     * 伪装IP，一旦设置，一直有效
     * @param String $ip 空则随机获取一个
     * @return $this
     */
    public function setFakeIp($ip = "") {
        if ($ip === "") {
            $this->fake_ip = long2ip(mt_rand("607649792", "2079064063"));
        } else {
            $this->fake_ip = $ip;
        }
        return $this;
    }

    /**
     * 设置代理IP，为空则取消代理
     * @param $proxy
     * @return $this
     */
    public function setProxy($proxy = '') {
        $this->proxy = $proxy;
        return $this;
    }

    /**
     * 设置超时时间
     * @param $time
     * @return $this
     */
    public function setTimeOut($time) {
        $this->timeout = $time;
        return $this;
    }

    /**
     * 设置后一直有效
     * @param $value
     * @return $this
     */
    public function setUserAgent($value = 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.2)') {
        $this->ua = $value;
        return $this;
    }

    /**
     * 创建一个CURL句柄
     * @param $method string GET/POST/PUT/DELETE...
     * @param $url    string
     * @return $this
     * @throws Exception
     */
    public function open($method, $url) {
        CookiesManager::checkUrl($url);
        $this->response_headers_arr = array();
        $this->request_headers      = array();
        $this->response_body        = '';
        //重置
        $this->url = $url;
        $this->ch  = curl_init();
        curl_setopt($this->ch, CURLOPT_URL, $this->url);
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $method);
        if (!empty($this->fake_ip)) {
            $this->request_headers[] = "X-Forwarded-For: " . $this->fake_ip;
            $this->request_headers[] = "X-Originating-IP: " . $this->fake_ip;
            $this->request_headers[] = "X-Remote-IP: " . $this->fake_ip;
            $this->request_headers[] = "X-Remote-Addr: " . $this->fake_ip;
            $this->request_headers[] = "X-Client-IP: " . $this->fake_ip;
            $this->request_headers[] = "Forwarded-For: " . $this->fake_ip;
            $this->request_headers[] = "Originating-IP: " . $this->fake_ip;
            $this->request_headers[] = "Remote-IP: " . $this->fake_ip;
            $this->request_headers[] = "Remote-Addr: " . $this->fake_ip;
            $this->request_headers[] = "Client-IP: " . $this->fake_ip;
        }
        return $this;
    }

    /**
     * 类内部自用的，CURLOPT_HTTPHEADER的时候用
     * @return array
     */
    protected function buildRequestHeadersArray() {
        $arr = array();
        foreach ($this->request_headers as $header) {
            $arr[] = $header['name'] . ': ' . $header['value'];
        }
        return $arr;
    }

    /**
     * 设置本次请求头
     * @param $name
     * @param $value
     * @return $this
     */
    public function setRequestHeader($name, $value) {
        foreach ($this->request_headers as $k => $v) {
            if (CookiesManager::equal($k, $name)) {
                unset($this->request_headers[$k]);
            }
        }
        $this->request_headers[] = ['name' => trim($name), 'value' => trim($value)];
        return $this;
    }

    /**
     * 批量添加headers
     * @param $headers array 每个数组都是一条header name: value
     * @return $this
     */
    public function setRequestHeaders($headers) {
        foreach ($headers as $header) {
            $arr = explode(':', $header);
            if (count($headers) == 2) {
                $this->setRequestHeader($arr[0], $arr[1]);
            }
        }
        return $this;
    }

    public function setAccept($value = '*/*') {
        $this->setRequestHeader('Accept', $value);
        return $this;
    }

    public function setAcceptLanguage($value = 'zh-cn') {
        $this->setRequestHeader('Accept-Language', $value);
        return $this;
    }

    public function setContentType($value = 'application/x-www-form-urlencoded ') {
        $this->setRequestHeader('Content-Type', $value);
        return $this;
    }


    public function setReferer($value = '') {
        $this->setRequestHeader('Referer', $value);
        return $this;
    }

    public function setOrigin($value = '') {
        $this->setRequestHeader('Origin', $value);
        return $this;
    }

    public function setXMLHttpRequest() {
        $this->setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        return $this;
    }

    /**
     * 发送请求
     * @param $post string|string[]
     * @return $this
     * @throws Exception
     */
    public function send($post = '') {
        if ($this->ua) {
            $this->setRequestHeader('User-Agent', $this->ua);
        }
        $cookies = $this->cookies->getCookies($this->url);
        if ($cookies) {
            $this->setRequestHeader('Cookie', $cookies);
        }
        $headers = $this->buildRequestHeadersArray();
        if (count($headers) > 0) {
            curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
        }
        if (!empty($this->proxy)) {
            curl_setopt($this->ch, CURLOPT_PROXY, $this->proxy); //代理服务器地址
        }
        if (!empty($post)) {
            if (is_array($post)) {
                $post = http_build_query($post);
            }
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post);
        }
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($this->ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($this->ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($this->ch, CURLOPT_HEADER, TRUE);
        curl_setopt($this->ch, CURLOPT_ENCODING, "gzip");
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        $ret        = curl_exec($this->ch);
        $headerSize = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);
        curl_close($this->ch);
        $this->response_headers = substr($ret, 0, $headerSize);
        $this->response_body    = substr($ret, $headerSize);
        $this->cookies->upH($this->response_headers, $this->url);
        if ($this->redirect_num < $this->redirect_max_num) {
            $this->redirect_num++;
            $location = $this->getResponseHeader('location');
            $this->open('GET', $location)->send();
        }
        $this->redirect_num = 0;//已经跳转的次数归0
        return $this;
    }

    /**
     * 获取返回的header
     * @param $name string 为空则获取全部header
     * @return array|mixed
     */
    public function getResponseHeader($name = '') {
        if ($name == '') {
            return $this->response_headers;
        }
        //只有首次查询才遍历1次
        if ($this->response_headers != '' && count($this->response_headers_arr) == 0) {
            $header_line_arr = explode("\r\n", $this->response_headers);
            foreach ($header_line_arr as $header_line) {
                $header_name_offset                       = stripos($header_line, ":");
                $header_name                              = trim(substr($header_line, 0, $header_name_offset));
                $header_value                             = trim(substr($header_line, $header_name_offset + 1));
                $this->response_headers_arr[$header_name] = $header_value;
            }
        }
        foreach ($this->response_headers_arr as $key => $response_headers) {
            if (strcasecmp($key, $name) == 0) {
                return $this->response_headers_arr[$key];
            }
        }
        return false;
    }

    /**
     * 获取返回信息
     * @return mixed
     */
    public function getResponseBody() {
        return $this->response_body;
    }

    /**
     * 取两个字符串中间的字符串，如果左边两边的字符串任何一个不存在则返回false
     * @param $str
     * @param $leftStr
     * @param $rightStr
     * @return false|string
     */
    public static function getSubstr($str, $leftStr, $rightStr) {
        $left  = strpos($str, $leftStr);
        $right = strpos($str, $rightStr, $left);
        if ($left === false || $right === false) return false;
        return substr($str, $left + strlen($leftStr), $right - $left - strlen($leftStr));
    }
}