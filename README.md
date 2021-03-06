[![PHP Build](https://github.com/empress-php/inotify/actions/workflows/php.yml/badge.svg)](https://github.com/empress-php/inotify/actions/workflows/php.yml)
[![Latest Stable Version](https://poser.pugx.org/empress-php/inotify/v)](//packagist.org/packages/empress-php/inotify)
[![Coverage Status](https://coveralls.io/repos/github/empress-php/inotify/badge.svg?branch=master)](https://coveralls.io/github/empress-php/inotify?branch=master)

# inotify
Non-blocking ext-inotify wrapper for Amp

## Install it
```sh
composer require empress-php/inotify
```

## Example
```php
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
```
