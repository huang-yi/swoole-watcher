<?php

namespace HuangYi\Watcher\Tests\Commands;

use HuangYi\Watcher\Commands\Fswatch;
use HuangYi\Watcher\Exceptions\InvalidBinaryException;
use HuangYi\Watcher\Exceptions\InvalidOutputException;
use PHPUnit\Framework\TestCase;

class FswatchTest extends TestCase
{
    public function test_get_binary()
    {
        $this->assertStringContainsString('fswatch', $this->makeFswatch()->getBinary());
    }


    public function test_get_invalid_binary()
    {
        $this->expectException(InvalidBinaryException::class);

        $this->makeFswatch('invalid binary')->getBinary();
    }


    public function test_get_arguments()
    {
        $arguments = $this->makeFswatch()->getArguments();

        $this->assertContains('--numeric', $arguments);
        $this->assertContains('--event-flags', $arguments);
        $this->assertEquals(__DIR__, end($arguments));
    }


    public function test_parse_events()
    {
        $events = $this->makeFswatch()->parseEvents('/watched/dir 2');

        $this->assertEquals([['/watched/dir', 2]], $events);
    }


    public function test_parse_invalid_events()
    {
        $this->expectException(InvalidOutputException::class);

        $this->makeFswatch()->parseEvents('invalid outputs');
    }


    protected function makeFswatch($binary = null)
    {
        return new Fswatch(__DIR__, $binary);
    }
}
