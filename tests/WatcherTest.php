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

        $directories = __DIR__ . '/stubs/';
        $excludedDirectories = __DIR__ . '/stubs/excluded_dir';
        $suffixes = '_stub';

        $this->watcher = new Watcher($directories, $excludedDirectories, $suffixes);
    }

    public function testWatch()
    {
        $this->watcher->watch();

        $this->assertTrue(in_array(__DIR__ . '/stubs/', $this->watcher->getWatchedDirectories()));
        $this->assertFalse(in_array(__DIR__ . '/stubs/excluded_dir', $this->watcher->getWatchedDirectories()));
    }

    public function testRewatch()
    {
        $this->watcher->watch();
        $this->watcher->setExcludedPaths([]);
        $this->watcher->rewatch();

        $this->assertTrue(in_array(__DIR__ . '/stubs/', $this->watcher->getWatchedDirectories()));
        $this->assertTrue(in_array(__DIR__ . '/stubs/excluded_dir', $this->watcher->getWatchedDirectories()));
    }

    public function testSetHandler()
    {
        $this->watcher->setHandler(function () {
            return true;
        });

        $result = call_user_func_array($this->watcher->getHandler(), [$this->watcher, []]);

        $this->assertTrue($result);
    }

    public function testSetDirectories()
    {
        $paths = 'foobar';

        $this->watcher->setDirectories($paths);

        $this->assertEquals((array) $paths, $this->watcher->getDirectories());
    }

    public function testSetExcludedDirectories()
    {
        $excludedDirectories = 'foobar';

        $this->watcher->setExcludedPaths($excludedDirectories);

        $this->assertEquals((array) $excludedDirectories, $this->watcher->getExcludedDirectories());
    }

    public function testSetTypes()
    {
        $suffixes = 'foobar';

        $this->watcher->setSuffixes($suffixes);

        $this->assertEquals((array) $suffixes, $this->watcher->getSuffixes());
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
