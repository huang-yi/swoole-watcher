# Swoole Watcher

This package provides a file watcher.

## Usage

You may install Swoole Unit via Composer:

```sh
$ composer require huang-yi/swoole-watcher
```

Then, create your watcher script.

```php
<?php

use HuangYi\Watcher\Commands\Fswatch;
use HuangYi\Watcher\Watcher;

$command = new Fswatch('/watched/path');

$command->setOptions([
    '--recursive' => true,
    '--filter-from' => '/path/to/filter-rules-file',
]);

$watcher = new Watcher($command);

$watcher->onChange(function () {
    // do something...
});

$watcher->start();

```

You can stop watch by using `$watcher->stop()`.

## License

Swoole Unit is open-sourced software licensed under the [MIT license](LICENSE).