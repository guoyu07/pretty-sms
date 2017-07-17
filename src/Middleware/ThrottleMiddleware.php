<?php

namespace Godruoyi\PrettySms\Middleware;

use Closure;
use Godruoyi\PrettySms\Support\Response;
use Godruoyi\PrettySms\Support\Collection;

/**
 * 该中间件定义了terminate方法， 在验证失败、发送失败时，减少
 * redis的自增次数，必须注册为单列的
 * 
 */
class ThrottleMiddleware
{
    /**
     * @param  array    $requestParams
     * @param  Closure  $next         
     * @return function               
     */
    public function handle(Collection $request, Closure $next)
    {
        return false;
        // return $next($this->request);
    }

    /**
     * Terminate Response
     * 
     * @param  Response $response
     * @return void
     */
    public function terminate(Response $response)
    {
        //Cache::decrement('key', 1);
    }
}