<?php

namespace HuangYi\Watcher;

use Closure;
use Exception;

class Watcher
{
    /**
     * Watched paths.
     *
     * @var array
     */
    protected $paths;

    /**
     * Excluded paths.
     *
     * @var array
     */
    protected $excludedPaths;

    /**
     * Watched file types.
     *
     * @var array
     */
    protected $types;

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
        IN_MODIFY, IN_MOVED_TO, IN_MOVED_FROM, IN_CREATE, IN_DELETE,
    ];

    /**
     * Mask value.
     *
     * @var int
     */
    protected $maskValue = IN_MODIFY | IN_MOVED_TO | IN_MOVED_FROM | IN_CREATE | IN_DELETE;

    /**
     * @var Resource
     */
    protected $inotify;

    /**
     * Watched paths.
     *
     * @var array
     */
    protected $watchedPaths = [];

    /**
     * Watcher constructor.
     *
     * @param array|string $paths
     * @param array|string $excludedPaths
     * @param array|string $types
     * @throws \Exception
     */
    public function __construct($paths, $excludedPaths, $types)
    {
        if (! extension_loaded('inotify')) {
            throw new Exception('Extension inotify is required!');
        }

        $this->paths = (array) $paths;
        $this->excludedPaths = (array) $excludedPaths;
        $this->types = (array) $types;
        $this->inotify = inotify_init();

        $this->registerEvent();
    }

    /**
     * Run watcher.
     */
    public function run()
    {
        $this->watchPaths();
    }

    /**
     * Rewatch paths.
     */
    public function rewatchPaths()
    {
        $this->clearWatches();
        $this->watchPaths();
    }

    /**
     * Stop watcher.
     */
    public function stop()
    {
        $this->clearWatches();

        swoole_event_del($this->inotify);
        swoole_event_exit();

        $this->inotify = null;
    }

    /**
     * Register event.
     */
    protected function registerEvent()
    {
        swoole_event_add($this->inotify, function () {
            if (! $events = inotify_read($this->inotify)) {
                return;
            }

            foreach ($events as $event) {
                $break = ! $this->handle($event);

                if ($event['mask'] === IN_IGNORED) {
                    $this->rewatchPath($event['wd']);
                }

                if ($break) {
                    break;
                }
            }
        });
    }

    /**
     * Watch paths.
     */
    protected function watchPaths()
    {
        foreach ($this->paths as $path) {
            if (is_dir($path)) {
                $this->watchDir($path);
            } else {
                $this->watchFile($path);
            }
        }
    }

    /**
     * Watch all subdirectories and files under the directory.
     *
     * @param string $dir
     * @return bool
     */
    protected function watchDir($dir)
    {
        $dir = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (! $this->watchPath($dir)) {
            return false;
        }

        $names = scandir($dir);

        foreach ($names as $name) {
            if (in_array($name, ['.', '..'], true)) {
                continue;
            }

            $path = $dir . $name;

            if (is_dir($path)) {
                $this->watchDir($path);
            } else {
                $this->watchFile($path);
            }
        }

        return true;
    }

    /**
     * Watch file.
     *
     * @param string $file
     * @return bool
     */
    protected function watchFile($file)
    {
        return $this->watchPath($file);
    }

    /**
     * Watch path.
     *
     * @param string $path
     * @return bool
     */
    protected function watchPath($path)
    {
        if (! $this->shouldBeWatched($path)) {
            return false;
        }

        if (! isset($this->watchedPaths[$path])) {
            $wd = inotify_add_watch($this->inotify, $path, $this->getMaskValue());
            $this->watchedPaths[$wd] = $path;
        }

        return true;
    }

    /**
     * Rewatch path.
     *
     * @param int $wd
     * @return bool
     */
    protected function rewatchPath($wd)
    {
        if (! isset($this->watchedPaths[$wd])) {
            return false;
        }

        $path = $this->watchedPaths[$wd];
        unset($this->watchedPaths[$wd]);

        $this->watchPath($path);
    }

    /**
     * Determine if the path should be watched.
     *
     * @param string $path
     * @return bool
     */
    protected function shouldBeWatched($path)
    {
        if (in_array($path, $this->excludedPaths, true)) {
            return false;
        }

        if (! file_exists($path)) {
            return false;
        }

        if (empty($this->types) || is_dir($path)) {
            return true;
        }

        foreach ($this->types as $type) {
            $start = strlen($type);

            if (substr($path, -$start, $start) === $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * Clear watches.
     */
    protected function clearWatches()
    {
        foreach ($this->watchedPaths as $wd => $path) {
            inotify_rm_watch($this->inotify, $wd);
        }

        $this->watchedPaths = [];
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
     * Get watched paths.
     *
     * @return array
     */
    public function getPaths()
    {
        return $this->paths;
    }

    /**
     * Set watched paths.
     *
     * @param array|string $paths
     * @return $this
     */
    public function setPaths($paths)
    {
        $this->paths = (array) $paths;

        return $this;
    }

    /**
     * Get excluded paths.
     *
     * @return array
     */
    public function getExcludedPaths()
    {
        return $this->excludedPaths;
    }

    /**
     * Set excluded paths.
     *
     * @param array|string $excludedPaths
     * @return $this
     */
    public function setExcludedPaths($excludedPaths)
    {
        $this->excludedPaths = (array) $excludedPaths;

        return $this;
    }

    /**
     * Get watched file types.
     *
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * Set watched file types.
     *
     * @param array|string $types
     * @return $this
     */
    public function setTypes($types)
    {
        $this->types = (array) $types;

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
     * Get watched paths.
     *
     * @return array
     */
    public function getWatchedPaths()
    {
        return $this->watchedPaths;
    }
}
