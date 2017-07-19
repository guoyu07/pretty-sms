<?php

namespace Godruoyi\PrettySms\Middleware;

use Closure;
use Godruoyi\PrettySms\Client;
use Godruoyi\PrettySms\Support\Collection;
use Godruoyi\PrettySms\Support\Response;

class TencentyunMiddleware
{
    /**
     * Client Instance
     * 
     * @var Client
     */
    protected $app;

    /**
     * Register Client
     * 
     * @param Client $app
     */
    public function __construct(Client $app)
    {
        $this->app = $app;
    }

    /**
     * @param  array    $requestParams
     * @param  Closure  $next         
     * @return function               
     */
    public function handle(Collection $request, Closure $next)
    {
        //php5.3只能这样写，我有什么办法
        $templateId = $request->get('template');
        $template = $request->get('template_data');
        $appkey = $this->app['config']->get('configs.tencentyun.appKey');
        $signName = $this->app['config']->get('configs.tencentyun.sign_name');
        $appId = $this->app['config']->get('configs.tencentyun.appId');

        if (empty($templateId)) {
            return Response::error('[腾讯云]模板ID不能为空', -1000);
        } elseif (empty($template)) {
            return Response::error('[腾讯云]模板参数不能为空', -1000);
        } elseif (empty($appkey)) {
            return Response::error('[腾讯云]APPKEY不能为空', -1000);
        } elseif (empty($signName)) {
            return Response::error('[腾讯云]签名不能为空', -1000);
        } elseif (empty($appId)) {
            return Response::error('[腾讯云]APPID不能为空', -1000); 
        }
        
        $request = $this->rebuildRequest($request);

        return $next($request);
    }

    /**
     * 根据平台模板ID获取腾讯云模板ID
     * 
     * @param  Collection $request
     * @return Collection
     */
    public function rebuildRequest(Collection $request)
    {
        $oldTemplateId = $request->get('template');

        $configs = $this->app->make('dayuw\api\app\Service\TemplateConfigService')
            ->findByAttributes(array(
                'type' => 'tencentcloud',
                'source_id' => $oldTemplateId
            ));

        if (!$configs || empty($configs->target_id)) {
            throw new \Exception("[腾讯云]未配置的模板ID");
        }

        if (empty($configs->tencent_cloud_setting)) {
            throw new \Exception("[腾讯云]服务端未指定模板顺序");
        }

        $request['template'] = $configs->target_id;
        $request['template_data'] = $this->buildTemplateData($request['template_data'], $configs->tencent_cloud_setting);

        return $request;
    }

    /**
     * 根据腾讯云模板ID
     * 
     * @param  mixed $templateData
     * @param  mixed $sort        
     * @return mixed              
     */
    public function buildTemplateData($templateData, $sort)
    {
        $templateData = json_decode($templateData, true);
        $sort = json_decode($sort, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("[腾讯云]模板参数格式化失败");
        }

        $realtemplateData = array();

        foreach ($sort as $key) {
            if (empty($templateData[$key])) {
                throw new \Exception("[腾讯云]模板参数缺少或为空({$key})");
            }
            $realtemplateData[] = $templateData[$key];
        }

        return $realtemplateData;
    }
}