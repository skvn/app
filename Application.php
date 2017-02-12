<?php

namespace Skvn\App;

use Skvn\Base\Container;
use Skvn\Base\Config;
use Skvn\Event\EventDispatcher;
use Skvn\Base\Helpers\Str;
use Skvn\Base\Exceptions\NotFoundException;

class Application extends Container
{
    protected $rootPath;
    protected $pathAliases = [];

    function __construct($path)
    {
        parent :: __construct();
        $this->rootPath = $path;
        $this->alias('config', new Config());
        $this->alias('events', new EventDispatcher());
        $this->registerPath('var', 'var');
        $this->registerPath('locks', 'var/locks');
    }

    function run()
    {
        $data = ['app' => $this];
        try
        {
            $this->events->trigger(new Events\Bootstrap($data));
            $this->events->trigger(new Events\CreateEnv($data));
            $this->events->trigger(new Events\SessionStart($data));
            $this->events->trigger(new Events\Preroute($data));
            $this->events->trigger(new Events\Route($data));
            $this->events->trigger(new Events\Execute($data));
            $this->events->trigger(new Events\Render($data));
        }
        catch (\Exception $e)
        {
            $this->events->trigger(new Events\Exception(['exception' => $e, 'app' => $this]));
        }
        $this->events->trigger(new Events\Shutdown($data));
    }

    function registerPath($name, $path)
    {
        if (Str :: pos(DIRECTORY_SEPARATOR, $path) !== 0) {
            $path = $this->rootPath . DIRECTORY_SEPARATOR . $path;
        }
        if (Str :: pos('@', $name) !== 0) {
            $name = '@' . $name;
        }
        $this->pathAliases[$name] = $path;
        return $path;
    }

    function getPath($path)
    {
        if (Str :: pos('@', $path) === 0) {
            $pos = Str :: pos(DIRECTORY_SEPARATOR, $path);
            $alias = $pos === false ? $path : substr($path, 0, $pos);
            if (!isset($this->pathAliases[$alias])) {
                throw new NotFoundException('Alias ' . $alias . ' not found');
            }
            $path = str_replace($alias, $this->pathAliases[$alias], $path);
        }
        return $path;
    }



}