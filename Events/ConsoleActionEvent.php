<?php

namespace Skvn\App\Events;

use Skvn\Base\Traits\ConsoleOutput;
use Skvn\Base\Helpers\Str;
use Skvn\Base\Exceptions\InvalidArgumentException;
use Skvn\Event\Events\NotifyRegular;
/**
 * Class ConsoleActionEvent
 * @package Skvn\App\Events
 *
 * @property array $arguments
 * @property array $options
 */
class ConsoleActionEvent extends ActionEvent
{
    use ConsoleOutput {stdout as traitStdout;}

    protected $strings = [];
    protected $mailOutput = false;
    protected $mailSubject = "";

    function handle()
    {
        if (!empty($this->options['notify'])) {
            $this->mailOutput = true;
            $this->mailSubject = 'Result of ' . Str :: classBasename(get_class($this)) . ' job';
        }
        if (!empty($this->options['cron'])) {
            $this->mailSubject = 'CRON-' . $this->app['cluster']->getOption('my_id') . ': ' . Str :: classBasename(get_class($this)) . '/' . $this->action . '(' . json_encode($this->options) . ')';
        }
        if (!empty($this->options['locks'])) {
            file_put_contents($this->app->getPath('@locks/cron.' . posix_getpid()), json_encode([
                'command' => Str :: classBasename(get_class($this)) . '/' . $this->action,
                'options' => $this->options
            ], JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES));
        }
        $result = parent :: handle();
        if (!empty($this->options['locks'])) {
            unlink($this->app->getPath('@locks/cron.' . posix_getpid()));
        }
        if ($this->mailOutput && !empty($this->strings)) {
            $this->app->triggerEvent(new NotifyRegular(['subject' => $this->mailSubject, 'message' => implode(PHP_EOL, $this->strings)]));
        }
        return $result;
    }

    function stdout($text)
    {
        $this->strings = array_merge($this->strings, (array) $text);
        if ($this->mailOutput) {
            return;
        }
        return $this->traitStdout($text);
    }

    function describeClass()
    {
        return "";
    }

    function describeActions()
    {
        return array_flip(
            array_map(
            function($v){return Str :: snake(substr($v, 6));}, array_filter(
            get_class_methods($this),
            function($v){return Str :: pos('action', $v) === 0;}
        )));
    }

    function getCommandName()
    {
        return Str :: snake(Str :: classBasename(get_class($this)));
    }

    function validateParams()
    {
        $info = $this->describeActions();
        $required = 0;
        if (isset($info[$this->action]['tags']['argument'])) {
            $args = (array) $info[$this->action]['tags']['argument'];
            foreach ($args as $arg) {
                list($type, $name, $desc) = explode(' ', $arg);
                if (Str :: pos('*', $name) === 0) {
                    $required++;
                }
            }
        }
        if ($required > 0 && count($this->arguments) < $required) {
            throw new InvalidArgumentException('Not enough arguments for command');
        }
        foreach ((array) ($info[$this->action]['tags']['option'] ?? []) as $opt) {
            list($type, $name, $desc) = explode(' ', $opt);
            if (Str :: pos('*', $name) === 0) {
                if (!isset($this->options[substr($name, 1)])) {
                    throw new InvalidArgumentException('Option ' . substr($name, 1) . ' is required');
                }
            }
        }
        return true;
    }

}