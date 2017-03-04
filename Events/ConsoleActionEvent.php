<?php

namespace Skvn\App\Events;

use Skvn\Base\Traits\ConsoleOutput;
use Skvn\Base\Helpers\Str;
use Skvn\Base\Exceptions\InvalidArgumentException;

class ConsoleActionEvent extends ActionEvent
{
    use ConsoleOutput;

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
                if (Str :: pos('*', $arg) === 0) {
                    $required++;
                }
            }
        }
        if ($required > 0 && count($this->arguments) < $required) {
            throw new InvalidArgumentException('Not enough arguments for command');
        }
        return true;
    }

}