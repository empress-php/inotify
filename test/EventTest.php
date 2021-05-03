<?php

namespace Empress\Inotify\Test;

use Empress\Inotify\Event;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    public function testCreateFromInotifyEvent(): void
    {
        $eventData = [
            'wd' => 10,
            'mask' => \IN_MODIFY,
            'cookie' => 0,
            'name' => 'abc'
        ];

        $event = Event::create($eventData);

        static::assertEquals(10, $event->getDescriptor());
        static::assertEquals(\IN_MODIFY, $event->getMask());
        static::assertEquals(0, $event->getCookie());
        static::assertEquals('abc', $event->getName());
    }
}