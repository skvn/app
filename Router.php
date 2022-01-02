<?php
namespace Skvn\App;

use Skvn\Base\Traits\AppHolder;
use Skvn\Base\Helpers\Str;

class Router
{
    use AppHolder;

    protected $routes = [];

    function add($method, $rule, $defaults = [])
    {
        $this->routes[] = [
            'method' => $method,
            'rule' => $rule,
            'defaults' => $defaults
        ];
        return $this;
    }

    function addRoute($rule, $defaults = [])
    {
        return $this->add('*', $rule, $defaults);
    }

    function getRoute($rule, $defaults = [])
    {
        return $this->add('GET', $rule, $defaults);
    }

    function postRoute($rule, $defaults = [])
    {
        return $this->add('POST', $rule, $defaults);
    }

    function run($url)
    {
        if (is_null($url)) {
            $url = '';
        }
        $routed = [];
        foreach ($this->routes as $route) {
            if ($route['method'] != '*' && $route['method'] != $this->app->request->getMethod()) {
                continue;
            }
            if (is_callable($route['rule'])) {
                if (($r = call_user_func($route['rule'], $this->app)) !== false) {
                    $routed = array_merge($r, $route['defaults']);
                    break;
                }
            } elseif (is_string($route['rule'])) {
                $compiled = $this->compileRule($route['rule']);
                if (($r = $this->checkRule($compiled, $url)) !== false) {
                    $routed = array_merge($r, $route['defaults']);
                    break;
                }
            }
        }
        return $routed;
    }


    private function compileRule($rule)
    {
        if (Str::pos('[', $rule) === false && Str::pos('*', $rule) === strlen($rule)-1) {
            return [
                'regexp' => preg_replace('#\*$#', '', $rule),
                'exact' => false,
                'prefix' => true
            ];
        }
        if (Str::pos('[', $rule) === false) {
            return [
                'regexp' => $rule,
                'exact' => true,
                'prefix' => false
            ];
        }
        preg_match_all('#\[([A-Za-z_0-9]+)\]#sU', $rule, $matches, PREG_SET_ORDER);
        $map = [];
        for ($i=0; $i < count($matches); $i++) {
            $map[$i+1] = $matches[$i][1];
            $rule = str_replace('[' . $matches[$i][1] . ']', '([A-Za-z%\.\(\)0-9-_\:]+)', $rule);
        }
        return [
            'regexp' => str_replace('*', '.+', '#^' . $rule . '#s'),
            'exact' => false,
            'prefix' => false,
            'map' => $map
        ];
    }

    private function checkRule(array $rule, $url)
    {
        if ($rule['exact']) {
            if ($rule['regexp'] == $url) {
                return ['routed' => 1];
            }
            return false;
        }
        if ($rule['prefix']) {
            if (Str::pos($rule['regexp'], $url) === 0) {
                return ['routed' => 1];
            }
            return false;
        }
        if (preg_match($rule['regexp'], $url, $matches)) {
            $res = ['routed' => 1];
            for ($i=1; $i<count($matches); $i++) {
                $res[$rule['map'][$i]] = $matches[$i];
            }
            return $res;
        }
        return false;
    }

}