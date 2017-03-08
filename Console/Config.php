<?php

namespace Skvn\App\Console;

use Skvn\App\Events\ConsoleActionEvent;
use Skvn\Base\Traits\SelfDescribe;
use Skvn\Base\Helpers\Str;
use Skvn\Base\Exceptions\ConsoleException;

/**
 * Builds configuration
 * @package Skvn\App\Console
 */

class Config extends ConsoleActionEvent
{
    use SelfDescribe;

    protected $defaultAction = "default";


    function actionDefault()
    {
        throw new ConsoleException('Action not defined');
    }

    /**
     * Build single configuration file
     * @option string *path Path to build configuration to
     */
    function actionBuild()
    {
        $config = $this->app->config->export();
        $str = '<?php' . PHP_EOL . 'return ' . var_export($config, true) . ';' . PHP_EOL;
        file_put_contents($this->options['path'], $str);
        $this->stdout('Configuration built');
    }



}