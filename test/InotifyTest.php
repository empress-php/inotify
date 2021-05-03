<?php

namespace Empress\Inotify\Test;

use Amp\Loop;
use Amp\PHPUnit\AsyncTestCase;
use Empress\Inotify\Event;
use Empress\Inotify\Inotify;
use Empress\Inotify\InvalidWatchException;
use Empress\Inotify\PendingEventReadError;

class InotifyTest extends AsyncTestCase
{
    private const STATIC_DIR = __DIR__ . '/../static';
    private const FOO_FILE = self::STATIC_DIR . '/foo.txt';
    private const BAR_FILE = self::STATIC_DIR . '/bar.txt';

    public function testNoWatches(): void
    {
        $inotify = new Inotify();

        static::assertFalse($inotify->getPendingEvents());
    }

    public function testReadEventsFromDir(): \Generator
    {
        $inotify = new Inotify();
        $inotify->addWatch(self::STATIC_DIR, \IN_ATTRIB | \IN_MODIFY);

        \touch(self::FOO_FILE);
        \file_put_contents(self::BAR_FILE, '');

        $events = yield $inotify->readEvents();

        /** @var Event $fooEvent */
        /** @var Event $barEvent */
        [$fooEvent, $barEvent] = $events;

        static::assertCount(2, $events);

        static::assertEquals(\IN_ATTRIB, $fooEvent->getMask());
        static::assertEquals(\basename(self::FOO_FILE), $fooEvent->getName());

        static::assertEquals(\IN_MODIFY, $barEvent->getMask());
        static::assertEquals(\basename(self::BAR_FILE), $barEvent->getName());

        static::assertFalse($inotify->getPendingEvents());
    }

    public function testReadEventsAfter(): \Generator
    {
        Loop::delay(250, function () {
            \touch(self::FOO_FILE);
        });

        $inotify = new Inotify();
        $inotify->addWatch(self::STATIC_DIR, \IN_ATTRIB);

        /** @var Event $fooEvent */
        [$fooEvent] = yield $inotify->readEvents();

        static::assertEquals(\IN_ATTRIB, $fooEvent->getMask());
        static::assertEquals(\basename(self::FOO_FILE), $fooEvent->getName());

        static::assertFalse($inotify->getPendingEvents());
    }

    public function testAddWatch()
    {
        $this->expectException(InvalidWatchException::class);

        $inotify = new Inotify();
        $inotify->addWatch(self::STATIC_DIR, 0);
    }

    public function testRemoveWatch()
    {
        $this->expectException(InvalidWatchException::class);

        $inotify = new Inotify();
        $descriptor = $inotify->addWatch(self::STATIC_DIR, \IN_ATTRIB);

        $inotify->removeWatch($descriptor);
        $inotify->removeWatch($descriptor);
    }

    public function testPendingEventReadError()
    {
        $this->expectException(PendingEventReadError::class);

        Loop::delay(250, function () {
            \touch(self::BAR_FILE);
        });

        $inotify = new Inotify();
        $inotify->addWatch(self::STATIC_DIR, \IN_ATTRIB);

        $inotify->readEvents();
        $inotify->readEvents();
    }

    public function testGetResource(): void
    {
        $inotify = new Inotify();

        static::assertIsResource($inotify->getResource());
    }

    public function testGetPendingEvents(): void
    {
        $inotify = new Inotify();
        $inotify->addWatch(self::STATIC_DIR, \IN_ATTRIB);

        \touch(self::FOO_FILE);
        \touch(self::BAR_FILE);

        static::assertTrue($inotify->getPendingEvents());
    }
}
