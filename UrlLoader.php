<?php

namespace Skvn\App;

use Skvn\Base\Helpers\Curl;
use Skvn\Base\Helpers\Str;
use Skvn\Base\Exceptions\CurlException;
use Skvn\Base\Traits\AppHolder;
use Skvn\Event\Events\Log as LogEvent;

class UrlLoader
{
    use AppHolder;

    function load($url, $args = [], $params = [])
    {
        if (!empty($args))
        {
            $params['post'] = 1;
            $params['postfields'] = http_build_query($args);
        }
        $curlParams = [];
        foreach ($params as $pname => $pvalue) {
            if (Str :: pos('ctl_', $pname) === 0) continue;
            $curlParams[constant('CURLOPT_' . strtoupper($pname))] = $pvalue;
        }
        $result = Curl :: fetch($url, $curlParams);
        $this->app->triggerEvent(new LogEvent([
            'message' => $url . ' loaded (' . strlen($result['response']) . ') in ' . $result['time'],
            'category' => 'debug/curl_load',
            'info' => $result
        ]));
        if ($result['error_num'] > 0 || $result['code'] != 200) {
            $this->app->triggerEvent(new LogEvent([
                'message' => $url . ' load failed',
                'category' => 'debug/curl_fail',
                'info' => $result
            ]));
            if (empty($params['ctl_return_error'])) {
                throw new CurlException($url . ' load failed', $result);
            }
        }
        return $result['response'];

    }

}