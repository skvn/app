<?php

namespace Skvn\App\Events;

use Skvn\Event\Event as BaseEvent;
use Skvn\Event\Contracts\SelfHandlingEvent;
use Skvn\Base\Helpers\Str;
use Skvn\Base\Exceptions\NotFoundException;
use Skvn\Base\Traits\ConsoleOutput;


/**
 * Class ActionEvent
 * @package Skvn\App\Events
 *
 * @property \Skvn\App\Application $app
 * @property string $action
 */
abstract class ActionEvent extends BaseEvent implements SelfHandlingEvent
{
    use ConsoleOutput;

    function handle()
    {
        $action = 'action' . Str :: studly($this->action);
        if (method_exists($this, $action)) {
            if ($this->beforeAction() === false) {
                return false;
            }
            $result =  $this->$action();
            return $this->afterAction($result);
        } else {
            throw new NotFoundException('Action ' . $this->action . ' not found at ' . get_class($this));
        }
    }

    protected function beforeAction()
    {

    }

    protected function afterAction($data)
    {
        return $data;
    }
}