<?php

namespace Skvn\App\Events;

use Skvn\Base\Traits\ConsoleOutput;
use Skvn\Base\Helpers\Str;

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

}