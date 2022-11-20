<?php
/**
 * author      : Administrator
 * creatTime   : 2022/11/19 22:28
 * description :
 */

namespace Ibazhe\PhpCurl;

use Exception;

class MultiCurl
{

    /**
     * 线程池内的CURL实例
     * @var $curls Curl[]
     */
    public $curls = array();


    ///**
    // * 静态方法实例化对象，为了实现链式调用
    // * @return static
    // */
    //public static function createInstance(){
    //    return new static();
    //}

    /**
     * 添加一个CURL对象到线程池中，相同实例不可添加多次
     * @param $ch Curl
     * @return int 返回线程id
     * @throws Exception
     */
    public function push($ch) {
        if(in_array($ch,$this->curls,true)){
            throw new Exception('线程池中有相同实例');
        }
        $index         = count($this->curls);
        $this->curls[] = $ch;
        return $index;
    }

    /**
     * 执行线程池里的curl
     * @return bool
     * @throws Exception
     */
    public function exec() {
        if (!is_array($this->curls) or count($this->curls) == 0) {
            return false;
        }
        $handle = curl_multi_init();
        //准备分配线程
        foreach ($this->curls as $curl) {
            //向curl批处理会话中添加单独的curl句柄
            curl_multi_add_handle($handle, $curl->getCurlHandle());
        }
        $active = null;
        do {
            //运行当前 cURL 句柄的子连接
            $mrc = curl_multi_exec($handle, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc == CURLM_OK) {
            //等待所有cURL批处理中的活动连接
            if (curl_multi_select($handle) != -1) {
                usleep(100);
            }
            do {
                //运行当前 cURL 句柄的子连接
                $mrc = curl_multi_exec($handle, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }
        foreach ($this->curls as $curl) {
            $error = curl_error($curl->getCurlHandle());
            if ($error === '') {
                //如果没有报错则将获取到的字符串添加到数组中
                $ret = curl_multi_getcontent($curl->getCurlHandle());
            } else {
                $ret = $error;
            }
            //移除并关闭curl该句柄资源
            curl_multi_remove_handle($handle, $curl->getCurlHandle());
            $curl->finishCh($ret);
        }
        //关闭cURL句柄
        curl_multi_close($handle);
        return true;
    }

    /**
     * 重置线程池
     * @return void
     */
    public function reset() {
        $this->curls = array();
    }


}