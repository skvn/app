<?php

namespace Skvn\App;

use Skvn\Base\Traits\AppHolder;
use Skvn\Base\Helpers\Str;
use Skvn\Event\Events\Log as LogEvent;


class JsonApi
{
    use AppHolder;

    protected $services;
    protected $config;

    function __construct($config) {
        $this->config = $config;
    }

    function handleRequest($request, $response)
    {
        $t = microtime(true);
        if (!$request->isPost()) {
            throw new Exceptions\ApiException('Only POST allowed for JSON API');
        }
        $post = json_decode($request->getRawBody(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exceptions\ApiException('Unable to decode API request');
        }
        if (empty($post)) {
            throw new Exceptions\ApiException('JSON API request is empty');
        }
        if (empty($post['endpoint'])) {
            throw new Exceptions\ApiException('Endpoint not found');
        }
        if (Str :: pos('/', $post['endpoint']) === false) {
            throw new Exceptions\ApiException('Invalid endpoint: ' . $post['endpoint']);
        }
        $this->buildServices();

        try {
            list($service, $method) = explode('/', $post['endpoint'], 2);
            if (!isset($this->services[$service])) {
                throw new Exceptions\ApiException('Service ' . $service . ' not found');
            }
            if (!$this->services[$service]->authorize($post)) {
                throw new Exceptions\ApiException('Authorization failed for ' . $post['endpoint']);
            }
            $result = $this->services[$service]->call($method, $post);
        } catch (\Exception $e) {
            $result = ['error_code' => $e->getCode(), 'error_message' => $e->getMessage()];
        }

        $endpoint = $post['endpoint'];
        unset($post['endpoint']);
        $this->log([
            'endpoint' => $endpoint,
            'args' => $post,
            'result' => $result,
            'time' => microtime(true) - $t
        ], true);

        $response->setContent($result, 'json');
    }

    protected function buildServices()
    {
        foreach ($this->config['services'] ?? [] as $class) {
            $this->registerService($class);
        }
    }

    function registerService($class)
    {
        $service = $this->app->make($class);
        if (!$service instanceof ApiService) {
            throw new Exceptions\ApiException('Attempt to register incorrect API service: ' . $class);
        }
        $service->setApp($this->app);
        $this->services[$service->getName()] = $service;
    }

    function call($host, $endpoint, $args = [])
    {
        $t = microtime(true);
        $params = $args;
        $params['endpoint'] = $endpoint;
        $url = $this->app->config['app.protocol'] . $host . $this->config['url'];
        $data = json_encode($params);
        $result = $this->app->urlLoader->load($url, [], [
            'post' => 1,
            'postfields' => $data,
            'ctl_return_error' => true
        ]);
        $response = json_decode($result, true);
        $this->log([
            'response' => $response,
            'args' => $args,
            'host' => $host,'endpoint' => $endpoint,
            'time' => microtime(true) - $t
        ]);
        return $response;
    }


    function log($args, $handle = false)
    {
        if (!empty($this->config['logging'])) {
            $args['category'] = $handle ? 'apis' : 'api';
            $this->app->triggerEvent(new LogEvent($args));
        }
    }


}