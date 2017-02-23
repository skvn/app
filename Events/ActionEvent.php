<?php

namespace Skvn\App\Events;

use Skvn\Event\Event as BaseEvent;
use Skvn\Event\Contracts\SelfHandlingEvent;
use Skvn\Base\Helpers\Str;
use Skvn\Base\Exceptions\NotFoundException;
use Skvn\Base\Exceptions\ImplementationException;


/**
 * Class ActionEvent
 * @package Skvn\App\Events
 *
 * @property \Skvn\App\Application $app
 * @property string $action
 */
abstract class ActionEvent extends BaseEvent implements SelfHandlingEvent
{

    protected $request;
    protected $defaultAction = null;

    function handle()
    {
        $this->request = $this->app->request;
        $actionName = !empty($this->action) ? $this->action : $this->defaultAction;
        if (empty($actionName)) {
            throw new ImplementationException('Action not defined for command ' . get_class($this));
        }
        $action = 'action' . Str :: studly($actionName);
        if (method_exists($this, $action)) {
            if ($this->beforeAction() === false) {
                return false;
            }
            $result =  $this->$action();
            return $this->afterAction($result);
        } else {
            throw new NotFoundException('Action ' . $actionName . ' not found at ' . get_class($this));
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