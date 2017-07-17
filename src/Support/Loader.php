<?php

namespace Godruoyi\PrettySms\Support;

class Loader
{
    /**
     * Load gieved path to array
     * 
     * @param  string|array|null $path
     * @return array      
     */
    public function load($path)
    {
        if (is_string($path) && $this->exists($path)) {
            return $this->getRequire($path);
        }

        if (is_null($path) || !is_array($path)) {
            return array();
        }

        return $path;
    }

    /**
     * Determine if a file or directory exists.
     *
     * @param  string  $path
     * @return bool
     */
    public function exists($path)
    {
        return file_exists($path);
    }

    /**
     * Get the returned value of a file.
     *
     * @param  string  $path
     * @return mixed
     *
     * @throws \Exception
     */
    public function getRequire($path)
    {
        if ($this->isFile($path)) {
            return require $path;
        }

        throw new \Exception("File does not exist at path {$path}");
    }

    /**
     * Determine if the given path is a file.
     *
     * @param  string  $file
     * @return bool
     */
    public function isFile($file)
    {
        return is_file($file);
    }
}