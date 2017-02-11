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
    }

    function run()
    {
        try
        {
            $this->events->trigger(new Events\CreateEnv());
            $this->events->trigger(new Events\SessionStart());
            $this->events->trigger(new Events\Preroute());
            $this->events->trigger(new Events\Route());
            $this->events->trigger(new Events\Execute());
            $this->events->trigger(new Events\Render());
        }
        catch (\Exception $e)
        {
            $this->events->trigger(new Events\Exception(['exception' => $e]));
        }
        $this->events->trigger(new Events\Shutdown());
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