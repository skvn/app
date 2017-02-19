<?php

namespace Skvn\App;

use Skvn\Base\Container;
use Skvn\Base\Config;
use Skvn\Event\EventDispatcher;
use Skvn\Base\Helpers\Str;
use Skvn\Base\Exceptions\NotFoundException;
use Skvn\Event\Contracts\Event;

class Application extends Container
{
    protected $rootPath;
    protected $pathAliases = [];
    protected $appMode = null;


    function init($path, $mode = null)
    {
        $this->rootPath = $path;
        chdir($this->rootPath);
        if (is_null($mode)) {
            $mode = php_sapi_name() == "cli" ? "cli" : "web";
        }
        $this->appMode = $mode;

        $this->alias('app', $this);
        $this->alias('config', new Config());
        $this->alias('events', new EventDispatcher());
        $this->initApp();
        $this->bindAppEvents();

    }

    function initApp()
    {

    }

    function bindAppEvents()
    {

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

    function bindEvent($event, $handler, $modes = null, $prepend = false)
    {
        if (!$this->checkMode($modes))
        {
            return;
        }

        if (is_string($handler) && strpos($handler, '/') !== false && strpos($handler, '@') !== false)
        {
            list($class, $method) = explode('@', $handler);
            $classname = basename($class);
            $handler = $classname . '@' . $method;
        }
        return $this->events->listen($event, $handler, $prepend);
    }

    function triggerEvent(Event $event)
    {
        return $this->events->trigger($event);
    }

    function checkMode($modes)
    {
        if (is_null($modes) || $modes == '*') {
            return true;
        }
        $modes = (array) $modes;
        $has_allow = false;
        foreach ($modes as $m) {
            if (("!" . $this->appMode) == $m) {
                return false;
            }
            if (strpos($m, '!') === false) {
                $has_allow = true;
            }
            if ($m == $this->appMode) {
                return true;
            }
        }
        if ($has_allow)
        {
            return false;
        }
        return true;
    }

    function createFacade($target, $alias)
    {
        $code = 'class ' . $alias . ' extends \\Skvn\\Base\\Facade {protected static function getFacadeTarget() {return "' . $target . '";}}';
        eval($code);
        //$this->registerAlias('\\Facades\\' . $alias, $alias);
    }

    function registerClassAlias($class, $alias)
    {
        class_alias($class, '\\' . $alias);
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

    function getMode()
    {
        return $this->appMode;
    }



}