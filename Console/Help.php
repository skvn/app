<?php

namespace Skvn\App\Console;

use Skvn\App\Events\ConsoleActionEvent;
use Skvn\Base\Traits\SelfDescribe;

/**
 * Provides help
 * @package Skvn\App\Console
 */

class Help extends ConsoleActionEvent
{
    use SelfDescribe;

    protected $defaultAction = "help";


    function actionHelp()
    {
        if (empty($this->arguments[0])) {
            return $this->processList();
        } else {
            return $this->processHelp();
        }
    }

    function processList()
    {
        $this->success('List of available commands');
        foreach ($this->app->getAvailableCommands() as $name => $command) {
            $this->stdout('<yellow>- ' . $name . '</yellow> <bold>'.($command->describeClass()['title'] ?? '').'</bold>');
            foreach ($command->describeActions() as $action => $info) {
                $default = '';
                if ($command->getDefaultAction() && $command->getDefaultAction() === $action) {
                    $default = '<cyan>(default)</cyan> ';
                }
                $this->stdout(str_repeat(' ', 8) . '<green>' . $name . '/' . $action . '</green> ' . $default . ($info['title'] ?? '') );
            }
            $this->stdout(PHP_EOL . PHP_EOL);
        }
    }
}