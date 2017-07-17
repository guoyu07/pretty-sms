<?php

namespace Godruoyi\PrettySms\Support;

class Response
{
    protected $hasSuccess = true;

    protected $error = '';

    protected $code = 0;

    protected $proxyName = '';

    /**
     * Make a Reponse instrance
     * 
     * @param  boolean $isSuccess
     * @param  string  $error    
     * @param  integer $code     
     * @return static
     */
    public function __construct($isSuccess = true, $error = '发送成功', $code = 0)
    {
        $this->hasSuccess = (bool) $isSuccess;
        $this->error = $error;
        $this->code = 0 | $code;
    }

    /**
     * Make a success response instance
     * 
     * @param  string $msg 
     * @return static
     */
    public static function success($msg = '发送成功')
    {
        return new static(true, $msg, 0);
    }

    /**
     * Make a error response instance
     * 
     * @param  string $msg 
     * @return static
     */
    public static function error($msg, $code = -1)
    {
        return new static(false, $msg, $code);
    }

    /**
     * Has success
     * 
     * @return boolean
     */
    public function hasSuccess()
    {
        return $this->hasSuccess;
    }

    /**
     * Get error Code
     * 
     * @return int
     */
    public function getErrorCode()
    {
        return $this->code;
    }

    /**
     * Get error message
     * 
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->error;
    }

    /**
     * Get proxy name
     * 
     * @return string
     */
    public function getProxyName()
    {
        return $this->proxyName;
    }

    /**
     * Set proxy name
     * 
     * @param string $name
     */
    public function setProxyName($name = '')
    {
        $this->proxyName = $name;

        return $this;
    }
}