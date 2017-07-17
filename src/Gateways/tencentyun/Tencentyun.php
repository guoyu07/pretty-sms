<?php

namespace Godruoyi\PrettySms\proxys\tencentyun;

use Godruoyi\PrettySms\Support\Collection;
use Godruoyi\PrettySms\Support\HttpClient;
use Godruoyi\PrettySms\Support\Loader;
use Godruoyi\PrettySms\Support\Response;

class Tencentyun
{
    const NOTIFY_URL = 'https://yun.tim.qq.com/v5/tlssmssvr/sendsms';

    /**
     * Random
     *
     * @var string
     */
    private $random;

    /**
     * conter code
     *
     * @var string
     */
    private $nationcode = '86';

    /**
     * Http client instance
     * 
     * @var [HttpClient]
     */
    private $httpClient;

    /**
     * Register Http Client Instance
     * 
     * @param HttpClient $httpClient
     */
    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Start Send sms by Alidayu
     * 
     * @param  Collection $request      
     * @param  Collection $globalconfig 
     * @return mixed
     */
    public function handle(Collection $request, Collection $globalconfig)
    {
        // return Response::success();
        $url = sprintf(self::NOTIFY_URL . '?sdkappid=%s&random=%s', $globalconfig['appId'], $this->randomCode());
        $currentTime = time();
        $params = array(
            'tel' => array(
                'nationcode' => $this->nationcode,
                'mobile' => $request['to'],
            ),
            'sign' => $globalconfig['sign_name'],
            'tpl_id' => $request['template'],
            'params' => is_array($request['template_data']) ? $request['template_data'] : json_decode($request['template_data'], true),
            'sig' => $this->sig($globalconfig['appKey'], $request['to'], $currentTime),
            'time' => $currentTime,
            'extend' => '',
            'ext' => ''
        );

        return $this->httpClient->post($url, json_encode($params), array('Content-Type: application/json'));
    }

    /**
     * Process Response
     * 
     * @param  mixed $response
     * @return Response
     */
    public function processResponse($response)
    {
        $result = json_decode($response, true);

        if ($result && isset($result['result'])) {
            if ($result['result'] === 0) {
                return Response::success();
            }
            $loader = new Loader();
            $errorCodes = $loader->load(__DIR__ . '/errorcode.conf.php');
            if (!empty($result['result']) && !empty($errorCodes[$result['result']])) {
                return Response::error($errorCodes[$result['result']]);
            }
        }
        return Response::error($result['errmsg'] || '腾讯云未知错误，请联系管理员');
    }

    /**
     * Sig key by current time
     *
     * @param  string $currentTime
     * @return string
     */
    private function sig($appkey, $to, $currentTime)
    {
        $key = sprintf('appkey=%s&random=%s&time=%s&mobile=%s',
            $appkey,
            $this->randomCode(),
            $currentTime,
            $to
        );

        return hash("sha256", $key);
    }

    /**
     * Get rrandom
     *
     * @return string
     */
    private function randomCode()
    {
        if (!$this->random) {
            $this->random = rand(100000, 999999);
        }
        return $this->random;
    }
}