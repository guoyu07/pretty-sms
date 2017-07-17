<?php

namespace Godruoyi\PrettySms\Middleware;

use Closure;
use Godruoyi\PrettySms\Support\Collection;
use Godruoyi\PrettySms\Support\Response;

class GlobalValidateMiddleware
{
    /**
     * Collection Instance
     * 
     * @var Collection
     */
    protected $request;

    /**
     * @param  array    $request
     * @param  Closure  $next         
     * @return function               
     */
    public function handle(Collection $request, Closure $next)
    {
        $this->request = $request;

        return $next($this->request);
    }
}