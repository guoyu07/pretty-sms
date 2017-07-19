<?php

namespace Godruoyi\PrettySms;

use Closure;
use RuntimeException;
use Godruoyi\PrettySms\Client;
use Godruoyi\PrettySms\Support\Response;
use Godruoyi\PrettySms\Support\Collection;
use Godruoyi\PrettySms\Support\Pipeline;
use Godruoyi\PrettySms\Support\ConsistentHash;

class PrettyManager
{
    /**
     * Container Instance
     *
     * @var Client
     */
    public $app;

    /**
     * App instance prifix
     *
     * @var string
     */
    public $instanceKey = 'proxy';

    /**
     * Mark Instance
     *
     * @param Client $app
     */
    public function __construct(Client $app)
    {
        $this->app = $app;
    }

    /**
     * Start Send SMS by gieved params
     *
     * @param  Collection|null $requestParams
     * @return Response
     */
    public function handle(Collection $requestParams = null)
    {
        //该request是通过中间件检测后的，可能存在改动，故重置request参数
        $this->app['request'] = $requestParams;
        $name = $this->getProxyNameForRequest();

        $middlewares = Collection::make($this->app->getAliasMiddleware())->get($name, array());

        //通过全局中间件后，在指定其通过局部中间件
        $response = (new Pipeline($this->app))->send($this->app['request'])
                ->through($middlewares)
                ->then($this->ultimate($name));

        //解析返回结果
        $response = $this->parseResponse($name, $response);

        return $response;
    }

    /**
     * Resolve CURL Result to Response
     *
     * @param  string $name
     * @param  string|boolean|null|Response $response
     * @return Response
     */
    public function parseResponse($name, $response)
    {
        //默认情况下，我们只处理响应类型为true、false、null、及空字符串或Response实列的响应
        //其他情况的响应应又代理商自行处理
        if (!$response) {
            return $this->buildErrorResponse()->setProxyName($name);
        } elseif (true === $response) {
            return $this->buildSuccessResponse()->setProxyName($name);
        } elseif ($response instanceof Response) {
            return $response->setProxyName($name);
        }

        $instance = $this->app[$this->instanceKey . $name];
        if (method_exists($instance, 'processResponse')) {
            $response = $instance->processResponse($response);
            if ($response && ($response instanceof Response)) {
                return $response->setProxyName($name);
            }
        }

        throw new RuntimeException("不能解析代理商[{$name}]的返回类型，添加方法processResponse处理。");
    }

    /**
     * ultimate
     *
     * @param  string $name
     * @return mixed
     */
    protected function ultimate($name)
    {
        $container = $this;
        return function ($request) use ($name, $container) {
            $privateKey = $container->instanceKey . $name;
            $container->app->instance($privateKey, $container->initialProxyForName($name));

            if (!method_exists($container->app[$privateKey], 'handle')) {
                throw new RuntimeException("代理商[{$name}]不存在handle方法");
            }

            $globalconfig = $container->app['config']->get('configs.' . $name);
            if (! $globalconfig instanceof Collection) {
                $globalconfig = new Collection($globalconfig);
            }

            return $container->app[$privateKey]->handle($request, $globalconfig);
        };
    }

    /**
     * Build Success Response
     *
     * @return Response
     */
    protected function buildSuccessResponse()
    {
        return Response::success();
    }
    /**
     * Build Success Response
     *
     * @return Response
     */
    protected function buildErrorResponse()
    {
        return Response::error('发送失败');
    }

    /**
     * Prase Proxy name for gieved request parameter
     *
     * @return string
     */
    protected function getProxyNameForRequest()
    {
        if ($this->app['config']->get('hash')) {
            $proxyerName = $this->buildProxyNameForHash($this->app['request']->get('mobiles'));
        } else {
            $proxyerName = $this->buildProxyNameForWeights($this->app['config']->get('weights'));
        }

        return $proxyerName;
    }

    /**
     * initial Proxy instance For gieved Name
     *
     * @param string $proxyName
     * @return mixed
     */
    public function initialProxyForName($proxyName)
    {
        if ($this->app['config']->has('alias.' . strtolower($proxyName))) {
            return $this->app->make($this->app['config']->get('alias.' . $proxyName));
        }

        throw new RuntimeException("不存在的别名[$proxyName]");
    }

    /**
     * Build Proxy Name By mobile hsah
     *
     * @param string $mobiles
     * @return string
     */
    protected function buildProxyNameForHash($mobiles)
    {
        $consistentHash = new ConsistentHash();
        $consistentHash->addNodes(array_values($this->app['config']->get('active')));
        return $consistentHash->getNode($mobiles);
    }

    /**
     * Build Proxy Name By Weights
     *
     * @param  array  $weights
     * @return string
     */
    protected function buildProxyNameForWeights(array $weights)
    {
        arsort($weights);

        return key($weights);
    }
}
