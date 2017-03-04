<?php

namespace Skvn\App\Events;

use Skvn\Event\Event as BaseEvent;


/**
 * Class AppEvent
 * @package Skvn\App\Events
 *
 * @property \Skvn\App\Application $app
 * @property string $mesage
 * @property string $category
 */
class Log extends BaseEvent
{

}