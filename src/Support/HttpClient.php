<?php

namespace Godruoyi\PrettySms\Support;

class HttpClient
{
    /**
     * Current CURL instance
     * 
     * @var null
     */
    private $_curl = NUll;

    /**
     * Instance
     * 
     * @var static
     */
    private static $_instance = NULL;

    /**
     * Make Instance
     * 
     * @return static
     */
    public static function make()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Send a Http Get request
     * 
     * @param  string $url   
     * @param  mixed $params
     * @return mixed        
     */
    public function get($url, $params = null)
    {
        return $this->curl($url, $params, 'GET');
    }

    /**
     * Send a Http Post request
     * 
     * @param  string $url   
     * @param  mixed $params
     * @return mixed        
     */
    public function post($url, $params = null, array $header = array())
    {
        return $this->curl($url, $params, 'POST', $header);
    }

    /**
     * CURL请求
     * 
     * @param  string $url       请求地址
     * @param  array|string $data 请求参数， 当为get请求时会自动拼接在URL后面， post时放入请求体
     * @param  string $method    
     * @param  array  $header   
     * @return false|json
     */
    public function curl($url, $data = NULL, $method = 'GET', array $header = array())
    {
        return $this->_doCurl($url, $data, $method, $header);
    }

    /**
     * Do curl
     * 
     * @param  string $url      
     * @param  mixed $data     
     * @param  string $method   
     * @param  array  $header   
     * @param  int &$httpCode
     * @return mixed           
     */
    private function _doCurl($url, $data, $method = 'GET', array $header = array())
    {
        $url = $this->_completeUrl($url, $data, $method);
        $this->_curl = curl_init();

        curl_setopt($this->_curl, CURLOPT_URL, $url);
        curl_setopt($this->_curl, CURLOPT_POST, true);
        curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->_curl, CURLOPT_CUSTOMREQUEST, $method);
        if(!is_null($header) && !empty($header)){
            curl_setopt($this->_curl, CURLOPT_HTTPHEADER, $header);
        }
        curl_setopt($this->_curl, CURLOPT_CONNECTTIMEOUT, $this->_connect_time);
        curl_setopt($this->_curl, CURLOPT_TIMEOUT, $this->_connect_time);

        if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
            curl_setopt($this->_curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        }

        curl_setopt($this->_curl, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书  
        curl_setopt($this->_curl, CURLOPT_SSL_VERIFYHOST, 1); // 检查证书中是否设置域名
        
        $curl_get = curl_exec($this->_curl);

        if (FALSE === $curl_get) {
            throw new \Exception(curl_error($this->_curl));
        }
        curl_close($this->_curl);
        return $curl_get;
    }

    /**
     * 拼接请求URL
     * 
     * @param  string $url   
     * @param  array $data  
     * @param  string $method
     * @return string        
     */
    public function _completeUrl($url, $data, $method)
    {
        if (strtoupper($method) === 'GET' && !empty($data)) {
            $data = http_build_query($data);
            if (strpos('?', $url) === false) {
                $url = $url . '?' . $data;

            } else {
                $url = $url . '&' . $data;
            }
        }
        return $url;
    }
}