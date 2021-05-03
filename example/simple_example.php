<?php

use Amp\Loop;
use Empress\Inotify\Event;

require_once __DIR__ . '/../vendor/autoload.php';
//Loop::delay(1000, function () {
//    \touch(__FILE__);
//});

Loop::run(function () {
    $inotify = new Empress\Inotify\Inotify();
    $inotify->addWatch(__DIR__, \IN_ALL_EVENTS);

//    /** @var Event[] $events */
//    while ($events = yield $inotify->readEvents()) {
//        var_dump($events);
//    }
    $events = yield $inotify->readEvents();

    var_dump($events);
});
