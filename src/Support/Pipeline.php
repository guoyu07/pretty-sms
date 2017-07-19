<?php

namespace Godruoyi\PrettySms\Support;

use Closure;
use RuntimeException;
use Godruoyi\PrettySms\Client;

class Pipeline
{
    /**
     * The container implementation.
     *
     * @var Client
     */
    protected $container;

    /**
     * The object being passed through the pipeline.
     *
     * @var mixed
     */
    protected $passable;

    /**
     * The array of class pipes.
     *
     * @var array
     */
    protected $pipes = array();

    /**
     * The method to call on each pipe.
     *
     * @var string
     */
    protected $method = 'handle';

    /**
     * Create a new class instance.
     *
     * @param  Client
     * @return void
     */
    public function __construct(Client $container = null)
    {
        $this->container = $container;
    }

    /**
     * Set the object being sent through the pipeline.
     *
     * @param  mixed  $passable
     * @return $this
     */
    public function send($passable)
    {
        $this->passable = $passable;

        return $this;
    }

    /**
     * Set the array of pipes.
     *
     * @param  array|mixed  $pipes
     * @return $this
     */
    public function through($pipes)
    {
        $this->pipes = is_array($pipes) ? $pipes : func_get_args();

        return $this;
    }

    /**
     * Set the method to call on the pipes.
     *
     * @param  string  $method
     * @return $this
     */
    public function via($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Run the pipeline with a final destination callback.
     *
     * @param  \Closure  $destination
     * @return mixed
     */
    public function then(Closure $destination)
    {
        $firstSlice = $this->getInitialSlice($destination);

        $callable = array_reduce(
            array_reverse($this->pipes), $this->getSlice(), $firstSlice
        );

        return $callable($this->passable);
    }

    /**
     * Get a Closure that represents a slice of the application onion.
     *
     * @return \Closure
     */
    protected function getSlice()
    {
        $method = $this->method;
        $app = $this->getContainer();

        return function ($stack, $pipe) use ($method, $app) {
            return function ($passable) use ($stack, $pipe, $method, $app) {
                if ($pipe instanceof Closure) {
                    // If the pipe is an instance of a Closure, we will just call it directly but
                    // otherwise we'll resolve the pipes out of the container and call it with
                    // the appropriate method and arguments, returning the results back out.
                    return $pipe($passable, $stack);
                } elseif (! is_object($pipe)) {

                    // If the pipe is a string we will parse the string and resolve the class out
                    // of the dependency injection container. We can then build a callable and
                    // execute the pipe function giving in the parameters that are required.
                    // $pipe = $this->getContainer()->make($pipe);
                    $pipe = $app->make($pipe);

                    $parameters = array($passable, $stack);
                } else {
                    // If the pipe is already an object we'll just make a callable and pass it to
                    // the pipe as-is. There is no need to do any extra parsing and formatting
                    // since the object we're given was already a fully instantiated object.
                    $parameters = array($passable, $stack);
                }

                // return $pipe->{$this->method}(...$parameters);
                return $pipe->{$method}($parameters[0], $parameters[1]);
            };
        };
    }

    /**
     * Get the initial slice to begin the stack call.
     *
     * @param  \Closure  $destination
     * @return \Closure
     */
    protected function getInitialSlice(Closure $destination)
    {
        return function ($passable) use ($destination) {
            return $destination($passable);
        };
    }

    /**
     * Get the container instance.
     *
     * @return \Illuminate\Contracts\Container\Container
     * @throws \RuntimeException
     */
    protected function getContainer()
    {
        if (! $this->container) {
            throw new RuntimeException('A container instance has not been passed to the Pipeline.');
        }

        return $this->container;
    }
}