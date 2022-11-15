<?php

/**
 * author      : Administrator
 * creatTime   : 2022/11/15 21:53
 * description :
 */
namespace Ibazhe\PhpCurl;

class curl
{
    protected $url;
    protected $request_header = array();
    protected $response_header;
    protected $response_header_arr = array();
    protected $response_body;
    protected $timeout = 10;
    protected $fake_ip;
    protected $proxy;
    protected $ch;
    protected $redirect_num = 0;
    protected $ua;
    public $cookies;


    public function __construct() {
        $this->cookies = new CookiesManager();
        return $this;
    }

    /**
     * 开启自动重定向
     * @param $num int 最大连续重定向次数，0则禁止重定向,默认20
     * @return void
     */
    public function setRedirect($num = 20) {
        $this->redirect_num = $num;
    }

    /**
     * 获取返回的header
     * @param $name string 为空则获取全部header
     * @return array|mixed
     */
    public function getResponseHeader($name = '') {
        if ($name == '') {
            return $this->response_header;
        }
        //只有首次查询才遍历1次
        if ($this->response_header != '' && count($this->response_header_arr) == 0) {
            $header_line_arr = explode("\r\n", $this->response_header);
            foreach ($header_line_arr as $header_line) {
                $header_name_offset                      = stripos($header_line, ":");
                $header_name                             = trim(substr($header_line, 0, $header_name_offset));
                $header_value                            = trim(substr($header_line, $header_name_offset + 1));
                $this->response_header_arr[$header_name] = $header_value;
            }
        }
        foreach ($this->response_header_arr as $key => $response_header) {
            if (strcasecmp($key, $name) == 0) {
                return $this->response_header_arr[$key];
            }
        }
        return false;
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
     * 获取返回信息
     * @return mixed
     */
    public function getResponseBody() {
        return $this->response_body;
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
     * 创建一个CURL句柄
     * @param $method string GET/POST/PUT/DELETE...
     * @param $url    string
     * @return $this
     */
    public function open($method, $url) {
        $this->response_header_arr = array();
        $this->request_header      = array();
        $this->response_body       = '';
        //重置
        $this->url = $url;
        $this->ch  = curl_init();
        curl_setopt($this->ch, CURLOPT_URL, $this->url);
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $method);
        if (!empty($this->fake_ip)) {
            $this->request_header[] = "X-Forwarded-For: " . $this->fake_ip;
            $this->request_header[] = "X-Originating-IP: " . $this->fake_ip;
            $this->request_header[] = "X-Remote-IP: " . $this->fake_ip;
            $this->request_header[] = "X-Remote-Addr: " . $this->fake_ip;
            $this->request_header[] = "X-Client-IP: " . $this->fake_ip;
            $this->request_header[] = "Forwarded-For: " . $this->fake_ip;
            $this->request_header[] = "Originating-IP: " . $this->fake_ip;
            $this->request_header[] = "Remote-IP: " . $this->fake_ip;
            $this->request_header[] = "Remote-Addr: " . $this->fake_ip;
            $this->request_header[] = "Client-IP: " . $this->fake_ip;
        }
        return $this;
    }

    public function setRequestHeader($Header, $Value) {
        $this->request_header[] = $Header . ': ' . $Value;
        return $this;
    }

    /**
     * 批量添加cookies
     * @param $Headers array 每个数组都是一条header
     * @return $this
     */
    public function setRequestHeaders($Headers) {
        foreach ($Headers as $header) {
            $this->request_header[] = $header;
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

    /**
     * 设置后一直有效
     * @param $value
     * @return $this
     */
    public function setUserAgent($value = 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.2)') {
        $this->ua = $value;
        return $this;
    }

    public function setXMLHttpRequest() {
        $this->setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        return $this;
    }

    public function send($post = '') {
        if($this->ua){
            $this->setRequestHeader('User-Agent', $this->ua);
        }
        $cookies = $this->cookies->getCookies($this->url);
        if ($cookies) {
            $this->setRequestHeader('Cookie', $cookies);
        }
        if (count($this->request_header) > 0) {
            curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->request_header);
        }
        if (!empty($this->proxy)) {
            curl_setopt($this->ch, CURLOPT_PROXY, $this->proxy); //代理服务器地址
        }
        if (!empty($post)) {
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
        $this->response_header = substr($ret, 0, $headerSize);
        $this->response_body   = substr($ret, $headerSize);
        $this->cookies->upH($this->response_header);
        return $this;
    }

    public static function getSubstr($str, $leftStr, $rightStr) {
        $left = strpos($str, $leftStr);
        //echo '左边:'.$left;
        $right = strpos($str, $rightStr, $left);
        //echo '<br>右边:'.$right;
        if ($left < 0 or $right < $left) return '';
        return substr($str, $left + strlen($leftStr), $right - $left - strlen($leftStr));
    }
}