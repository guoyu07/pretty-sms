<?php

namespace Godruoyi\PrettySms;

use Closure;
use Exception;
use Throwable;
use Godruoyi\Container\Container;
use Godruoyi\PrettySms\Support\Collection;
use Godruoyi\PrettySms\Support\Pipeline;
use Godruoyi\PrettySms\Support\Response;

class Client extends Container
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        Middleware\GlobalValidateMiddleware::class
    ];

    /**
     * The application's alias middleware.
     *
     * @var array
     */
    protected $aliasMiddleware = [];

    /**
     * The array of terminating callbacks.
     *
     * @var array
     */
    protected $terminatingCallbacks = array();

    /**
     * mark Container Instance
     *
     * @param array|string|null $config
     */
    public function __construct($config = null)
    {
        $this->registerServiceProvider();
        $this->registerBase();

        $this['config'] = $this->parseConfig($config);
    }

    /**
     * Add a new global middleware to beginning of the stack if it does not already exist.
     * 
     * @param  string|array $middleware
     * @return static
     */
    public function prependMiddleware($middlewares)
    {
        foreach ((array) $middlewares as $middleware) {
            $middleware = $this->normalize($middleware);
            if (array_search($middleware, $this->middleware) === false) {
                array_unshift($this->middleware, $middleware);
            }
        }

        return $this;
    }

    /**
     * Add a new alias middleware to beginning of the stack if it does not already exist.
     *
     * @param  string  $name
     * @param  string|array  $middleware
     * @return $this
     */
    public function prependAliasMiddleware($name, $middlewares)
    {
        $name = $this->nullDefinition($name);

        if (!empty($this->aliasMiddleware[$name])) {
            foreach ((array) $middlewares as $middleware) {
                $middleware = $this->normalize($middleware);
                if (array_search($middleware, $this->aliasMiddleware[$name]) === false) {
                    array_unshift($this->aliasMiddleware[$name], $middleware);
                }
            }
        }

        return $this;
    }

    /**
     * Add a new middleware to end of the stack if it does not already exist.
     *
     * @param  string  $middleware
     * @return $this
     */
    public function pushMiddleware($middleware)
    {
        if (array_search($middleware, $this->middleware) === false) {
            $this->middleware[] = $middleware;
        }

        return $this;
    }

    /**
     * Add a new alias middleware to end of the stack if it does not already exist.
     *
     * @param  string  $middlewares
     * @return $this
     */
    public function pushAliasMiddleware($alias, $middlewares)
    {
        $name = $this->nullDefinition($alias);

        if (!empty($this->aliasMiddleware[$name])) {
            foreach ((array) $middlewares as $middleware) {
                $middleware = $this->normalize($middleware);
                if (array_search($middleware, $this->aliasMiddleware[$name]) === false) {
                    $this->aliasMiddleware[$name][] = $middleware;
                }
            }
        }

        return $this;
    }

    /**
     * Handle
     *
     * @return Response
     */
    public function send(array $requestParams = array())
    {
        $this['request'] = Collection::make($requestParams);
        try {
            $response = $this->throughPipeline();
        } catch (Exception $e) {
            $response = $this->readerException($e);

        } catch (Throwable $t) {
            $response = $this->readerException($t);
        }

        return $response;
    }

    /**
     * Register a terminating callback with the application.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function terminating(Closure $callback)
    {
        $this->terminatingCallbacks[] = $callback;

        return $this;
    }

    /**
     * terminate
     *
     * @param  Response $response
     * @return void
     */
    public function terminate(Response $response)
    {
        $middlewares = array_merge($this->middleware, array_values($this->aliasMiddleware));

        foreach ($middlewares as $middleware) {
            if (! is_string($middleware)) {
                continue;
            }

            if (($instance = $this->make($middleware)) && method_exists($instance, 'terminate')) {
                $instance->terminate($response);
            }
        }

        foreach ($this->terminatingCallbacks as $terminating) {
            $this->call($terminating);
        }
    }

    /**
     * Parse config to Collenction
     *
     * @param  array|string|null $config
     * @return Collection
     */
    protected function parseConfig($config)
    {
        $config = Collection::make($this['loader']->load($config));
        foreach ($config->get('globalMiddleware', []) as $middleware) {
            $this->pushMiddleware($middleware);
        }

        $this->pushMiddleware($config->get('middleware', array()));

        return $config;
    }

    protected function readerException($e)
    {
        return Response::error($e->getMessage());
    }

    /**
     * Register Base bind
     *
     * @return void
     */
    protected function registerBase()
    {
        self::$instance = $this;
        $this->instance('app', $this);
        $this->instance('Godruoyi\PrettySms\Client', $this);
    }

    /**
     * Register Base service provider
     *
     * @return void
     */
    protected function registerServiceProvider()
    {
        $this->singleton('loader', function($app){
            return $app->make(Support\Loader::class);
        });

        $this->singleton('proxy_service', function($app){
            return $app->make(PrettyManager:class);
        });
    }

    /**
     * Send Request Paramter Thorugh gieved middleware
     *
     * @return Response
     */
    protected function throughPipeline()
    {
        // 通过全局限制中间件，最后在通过proxy_service来执行handle
        $pipeLine = new Pipeline($this);
        return $pipeLine->send($this->app['request'])
                ->through($this->globalMiddleware)
                ->then($this->destination());
    }

    /**
     * Last Middleware
     *
     * @return mixed
     */
    protected function destination()
    {
        // PHP5.3不支持闭包里调用$this， 只能采用use写入闭包，但这种方式只能访问修饰符为
        // public的属性或方法
        $self = $this;
        return function ($requestParams) use ($self) {
            // $self->proviteMethod(); //Error
            // $self->protectedMethod(); //Error
            return $self['proxy_service']->handle($requestParams);
        };
    }

    /**
     * Get all global middleware
     *
     * @return Collenction
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }

    /**
     * Get all alias middleware
     *
     * @return Collenction
     */
    public function getAliasMiddleware()
    {
        return $this->aliasMiddlewares;
    }


}
