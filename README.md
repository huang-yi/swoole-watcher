# Swoole Watcher

This package provides a file watcher.

## Version Compatibility

| PHP     | Swoole  | inotify |
|:-------:|:-------:|:-------:|
| >=5.5.9 | >=1.9.3 | >=0.1.6 |

## Installation

You should install two extensions before requiring this package.

```sh
pecl install swoole
pecl install inotify
```

Then, require this package with composer.

```sh
composer require huang-yi/swoole-watcher
```

## Usage

```php
<?php

use HuangYi\Watcher\Watcher;

$directory = __DIR__;

$excludedDirectories = [
    __DIR__ . '/storage/',
    __DIR__ . '/public/',
];

$suffixes = ['.php', '.env'];

$watcher = new Watcher($directory, $excludedDirectories, $suffixes);

$watcher->setHandler(function ($watcher, $event) {
    // do anything you want.
});

$watcher->watch();
```

## License

The Swoole-Watcher package is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
