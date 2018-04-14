<?php

namespace Skvn\App;

use Skvn\Base\Container;
use Skvn\Base\Config;
use Skvn\Event\EventDispatcher;
use Skvn\Base\Helpers\Str;
use Skvn\Base\Exceptions\NotFoundException;
use Skvn\Event\Contracts\Event;
use Skvn\Base\Helpers\File;

class Application extends Container
{
    protected $rootPath;
    protected $pathAliases = [];
    protected $appMode = null;
    protected $wafRules = [];
    protected $commandNamespaces = [
        '\\Skvn\\App\\Console' => __DIR__ . '/Console',
        '\\Skvn\\Event\\Console' => __DIR__ . '/../event/Console',
    ];
    protected $services = [];


    function init($path, $mode = null)
    {
        $this->rootPath = $path;
        chdir($this->rootPath);
        if (is_null($mode)) {
            $mode = php_sapi_name() == 'cli' ? 'cli' : 'web';
        }
        $this->appMode = $mode;

        $this->alias('app', $this);
        $this->pathAliases['@root'] = $this->rootPath;
        $this->initApp();
        $this->bindAppEvents();

    }

    function initApp()
    {

    }

    function bindAppEvents()
    {

    }
    
    public function registerWafRule($rule)
    {
        $this->wafRules[] = $rule;
    }
    
    protected function checkWafRules()
    {
        foreach ($this->wafRules as $rule) {
            if (is_object($rule)) {
                foreach (get_class_methods($rule) as $method) {
                    if (Str::pos('waf', $method) === 0) {
                        if ($rule->$method($this->request) === false) {
                            return false;
                        }
                    }
                }
            } else if (is_callable($rule)) {
                if ($rule($this->request) === false) {
                    return false;
                }
            } else {
                throw new \Exception('Invalid WAF Rule assigned');
            }
        }
        return true;
    }

    function run()
    {
        $data = ['app' => $this];
        try {
            if ($this->checkWafRules() !== false) {
                $this->triggerEvent(new Events\Bootstrap($data));
                $this->triggerEvent(new Events\CreateEnv($data));
                $this->triggerEvent(new Events\SessionStart($data));
                $this->triggerEvent(new Events\Preroute($data));
                $this->triggerEvent(new Events\Route($data));
                $this->triggerEvent(new Events\Execute($data));
                $this->triggerEvent(new Events\Render($data));
            } else {
                $this->triggerEvent(new Events\Banned($data));
            }
        } catch (\Throwable $e) {
            $result = $this->triggerEvent(new Events\Exception(['exception' => $e, 'app' => $this]));
            if ($result === false) {
                throw $e;
            }
        }
        $this->triggerEvent(new Events\Shutdown($data));
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

    function get($alias)
    {
        if (!isset($this->aliases[$alias])) {
            return $this->getAppService($alias);
        }
        return $this->aliases[$alias];
    }

    function execCommand(Event $command)
    {
        return $this->events->trigger($command, true);
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

    function registerFacades()
    {
        foreach (array_merge($this->getAppFacades(), $this->config['app.facades'] ?? []) as $alias => $class) {
            $this->registerClassAlias($class, $alias);
        }
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

    function registerPaths()
    {
        foreach ($this->config['app.paths'] as $alias => $path) {
            $this->registerPath($alias, $path);
        }
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

    function registerCommandNamespace($namespace, $path)
    {
        $this->commandNamespaces[$namespace] = $path;
        return $this;
    }

    function createCommand($command, $args = [])
    {
        if (Str :: pos('/', $command) !== false) {
            list($controller, $action) = explode('/', $command);
        } else
        {
            list($controller, $action) = [$command, null];
        }
        foreach ($this->commandNamespaces as $ns => $path) {
            $class = $ns . '\\' . Str :: studly($controller);
            if (class_exists($class)) {
                $args['action'] = $action;
                return new $class($args);
            }
        }
        return false;

    }

    function getAvailableCommands()
    {
        $commands = [];
        foreach ($this->commandNamespaces as $ns => $path) {
            $files = File :: ls($path);
            foreach ($files as $file) {
                $class = preg_replace('#\.php$#', '', $file);
                $class = $ns . '\\' . $class;
                $command = new $class();
                $commands[$command->getCommandName()] = $command;
            }
        }
        ksort($commands);
        return $commands;
    }

    function filterScheduledEntry($entry)
    {
        return true;
    }

    function appendScheduledEntries($entries)
    {
        return $entries;
    }

    function getAppServices()
    {
        return array_merge([
            'config' => \Skvn\Base\Config :: class,
            'events' => \Skvn\Event\EventDispatcher :: class,
            'request' => \Skvn\App\Request :: class,
            'response' => \Skvn\App\Response :: class,
            'session' => \Skvn\App\Session :: class,
            'router' => \Skvn\App\Router :: class,
            'urlLoader' => \Skvn\App\UrlLoader :: class
        ], $this->services);
    }

    function getAppService($service)
    {
        $services = $this->getAppServices();
        if (array_key_exists($service, $services)) {
            if (is_string($services[$service]) && Str :: pos('.', $services[$service])) {
                list($factoryName, $serviceName) = explode('.', $services[$service]);
                $obj = $this->$factoryName->$serviceName;
            } else {
                if (is_array($services[$service])) {
                    $class = $services[$service]['class'];
                    $obj = new $class($services[$service]);
                } else {
                    $class = $services[$service];
                    $obj = new $class();
                }
            }
            $obj->setApp($this);
            $this->alias($service, $obj);
            return $obj;
        }
        throw new NotFoundException('Service ' . $service . ' not found');
    }

    function getAppFacades()
    {
        return [
            'App' => Facades\App :: class,
            //'Config' => Facades\Config :: class,
            'Queue' => Facades\Queue :: class,
            'Events' => Facades\Events :: class,
            'Request' => Facades\Request :: class,
            'Response' => Facades\Response :: class,
            'Session' => Facades\Session :: class,
            'DB' => Facades\DB :: class,
        ];
    }







}