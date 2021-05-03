<?php

use Amp\Loop;
use Empress\Inotify\Inotify;

require_once __DIR__ . '/../vendor/autoload.php';

Loop::repeat(1000, function () {
    \touch(__FILE__);
});

Loop::run(function () {
    $inotify = new Inotify();
    $inotify->addWatch(__DIR__, \IN_ATTRIB);

    while ($events = yield $inotify->readEvents()) {
        \var_dump($events);
    }
});
