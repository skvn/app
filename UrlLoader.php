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
        \App :: triggerEvent(new LogEvent([
            'message' => $url . ' loaded (' . strlen($result['response']) . ') in ' . $result['time'],
            'category' => 'debug/curl_load',
            'info' => $result
        ]));
        if ($result['error_num'] > 0 || $result['code'] != 200) {
            \App :: triggerEvent(new LogEvent([
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

//    function fetch2($uri, $args = [], $params = [])
//    {
//        mtoProfiler :: instance()->logDebug(, "debug/curl_call");
//        if (curl_errno($curl) > 0 || curl_getinfo($curl, CURLINFO_HTTP_CODE) != 200)
//        {
//            //var_dump($result);
//            mtoProfiler :: instance()->logDebug($uri . "::" . curl_getinfo($curl, CURLINFO_RESPONSE_CODE) . "::" . curl_errno($curl) . "::" . curl_error($curl).print_r($result,1), $filename);
//
//            //mtoProfiler :: instance()->logDebug($uri . "\n" . $result, "debug/curl_output");
//            curl_close($curl);
//            if (isset($params['ctl_return_error']))
//            {
//                return $result;
//            }
//            throw new mtoException("Load failed");
//        }
//        curl_close($curl);
//        return $result;
//    }


}