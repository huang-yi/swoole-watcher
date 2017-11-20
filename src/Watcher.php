<?php

namespace HuangYi\Watcher;

use Closure;
use Exception;

class Watcher
{
    /**
     * Watched directories.
     *
     * @var array
     */
    protected $directories;

    /**
     * Excluded directories.
     *
     * @var array
     */
    protected $excludedDirectories;

    /**
     * Watched file suffixes.
     *
     * @var array
     */
    protected $suffixes;

    /**
     * Watch handler.
     *
     * @var \Closure
     */
    protected $handler;

    /**
     * Watched masks.
     *
     * @var array
     */
    protected $masks = [
        IN_ATTRIB, IN_CREATE, IN_DELETE, IN_DELETE_SELF, IN_MODIFY, IN_MOVE,
    ];

    /**
     * Mask value.
     *
     * @var int
     */
    protected $maskValue = IN_ATTRIB | IN_CREATE | IN_DELETE | IN_DELETE_SELF | IN_MODIFY | IN_MOVE;

    /**
     * @var Resource
     */
    protected $inotify;

    /**
     * Watched directories.
     *
     * @var array
     */
    protected $watchedDirectories = [];

    /**
     * Watcher constructor.
     *
     * @param array|string $directories
     * @param array|string $excludedDirectories
     * @param array|string $suffixes
     * @throws \Exception
     */
    public function __construct($directories, $excludedDirectories, $suffixes)
    {
        if (! extension_loaded('inotify')) {
            throw new Exception('Extension inotify is required!');
        }

        $this->directories = (array) $directories;
        $this->excludedDirectories = (array) $excludedDirectories;
        $this->suffixes = (array) $suffixes;

        $this->init();
    }

    /**
     * Run watcher.
     */
    public function watch()
    {
        foreach ($this->directories as $directory) {
            if (is_dir($directory)) {
                $this->watchAllDirectories($directory);
            }
        }
    }

    /**
     * Rewatch.
     */
    public function rewatch()
    {
        $this->stop();
        $this->init();
        $this->watch();
    }

    /**
     * Stop watcher.
     */
    public function stop()
    {
        swoole_event_del($this->inotify);
        swoole_event_exit();

        fclose($this->inotify);

        $this->watchedDirectories = [];
    }

    /**
     * Initialize.
     */
    protected function init()
    {
        $this->inotify = inotify_init();

        swoole_event_add($this->inotify, [$this, 'watchHandler']);
    }

    /**
     * Watch handler.
     */
    public function watchHandler()
    {
        if (! $events = inotify_read($this->inotify)) {
            return;
        }

        foreach ($events as $event) {
            if (! empty($event['name']) && ! $this->inWatchedSuffixes($event['name'])) {
                continue;
            }

            if (! $this->handle($event)) {
                break;
            }
        }
    }

    /**
     * Watch the directory and all subdirectories under the directory.
     *
     * @param string $directory
     * @return bool
     */
    protected function watchAllDirectories($directory)
    {
        $directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (! $this->watchDirectory($directory)) {
            return false;
        }

        $names = scandir($directory);

        foreach ($names as $name) {
            if (in_array($name, ['.', '..'], true)) {
                continue;
            }

            $subdirectory = $directory . $name;

            if (is_dir($subdirectory)) {
                $this->watchDirectory($subdirectory . DIRECTORY_SEPARATOR);
            }
        }

        return true;
    }

    /**
     * Watch directory.
     *
     * @param string $directory
     * @return bool
     */
    protected function watchDirectory($directory)
    {
        if ($this->isExcluded($directory)) {
            return false;
        }

        if (! $this->isWatched($directory)) {
            $wd = inotify_add_watch($this->inotify, $directory, $this->getMaskValue());
            $this->watchedDirectories[$wd] = $directory;
        }

        return true;
    }

    /**
     * Determine if the directory is excluded.
     *
     * @param string $directory
     * @return bool
     */
    public function isExcluded($directory)
    {
        return in_array($directory, $this->excludedDirectories, true);
    }

    /**
     * Determine if the directory has been watched.
     *
     * @param $directory
     * @return bool
     */
    public function isWatched($directory)
    {
        return in_array($directory, $this->watchedDirectories, true);
    }

    /**
     * Determine if the file type should be watched.
     *
     * @param string $file
     * @return bool
     */
    protected function inWatchedSuffixes($file)
    {
        foreach ($this->suffixes as $suffix) {
            $start = strlen($suffix);

            if (substr($file, -$start, $start) === $suffix) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle watches.
     *
     * @param array $event
     * @return bool
     */
    protected function handle($event)
    {
        return call_user_func_array($this->getHandler(), [$this, $event]);
    }

    /**
     * Get the watches handler.
     *
     * @return \Closure
     */
    public function getHandler()
    {
        return $this->handler ?: function () {
            //
        };
    }

    /**
     * Set the watches handler.
     *
     * @param \Closure $callback
     * @return $this
     */
    public function setHandler(Closure $callback)
    {
        $this->handler = $callback;

        return $this;
    }

    /**
     * Get watched directories.
     *
     * @return array
     */
    public function getDirectories()
    {
        return $this->directories;
    }

    /**
     * Set watched directories.
     *
     * @param array|string $directories
     * @return $this
     */
    public function setDirectories($directories)
    {
        $this->directories = (array) $directories;

        return $this;
    }

    /**
     * Get excluded directories.
     *
     * @return array
     */
    public function getExcludedDirectories()
    {
        return $this->excludedDirectories;
    }

    /**
     * Set excluded directories.
     *
     * @param array|string $excludedDirectories
     * @return $this
     */
    public function setExcludedPaths($excludedDirectories)
    {
        $this->excludedDirectories = (array) $excludedDirectories;

        return $this;
    }

    /**
     * Get watched file suffixes.
     *
     * @return array
     */
    public function getSuffixes()
    {
        return $this->suffixes;
    }

    /**
     * Set watched file suffixes.
     *
     * @param array|string $suffixes
     * @return $this
     */
    public function setSuffixes($suffixes)
    {
        $this->suffixes = (array) $suffixes;

        return $this;
    }

    /**
     * Get the watched masks.
     *
     * @return array
     */
    public function getMasks()
    {
        return $this->masks;
    }

    /**
     * Set the watched masks.
     *
     * @param array $masks
     * @return $this
     */
    public function setMasks($masks)
    {
        $this->masks = (array) $masks;
        $this->maskValue = array_reduce($this->masks, function ($maskValue, $mask) {
            return $maskValue | $mask;
        });

        return $this;
    }

    /**
     * Get mask value.
     *
     * @return int
     */
    public function getMaskValue()
    {
        return $this->maskValue;
    }

    /**
     * Get watched directories.
     *
     * @return array
     */
    public function getWatchedDirectories()
    {
        return $this->watchedDirectories;
    }
}
