<?php

namespace Skvn\App\Events;

use Skvn\Event\Event as BaseEvent;
use Skvn\Event\Contracts\SelfHandlingEvent;
use Skvn\Base\Helpers\Str;
use Skvn\Base\Exceptions\NotFoundException;


/**
 * Class AppEvent
 * @package Skvn\App\Events
 *
 * @property \Skvn\App\Application $app
 * @property string $action
 */
abstract class ActionEvent extends BaseEvent implements SelfHandlingEvent
{
    function handle()
    {
        $action = 'action' . Str :: studly($this->action);
        if (method_exists($this, $action)) {
            return $this->$action();
        } else {
            throw new NotFoundException('Action ' . $this->action . ' not found at ' . get_class($this));
        }
    }
}