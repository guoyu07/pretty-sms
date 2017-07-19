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

        foreach ((array) $middlewares as $middleware) {
            $middleware = $this->normalize($middleware);
            if (array_search($middleware, $this->aliasMiddleware[$name]) === false) {
                array_unshift($this->aliasMiddleware[$name], $middleware);
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

        foreach ((array) $middlewares as $middleware) {
            $middleware = $this->normalize($middleware);
            if (empty($this->aliasMiddleware[$name]) || 
                array_search($middleware, $this->aliasMiddleware[$name]) === false) {
                
                $this->aliasMiddleware[$name][] = $middleware;
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
            $response = $this->throughPipeline();
        // try {
        // } catch (Exception $e) {
        //     $response = $this->readerException($e);

        // } catch (Throwable $t) {
        //     $response = $this->readerException($t);
        // }

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
        foreach ($config->get('middleware', []) as $middleware) {
            $this->pushMiddleware($middleware);
        }

        foreach ($config->get('middlewareAlias', []) as $alias => $middleware) {
            $alias = $this->nullDefinition($alias);
            $this->pushAliasMiddleware($alias, $middleware);
        }

        return $config;
    }

    /**
     * Reader Exception to response
     * 
     * @param  mixed $e
     * @return Response
     */
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

        $this->singleton('proxy_service', function($app) {
            return $app->make(PrettyManager::class);
        });
    }

    /**
     * Send Request Paramter Thorugh gieved middleware
     *
     * @return Response
     */
    protected function throughPipeline()
    {
        return (new Pipeline($this))->send($this->app['request'])
                ->through($this->middleware)
                ->then($this->destination());
    }

    /**
     * Get Last Clouse
     *
     * @return mixed
     */
    protected function destination()
    {
        return function ($requestParams) {
            return $this['proxy_service']->handle($requestParams);
        };
    }

    /**
     * Check gieved name has empty
     * 
     * @param  string $name
     * @return string
     */
    public function nullDefinition($name)
    {
        if (empty($name)) {
            throw new Exceptions\InvalidArgumentException('Invalid Argument Null DEfined');
        }

        return trim($name);
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
        return $this->aliasMiddleware;
    }
}
