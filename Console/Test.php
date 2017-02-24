<?php

namespace Skvn\App\Console;

use Skvn\App\Events\ConsoleActionEvent;
use Composer\Autoload\ClassLoader;

class Test extends ConsoleActionEvent
{
    protected $defaultAction = "help";


}