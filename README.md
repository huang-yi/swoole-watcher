# Swoole Watcher

This package provides a file watcher.

## Installation

The current version only supports [fswatch](https://github.com/emcrisostomo/fswatch), so you'll have to install fswatch first.

```sh
# MacOS
brew install fswatch

# Linux (building from Source)
wget https://github.com/emcrisostomo/fswatch/releases/download/{VERSION}/fswatch-{VERSION}.tar.gz
tar -xzvf fswatch-{VERSION}.tar.gz
cd fswatch-{VERSION} && ./configure && make && sudo make install && sudo ldconfig
```

> A user who wishes to build fswatch should get a release [tarball](https://github.com/emcrisostomo/fswatch/releases)

Then, make sure you have [swoole](https://www.swoole.co.uk/) extension installed in PHP.

```sh
pecl install swoole
```

Finally, you may install the Swoole Watcher via Composer:

```sh
composer require huang-yi/swoole-watcher
```

## Usage

Create your watcher script like this:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use HuangYi\Watcher\Commands\Fswatch;
use HuangYi\Watcher\Watcher;

$command = new Fswatch('/watched/path');

$command->setOptions([
    '--recursive' => true,
    '--filter-from' => '/path/to/filter-rules-file',
]);

$watcher = new Watcher($command);

// Registers a callback for an event.
$watcher->on(Fswatch::CREATED, function ($path) {
    // do something...
});

// or registers a default callback for any event.
$watcher->onChange(function ($events) {
    // do something...
});

$watcher->start();

```

## License

Swoole Watcher is open-sourced software licensed under the [MIT license](LICENSE).