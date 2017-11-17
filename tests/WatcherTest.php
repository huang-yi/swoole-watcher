<?php

namespace HuangYi\Watcher\Tests;

use HuangYi\Watcher\Watcher;

class WatcherTest extends TestCase
{
    /**
     * @var \HuangYi\Watcher\Watcher
     */
    protected $watcher;

    public function setUp()
    {
        parent::setUp();

        $paths = __DIR__ . '/stubs/';
        $excludedPaths = __DIR__ . '/stubs/excluded_stub';
        $types = '_stub';

        $this->watcher = new Watcher($paths, $excludedPaths, $types);
    }

    public function testRun()
    {
        $this->watcher->run();

        $this->assertTrue(in_array(__DIR__ . '/stubs/', $this->watcher->getWatchedPaths()));
        $this->assertTrue(in_array(__DIR__ . '/stubs/watched_stub', $this->watcher->getWatchedPaths()));
    }

    public function testRewatchPaths()
    {
        $this->watcher->run();

        $this->watcher->setExcludedPaths([
            __DIR__ . '/stubs/watched_stub',
        ]);

        $this->watcher->rewatchPaths();

        $this->assertTrue(in_array(__DIR__ . '/stubs/', $this->watcher->getWatchedPaths()));
        $this->assertFalse(in_array(__DIR__ . '/stubs/watched_stub', $this->watcher->getWatchedPaths()));
        $this->assertTrue(in_array(__DIR__ . '/stubs/excluded_stub', $this->watcher->getWatchedPaths()));
    }

    public function testSetHandler()
    {
        $this->watcher->setHandler(function () {
            return true;
        });

        $result = call_user_func_array($this->watcher->getHandler(), [$this->watcher, []]);

        $this->assertTrue($result);
    }

    public function testSetPaths()
    {
        $paths = 'foobar';

        $this->watcher->setPaths($paths);

        $this->assertEquals((array) $paths, $this->watcher->getPaths());
    }

    public function testSetExcludedPaths()
    {
        $excludedPaths = 'foobar';

        $this->watcher->setExcludedPaths($excludedPaths);

        $this->assertEquals((array) $excludedPaths, $this->watcher->getExcludedPaths());
    }

    public function testSetTypes()
    {
        $types = 'foobar';

        $this->watcher->setTypes($types);

        $this->assertEquals((array) $types, $this->watcher->getTypes());
    }

    public function testSetMasks()
    {
        $masks = [IN_MODIFY, IN_CREATE, IN_DELETE];

        $this->watcher->setMasks($masks);

        $this->assertEquals($masks, $this->watcher->getMasks());
        $this->assertEquals(IN_MODIFY | IN_CREATE | IN_DELETE, $this->watcher->getMaskValue());
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->watcher->stop();
    }
}
