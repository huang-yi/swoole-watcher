<?php

namespace HuangYi\Watcher\Commands;

use HuangYi\Watcher\Contracts\Command;
use HuangYi\Watcher\Exceptions\InvalidBinaryException;
use HuangYi\Watcher\Exceptions\InvalidOutputException;
use Symfony\Component\Process\ExecutableFinder;

class Fswatch implements Command
{
    const NO_OP              = 0;
    const PLATFORM_SPECIFIC  = 1;
    const CREATED            = 2;
    const UPDATED            = 4;
    const REMOVED            = 8;
    const RENAMED            = 16;
    const OWNER_MODIFIED     = 32;
    const ATTRIBUTE_MODIFIED = 64;
    const MOVED_FROM         = 128;
    const MOVED_TO           = 256;
    const IS_FILE            = 512;
    const IS_DIR             = 1024;
    const IS_SYMLINK         = 2048;
    const LINK               = 4096;
    const OVERFLOW           = 8192;

    /**
     * The user-specified binary path.
     *
     * @var string
     */
    protected $binary;

    /**
     * The paths.
     *
     * @var array
     */
    protected $paths = [];

    /**
     * The fixed options.
     *
     * @var array
     */
    protected $fixedOptions = [
        '--numeric'     => true,
        '--event-flags' => true,
    ];

    /**
     * The command options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Fswatch constructor.
     *
     * @param  string  $path
     * @param  string  $binary
     * @return void
     */
    public function __construct($path, $binary = null)
    {
        $this->addPath($path);

        $this->binary = $binary;
    }

    /**
     * Get the executable binary.
     *
     * @return string
     */
    public function getBinary(): string
    {
        if ($this->binary) {
            $binary = $this->binary;
        } else {
            $binary = (new ExecutableFinder)->find('fswatch');
        }

        if (! $binary) {
            throw new InvalidBinaryException("Binary file 'fswatch' not found.");
        }

        if (! @is_executable($binary)) {
            throw new InvalidBinaryException("Binary file '$binary' is not executable.");
        }

        return $binary;
    }

    /**
     * Get the command options.
     *
     * @return array
     */
    public function getArguments(): array
    {
        return array_merge(
            $this->concatOptions(),
            $this->getPaths()
        );
    }

    /**
     * Parse events from the outputs.
     *
     * @param  string  $outputs
     * @return array
     */
    public function parseEvents(string $outputs): array
    {
        $events = [];

        foreach (explode("\n", trim($outputs)) as $line) {
            $pieces = explode(' ', $line);

            if (count($pieces) != 2) {
                throw new InvalidOutputException($outputs);
            }

            if (! is_numeric($pieces[1])) {
                throw new InvalidOutputException($outputs);
            }

            $events[] = $pieces;
        }

        return $events;
    }

    /**
     * Concat the options.
     *
     * @return array
     */
    public function concatOptions()
    {
        $options = [];

        foreach ($this->getOptions() as $key => $value) {
            if ($value === true) {
                $options[] = $key;
            } elseif ($value) {
                $options[] = $key.'='.$value;
            }
        }

        return $options;
    }

    /**
     * Get the options.
     *
     * @return array
     */
    public function getOptions()
    {
        return array_merge($this->options, $this->fixedOptions);
    }

    /**
     * Set command options.
     *
     * @param  array  $options
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Get the paths.
     *
     * @return array
     */
    public function getPaths()
    {
        return $this->paths;
    }

    /**
     * Add a path.
     *
     * @param  string  $path
     * @return $this
     */
    public function addPath($path)
    {
        if (is_array($path)) {
            return $this->addPaths($path);
        }

        $path = trim($path);

        if (! in_array($path, $this->paths)) {
            $this->paths[] = $path;
        }

        return $this;
    }

    /**
     * Add paths.
     *
     * @param  array  $paths
     * @return $this
     */
    public function addPaths(array $paths)
    {
        foreach ($paths as $path) {
            $this->addPath($path);
        }

        return $this;
    }
}
