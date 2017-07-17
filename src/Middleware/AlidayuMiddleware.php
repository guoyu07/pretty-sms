<?php

namespace Godruoyi\PrettySms\Middleware;

use Closure;
use Godruoyi\PrettySms\Kernel;
use Godruoyi\PrettySms\Support\Collection;
use Godruoyi\PrettySms\Support\Response;

class AlidayuMiddleware
{
    /**
     * Kernel Instance
     * 
     * @var Kernel
     */
    protected $app;

    /**
     * Register Kernel
     * 
     * @param Kernel $app
     */
    public function __construct(Kernel $app)
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
        $appkey = $this->app['config']->get('configs.alidayu.appKey');
        $signName = $this->app['config']->get('configs.alidayu.sign_name');
        $secretKey = $this->app['config']->get('configs.alidayu.secretKey');
        
        if (empty($templateId)) {
            return Response::error('[阿里大鱼]模板ID不能为空', -1000);
        } elseif (empty($template)) {
            return Response::error('[阿里大鱼]模板参数不能为空', -1000);
        } elseif (empty($appkey)) {
            return Response::error('[阿里大鱼]APPKEY不能为空', -1000);
        } elseif (empty($signName)) {
            return Response::error('[阿里大鱼]签名不能为空', -1000);
        } elseif (empty($secretKey)) {
            return Response::error('[阿里大鱼]SECRETKEY不能为空', -1000); 
        }

        $request = $this->rebuildRequest($request);

        return $next($request);
    }

    /**
     * 根据平台模板ID重新构建阿里大鱼模板ID
     * 
     * @param  Collection $request
     * @return Collection
     */
    public function rebuildRequest(Collection $request)
    {
        //wl平台模板ID => alidayu模板ID
        $oldTemplateId = $request->get('template');

        $configs = $this->app->make('dayuw\api\app\Service\TemplateConfigService')
            ->findByAttributes(array(
                'type' => 'alidayu',
                'source_id' => $oldTemplateId
            ));

        if (!$configs || empty($configs->target_id)) {
            throw new \Exception("[阿里大鱼]未配置的模板ID");
        }
        $request['template'] = $configs->target_id;

        return $request;
    }
}