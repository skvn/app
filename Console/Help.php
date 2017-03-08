<?php

namespace Skvn\App\Console;

use Skvn\App\Events\ConsoleActionEvent;
use Skvn\Base\Traits\SelfDescribe;
use Skvn\Base\Helpers\Str;

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

    protected function processList()
    {
        $this->success('List of available commands');
        foreach ($this->app->getAvailableCommands() as $name => $command) {
            $this->stdout('<yellow>- ' . $name . '</yellow> <bold>'.($command->describeClass()['title'] ?? '').'</bold>');
            $this->processActionList($command, $name);
            $this->stdout(PHP_EOL . PHP_EOL);
        }
    }

    protected function processHelp()
    {
        $command = $this->app->createCommand($this->arguments[0]);
        if ($command instanceof ConsoleActionEvent) {
            if (!empty($command->action)) {
                $this->processActionHelp($command);
            } else {
                $this->processClassHelp($command);
            }
            return true;
        } else {
            $this->error('Command ' . $this->arguments[0] . ' not found');
            return false;
        }
    }

    protected function processClassHelp($command)
    {
        $this->stdout('<bold>DESCRIPTION</bold>' . PHP_EOL);
        $this->stdout($command->describeClass()['description'] ?? '');
        $this->stdout(PHP_EOL . PHP_EOL);
        $this->stdout('<bold>ACTIONS</bold>' . PHP_EOL);
        $this->processActionList($command, $command->getCommandName());

    }

    protected function processActionList($command, $name)
    {
        foreach ($command->describeActions() as $action => $info) {
            $default = '';
            if ($command->getDefaultAction() && $command->getDefaultAction() === $action) {
                $default = '<cyan>(default)</cyan> ';
            }
            $this->stdout(str_repeat(' ', 8) . '<green>' . $name . '/' . $action . '</green> ' . $default . ($info['title'] ?? '') );
        }
    }

    protected function processActionHelp($command)
    {
        $actions = $command->describeActions();
        if (!isset($actions[$command->action])) {
            $this->error('Action ' . $command->action . ' on command ' . $command->getCommandName());
            return;
        }
        $this->stdout('<bold>DESCRIPTION</bold>' . PHP_EOL);
        $this->stdout($actions[$command->action]['description'] ?? '');
        $this->stdout(PHP_EOL . PHP_EOL);
        $this->stdout('<bold>ARGUMENTS</bold>' . PHP_EOL);
        foreach ((array) ($actions[$command->action]['tags']['argument'] ?? []) as $arg) {
            $this->processParam($arg);
        }
        $this->stdout(PHP_EOL . PHP_EOL);
        $this->stdout('<bold>OPTIONS</bold>' . PHP_EOL);
        foreach ((array) ($actions[$command->action]['tags']['option'] ?? []) as $arg) {
            $this->processParam($arg, "--");
        }
    }

    protected function processParam($param, $prefix = "")
    {
        list($type, $name, $desc) = explode(' ', $param, 3);
        $required = '';
        $default = '';
        if (Str :: pos('*', $name) === 0) {
            $required = '(Required)';
            $name = substr($name, 1);
        }
        $eqpos = Str :: pos('=', $name);
        if ($eqpos !== false) {
            $default = substr($name, $eqpos+1);
            $name = substr($name, 0, $eqpos);
        }
        $str = [];
        $str[] = '<cyan>' . $prefix . $name . '</cyan>:';
        $str[] = '<bold>' . $type . '</bold>';
        if ($required) {
            $str[] = $required;
        }
        if ($default) {
            $str[] = 'Defaults to: ' . $default;
        }

        $this->stdout(implode(' ', $str));
        $this->stdout('    ' . $desc);
        $this->stdout(PHP_EOL);
    }


}