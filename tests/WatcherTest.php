<?php

namespace HuangYi\Watcher\Tests;

use PHPUnit\Framework\TestCase;

class WatcherTest extends TestCase
{
    public function test_change()
    {
        if (file_exists(__DIR__.'/created')) {
            unlink(__DIR__.'/created');
        }

        if (file_exists(__DIR__.'/changed')) {
            unlink(__DIR__.'/changed');
        }

        touch(__DIR__.'/created');

        sleep(1);

        $this->assertTrue(file_exists(__DIR__.'/changed'));
    }
}
