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
    /**
     * CURL句柄
     * @var $ch resource
     */
    protected $ch;

    /**
     * @var $redirect_max_num int  重定向上限
     */
    protected $redirect_max_num = 0;

    /**
     * @var $redirect_num int 当前请求已重定向次数
     */
    protected $redirect_num = 0;

    /**
     * @var $ua string curl ua
     */
    protected $ua;

    /**
     * @var $ssl_verify bool  是否开启ssl验证
     */
    protected $ssl_verify = false;

    /**
     * @var $http_version int HTTP版本
     */
    protected $http_version = 2;

    /**
     * @var $resolve_mode int 域名解析方式
     */
    protected $resolve_mode = 0;

    /**
     * @var $resolve array 不使用DNS解析的域名，类似于hosts
     */
    protected $resolve = array();

    /**
     * @var $encoding string HTTP请求头中"Accept-Encoding: "的值。 这使得能够解码响应的内容。 支持的编码有"identity"，"deflate"和"gzip"。如果为空字符串""，会发送所有支持的编码类型。
     */
    protected $encoding = 'gzip';

    /**
     * @var $timeout int  请求超时时间
     */
    protected $timeout = 10;

    /**
     * @var $fake_ip string  请求伪造ip
     */
    protected $fake_ip;

    /**
     * @var $proxy string curl代理
     */
    protected $proxy;

    /**
     * @var $cookies CookiesManager
     */
    public $cookies;

    /**
     * @var $remarks mixed 这个属性随意设置，相当于一个备注，在多线程中可以分配给他们一个唯一属性之类的
     */
    public $remarks;


    /**
     * 本次请求是否已经构造过curl句柄
     * @var bool
     */
    protected $is_build_ch = false;

    /**
     * @var $url string 本次请求的URL
     */
    protected $url;

    /**
     * @var $url_parse array 本次请求的URL的解析
     */
    protected $url_parse;

    /**
     * @var $method string 本次请求方式
     */
    protected $method;

    /**
     * @var $post_data string 本次请求提交数据
     */
    protected $post_data;

    /**
     * @var $request_headers array  请求头数组 格式：array([name=>a,value=b],[name=>a,value=b])
     */
    protected $request_headers = array();


    /**
     * @var $response_raw string 原生返回，包含头
     */
    protected $response_raw;
    /**
     * @var $request_header string 原生请求头
     */
    protected $request_header;
    /**
     * @var $response_headers string 返回头文本
     */
    protected $response_headers;
    /**
     * @var $response_headers_arr string 返回头数组 格式 array([name=>value])
     */
    protected $response_headers_arr = array();
    /**
     * @var $response_body string 返回的body
     */
    protected $response_body;
    /**
     * @var $response_http_code int 返回的http代码
     */
    protected $response_http_code;


    /**
     * 静态实例化对象，为了实现链式调用
     * @param $serialize_cookies string 序列化后的cookies，可空
     */
    public static function createInstance($serialize_cookies = '')
    {
        return new static($serialize_cookies);
    }

    /**
     * 实例化对象
     * @param $serialize_cookies string 序列化后的cookies，可空
     */
    public function __construct($serialize_cookies = '')
    {
        $this->cookies = new CookiesManager($serialize_cookies);
    }

    /**
     * 这个属性随意设置，相当于一个备注，在多线程中可以分配给他们一个唯一属性之类的
     * @param $remarks
     * @return $this
     */
    public function setRemarks($remarks)
    {
        $this->remarks = $remarks;
        return $this;
    }

    /**
     * 设定HTTP请求头中"Accept-Encoding: "的值，默认gzip。这使得能够解码响应的内容。 支持的编码有"identity"，"deflate"和"gzip"。如果为空字符串""，会发送所有支持的编码类型。
     * @param $encoding 'gzip','deflate','identity'
     * @return $this|false
     */
    public function setEncoding($encoding = 'gzip')
    {
        if (in_array($encoding, array('gzip', 'deflate', 'identity', ''))) {
            $this->encoding = $encoding;
            return $this;
        }
        return false;
    }

    /**
     * 设定指定域名的主机地址，类似于hosts，一旦设置，一直有效
     * @param $domain string 域名
     * @param $ip     string 主机地址
     * @param $port   string 主机端口 默认80，提示：有https的是 443 端口
     * @return $this
     */
    public function setResolve($domain, $ip, $port = '80')
    {
        $this->resolve = array("$domain:$port:$ip");
        return $this;
    }

    /**
     * 设置解析模式，一旦设置，一直有效
     * @param $value int 4为仅解析IPV4,6为仅解析IPV6，0为无所谓。默认为0
     * @return $this|false
     */
    public function setResolveMode($value = 0)
    {
        if ($value == 0) {
            $this->resolve_mode = CURL_IPRESOLVE_WHATEVER;
        } elseif ($value == 4) {
            $this->resolve_mode = CURL_IPRESOLVE_V4;
        } elseif ($value == 6) {
            $this->resolve_mode = CURL_IPRESOLVE_V6;
        } else {
            return false;
        }
        return $this;
    }

    /**
     * 设置HTTP版本，一旦设置，一直有效
     * @param $version string 有1.0,1.1,2.0
     * @return $this|false
     */
    public function setHttpVersion($version = '1.0')
    {
        if ($version == '1.0') {
            $this->http_version = CURL_HTTP_VERSION_1_0;
        } elseif ($version == '1.1') {
            $this->http_version = CURL_HTTP_VERSION_1_1;
        } elseif ($version == '2.0') {
            $this->http_version = CURL_HTTP_VERSION_2_0;
        } else {
            return false;
        }
        return $this;
    }

    /**
     * 开启自动重定向，一旦设置，一直有效
     * @param $num int 最大连续重定向次数，0则禁止重定向,默认20
     * @return void
     */
    public function setRedirect($num = 20)
    {
        $this->redirect_max_num = $num;
    }

    /**
     * 开关ssl验证，默认为关闭，一旦设置，一直有效
     * @param $bool
     * @return void
     */
    public function setSSLVerify($bool = true)
    {
        $this->ssl_verify = $bool;
    }

    /**
     * 伪装IP，一旦设置，一直有效
     * @param String $ip 空则随机获取一个
     * @return $this
     */
    public function setFakeIp($ip = "")
    {
        if ($ip === "") {
            $this->fake_ip = long2ip(mt_rand("607649792", "2079064063"));
        } else {
            $this->fake_ip = $ip;
        }
        return $this;
    }

    /**
     * 设置代理IP，为空则取消代理，一旦设置，一直有效
     * @param $proxy
     * @return $this
     */
    public function setProxy($proxy = '')
    {
        $this->proxy = $proxy;
        return $this;
    }

    /**
     * 设置超时时间，一旦设置，一直有效
     * @param $time
     * @return $this
     */
    public function setTimeOut($time)
    {
        $this->timeout = $time;
        return $this;
    }

    /**
     * 设置UA，一旦设置，一直有效
     * @param $value
     * @return $this
     */
    public function setUserAgent($value = 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.2)')
    {
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
    public function open($method, $url)
    {
        CookiesManager::checkUrl($url);
        $this->response_headers_arr = array();
        $this->request_headers      = array();
        $this->response_body        = '';
        $this->post_data            = '';
        $this->is_build_ch          = false;
        $this->url_parse            = parse_url($url);
        //重置
        $this->url    = $url;
        $this->method = $method;
        if (!empty($this->fake_ip)) {
            $this->setRequestHeader("X-Forwarded-For", $this->fake_ip);
            $this->setRequestHeader("X-Originating-IP", $this->fake_ip);
            $this->setRequestHeader("X-Remote-IP", $this->fake_ip);
            $this->setRequestHeader("X-Remote-Addr", $this->fake_ip);
            $this->setRequestHeader("X-Client-IP", $this->fake_ip);
            $this->setRequestHeader("Forwarded-For", $this->fake_ip);
            $this->setRequestHeader("Originating-IP", $this->fake_ip);
            $this->setRequestHeader("Remote-IP", $this->fake_ip);
            $this->setRequestHeader("Remote-Addr", $this->fake_ip);
            $this->setRequestHeader("Client-IP", $this->fake_ip);
        }
        return $this;
    }

    /**
     * 类内部自用的，CURLOPT_HTTPHEADER的时候用
     * @return array
     */
    protected function buildRequestHeadersArray()
    {
        $arr = array();
        foreach ($this->request_headers as $header) {
            $arr[] = $header['name'] . ': ' . $header['value'];
        }
        return $arr;
    }

    /**
     * 设置本次请求头，open后用，再次open后上一次的设置失效
     * @param $name
     * @param $value
     * @return $this
     */
    public function setRequestHeader($name, $value)
    {
        foreach ($this->request_headers as $k => $v) {
            if (equal($v['name'], $name)) {
                unset($this->request_headers[$k]);
            }
        }
        $this->request_headers[] = array('name' => trim($name), 'value' => trim($value));
        return $this;
    }

    /**
     * 批量添加headers，open后用，再次open后上一次的设置失效
     * @param $headers array 每个数组都是一条header name: value
     * @return $this
     */
    public function setRequestHeaders($headers)
    {
        foreach ($headers as $header) {
            $arr = explode(':', $header);
            if (count($arr) == 2) {
                $this->setRequestHeader($arr[0], $arr[1]);
            }
        }
        return $this;
    }

    /**
     * open后用，再次open后上一次的设置失效
     * @param $value
     * @return $this
     */
    public function setAccept($value = '*/*')
    {
        $this->setRequestHeader('Accept', $value);
        return $this;
    }

    /**
     * open后用，再次open后上一次的设置失效
     * @param $value
     * @return $this
     */
    public function setAcceptLanguage($value = 'zh-cn')
    {
        $this->setRequestHeader('Accept-Language', $value);
        return $this;
    }

    /**
     * open后用，再次open后上一次的设置失效
     * @param $value
     * @return $this
     */
    public function setContentType($value = 'application/x-www-form-urlencoded ')
    {
        $this->setRequestHeader('Content-Type', $value);
        return $this;
    }

    /**
     * open后用，再次open后上一次的设置失效
     * @param $value
     * @return $this
     */
    public function setReferer($value = '')
    {
        $this->setRequestHeader('Referer', $value);
        return $this;
    }

    /**
     * open后用，再次open后上一次的设置失效
     * @param $value
     * @return $this
     */
    public function setOrigin($value = '')
    {
        $this->setRequestHeader('Origin', $value);
        return $this;
    }

    /**
     * open后用，再次open后上一次的设置失效
     * @return $this
     */
    public function setXMLHttpRequest()
    {
        $this->setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        return $this;
    }

    /**
     * open后用，再次open后上一次的设置失效
     * @param $post_data string|array
     * @return $this
     */
    public function setPostData($post_data)
    {
        $this->post_data = $post_data;
        return $this;
    }

    /**
     * 设置并获取curl句柄，仅可设置1次，主要用于多线程里
     * @return resource|bool
     * @throws Exception
     */
    protected function buildCurlHandle()
    {
        if (!$this->is_build_ch) {
            $this->ch = curl_init();
            curl_setopt($this->ch, CURLOPT_URL, $this->url);
            curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $this->method);
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
            if (!empty($this->post_data)) {
                curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->post_data);
            }
            curl_setopt($this->ch, CURLOPT_RESOLVE, $this->resolve);
            curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, $this->ssl_verify);
            curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, $this->ssl_verify);
            curl_setopt($this->ch, CURLOPT_HTTP_VERSION, $this->http_version);
            curl_setopt($this->ch, CURLOPT_IPRESOLVE, $this->resolve_mode);
            curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($this->ch, CURLOPT_HEADER, TRUE);
            curl_setopt($this->ch, CURLOPT_ENCODING, $this->encoding);
            curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($this->ch, CURLINFO_HEADER_OUT, true);
            $this->is_build_ch = true;
            return $this->ch;
        } else {
            return false;
        }

    }

    /**
     * 获取CURL句柄
     * @return resource
     * @throws Exception
     */
    public function getCurlHandle()
    {
        if (!$this->is_build_ch) {
            $this->buildCurlHandle();
        }
        return $this->ch;
    }


    /**
     * 发送请求
     * @return $this
     * @throws Exception
     */
    public function send()
    {
        //在这用这个buildCurlHandle命名不太恰当，但是正好实现相应的功能了，不要在意那些细节
        $this->buildCurlHandle();
        $ret = curl_exec($this->ch);
        return $this->finishCh($ret);

    }

    /**
     * 解析返回信息并关闭句柄，多线程里内部用的
     * @param $ret string curl返回的原始数据
     * @return $this
     * @throws Exception
     */
    public function finishCh($ret)
    {
        $this->response_raw       = $ret;
        $this->request_header     = curl_getinfo($this->ch, CURLINFO_HEADER_OUT);
        $header_size              = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);
        $this->response_http_code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
        curl_close($this->ch);
        $this->response_headers = substr($this->response_raw, 0, $header_size);
        $this->response_body    = substr($this->response_raw, $header_size);
        $this->cookies->upH($this->response_headers, $this->url);
        if ($this->redirect_num < $this->redirect_max_num) {
            $location = $this->getResponseHeader('location');
            if ($location) {
                $this->redirect_num++;
                if ($location[0] == '/') {
                    $location = $this->url_parse['scheme'] . '://' . $this->url_parse['domain'].$location;
                }
                $this->open('GET', $location)->send();
            }

        }
        $this->redirect_num = 0;//已经跳转的次数归0
        return $this;
    }

    /**
     * 获取本次访问的方式
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * 获取本次访问的url
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * 获取本次访问的postdata
     * @return string
     */
    public function getPostData()
    {
        return $this->post_data;
    }

    /**
     * 获取返回的header
     * @param $name string 为空则获取全部header
     * @return array|mixed
     */
    public function getResponseHeader($name = '')
    {
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
     * 获取原生返回信息，包含头
     * @return string
     */
    public function getResponseRaw()
    {
        return $this->response_raw;
    }

    public function getRequestHeader()
    {
        return $this->request_header;
    }

    /**
     * 获取返回信息
     * @return string
     */
    public function getResponseBody()
    {
        return $this->response_body;
    }

    /**
     * 获取返回的http代码
     * @return int
     */
    public function getResponseHttpCode()
    {
        return $this->response_http_code;
    }

    /**
     * 取两个字符串中间的字符串，如果左边两边的字符串任何一个不存在则返回false
     * @param $str
     * @param $leftStr
     * @param $rightStr
     * @return false|string
     */
    public static function getSubstr($str, $leftStr, $rightStr)
    {
        $left  = strpos($str, $leftStr);
        $right = strpos($str, $rightStr, $left);
        if ($left === false || $right === false) return false;
        return substr($str, $left + strlen($leftStr), $right - $left - strlen($leftStr));
    }
}