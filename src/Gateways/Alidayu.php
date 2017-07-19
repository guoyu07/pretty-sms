<?php

namespace Godruoyi\PrettySms\Gateways;

use Godruoyi\PrettySms\Support\Loader;
use Godruoyi\PrettySms\Support\Collection;
use Godruoyi\PrettySms\Support\HttpClient;
use Godruoyi\PrettySms\Support\Response;

class Alidayu
{
    /**
     * HttpClient Instance
     * 
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * API接口名称
     * 
     * @var string
     */
    private $method          = 'alibaba.aliqin.fc.sms.num.send'; 
    
    /**
     * 正式环境
     * 
     * @var string
     */
    private $production_url  = 'http://gw.api.taobao.com/router/rest';  
    
    /**
     * 正式环境s
     * 
     * @var string
     */
    private $production_urls = 'https://eco.taobao.com/router/rest';
    
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
        return $this->httpClient->get($this->buildRequestUrl($request, $globalconfig));
    }

    /**
     * Process Response
     * 
     * @param  mixed $response
     * @return Response
     */
    public function processResponse($response)
    {
        $responseJson = json_decode($response, true);

        $successKey = 'alibaba_aliqin_fc_sms_num_send_response';
        $errorKey = 'error_response';

        if (isset($responseJson[$errorKey])) {

            $loader = new Loader();
            $errorcodes = $loader->load(__DIR__ . '/errorcode.conf.php');
            if (!empty($responseJson[$errorKey]['sub_code']) && !empty($errorcodes[$responseJson[$errorKey]['sub_code']])) {
                return Response::error($errorcodes[$responseJson[$errorKey]['sub_code']]);
            }
            return Response::error($responseJson[$errorKey]['msg']);
        } elseif (isset($responseJson[$successKey])) {
            return Response::success();
        }

        return Response::error('未知错误[阿里大鱼]');
    }

    /**
     * Build Request By request parameters
     * 
     * @param  Collection $request     
     * @param  Collection $globalconfig
     * @return string
     */
    protected function buildRequestUrl(Collection $request, Collection $globalconfig)
    {
        $requestData = array(
            'method'             => $this->method,
            'format'             => 'json',
            'app_key'            => $globalconfig['appKey'],
            'timestamp'          => date("Y-m-d H:i:s"),
            'v'                  => '2.0',
            'sign_method'        => 'md5',
            'sms_type'           => 'normal',
            'sms_free_sign_name' => $globalconfig['sign_name'],
            'sms_param'          => $request['template_data'],
            'rec_num'            => $request['to'],
            'sms_template_code'  => $request['template']
        );
        $requestData['sign'] = $this->sign($globalconfig, $requestData);
        
        $requestUrl = $this->production_url .'?'. http_build_query($requestData);

        return $requestUrl;
    }

    /**
     * Sign
     * 
     * @param  Collection $globalconfig
     * @param  array $params
     * @return string        
     */
    private function sign(Collection $globalconfig, $params)
    {
        if (!empty($params)) {
            ksort($params);

            $sign = $globalconfig['secretKey'];
            foreach ($params as $key => $value) {
                $sign .= $key.$value;
            }
            $sign .= $globalconfig['secretKey'];

            return strtoupper(md5($sign));
        }
    }
}