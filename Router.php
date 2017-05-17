<?php
namespace Skvn\App;

use Skvn\Base\Traits\AppHolder;


class Router
{
    use AppHolder;


    protected $routes = array();
    protected $host_routes = array();
    protected $routed = array();

    function add($method, $rule, $defaults = array())
    {
        array_push($this->routes, array(
            'method' => $method,
            'rule' => $rule,
            'defaults' => $defaults
        ));
        return $this;
    }

    function addRoute($rule, $defaults = array())
    {
        return $this->add('*', $rule, $defaults);
    }

    function getRoute($rule, $defaults = array())
    {
        return $this->add('GET', $rule, $defaults);
    }

    function postRoute($rule, $defaults = array())
    {
        return $this->add('POST', $defaults);
    }

    function hostRoute($rule)
    {
        array_push($this->host_routes, array('rule' => $rule));
        return $this;
    }

    function run()
    {
        foreach ($this->routes as $route)
        {
            if ($route['method'] != '*' && $route['method'] != $this->app->request->getRequestMethod())
            {
                continue;
            }
            if (is_callable($route['rule']))
            {
                $routed = call_user_func($route['rule'], $this->app);
                if ($routed !== false)
                {
                    $this->routed = array_merge($route['defaults'], $routed);
                    break;
                }
            }
            elseif (is_string($route['rule']))
            {
                $compiled = $this->compileRule($route['rule']);
                $routed = $this->checkRule($compiled, $this->app->request->getRawUrl());
                if ($routed !== false)
                {
                    $this->routed = array_merge($route['defaults'], $routed);
                    break;
                }
            }

        }
        foreach ($this->host_routes as $route)
        {
            if (is_callable($route['rule']))
            {
                $routed = call_user_func($route['rule'], $this->app);
                if ($routed !== false)
                {
                    $this->routed = array_merge($this->routed, $routed);
                    break;
                }
            }
        }
    }

    function apply(Request $request)
    {
        foreach ($this->routed as $key => $value)
        {
            $request->set($key, $value);
        }
    }

    function map(Request $request, $map)
    {
        foreach ($map as $key => $value)
        {
            if (isset($this->routed[$key]))
            {
                $request->set($value, $this->routed[$key]);
            }
        }
    }

    private function compileRule($rule)
    {
        if (strpos($rule, '[') === false && strpos($rule, '*') === strlen($rule)-1)
        {
            return array(
                'regexp' => preg_replace('#\*$#', '', $rule),
                'exact' => false,
                'prefix' => true
            );
        }
        if (strpos($rule, '[') === false)
        {
            return array(
                'regexp' => $rule,
                'exact' => true,
                'prefix' => false
            );
        }
        preg_match_all('#\[([A-Za-z_0-9]+)\]#sU', $rule, $matches, PREG_SET_ORDER);
        $map = array();
        for ($i=0; $i < count($matches); $i++)
        {
            $map[$i+1] = $matches[$i][1];
            $rule = str_replace('[' . $matches[$i][1] . ']', '([A-Za-z%\.\(\)0-9-_]+)', $rule);
        }
        return array(
            'regexp' => str_replace("*", ".+", '#^' . $rule . '#s'),
            'exact' => false,
            'prefix' => false,
            'map' => $map
        );
    }

    private function checkRule(array $rule, $url)
    {
        if ($rule['exact'])
        {
            if ($rule['regexp'] == $url)
            {
                return array('routed' => 1);
            }
            return false;
        }
        if ($rule['prefix'])
        {
            if (strpos($url, $rule['regexp']) === 0)
            {
                return array('routed' => 1);
            }
            return false;
        }
        if (preg_match($rule['regexp'], $url, $matches))
        {
            $res = array('routed' => 1);
            for ($i=1; $i<count($matches); $i++)
            {
                $res[$rule['map'][$i]] = $matches[$i];
            }
            return $res;
        }
        return false;
    }

}