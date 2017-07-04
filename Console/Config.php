<?php

namespace Skvn\App\Console;

use Skvn\App\Events\ConsoleActionEvent;
use Skvn\Base\Traits\SelfDescribe;
use Skvn\Base\Helpers\Str;
use Skvn\Base\Exceptions\ConsoleException;
use Skvn\Base\Helpers\File;

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

    /**
     * Join all configuration files from folder to single configuration file
     * @argument string *folder folder to join
     */
    function actionJoin()
    {
        $files = File :: ls($this->app->getPath('@config/' . $this->arguments[0]), ['paths' => true]);
        $content = '<?php';
        foreach ($files as $file) {
            $c = file_get_contents($file);
            $c = str_replace('<?php', '', $c);
            $content .= $c;
            $content .= "\n\n";
        }
        $filename = $this->app->getPath('@config/conf.' . $this->arguments[0] . '.compiled.php');
        file_put_contents($filename, $content);
        $this->success($filename . " compiled");
    }





}