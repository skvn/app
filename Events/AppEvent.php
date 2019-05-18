<?php

namespace Skvn\App\Events;

use Skvn\Event\Event as BaseEvent;


/**
 * Class AppEvent
 * @package Skvn\App\Events
 *
 * @property \Skvn\App\Application $app
 * @property \Skvn\App\Request $request
 * @property \Skvn\App\Response $response
 * @property \Exception $exception
 */
class AppEvent extends BaseEvent
{

}