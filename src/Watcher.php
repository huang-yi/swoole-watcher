<?php

namespace HuangYi\Watcher;

use Closure;
use HuangYi\Watcher\Contracts\Command;
use HuangYi\Watcher\Contracts\Watcher as WatcherContract;
use Swoole\Process;

class Watcher implements WatcherContract
{
    /**
     * The command instance.
     *
     * @var \HuangYi\Watcher\Contracts\Command
     */
    protected $command;

    /**
     * The process instance.
     *
     * @var \Swoole\Process
     */
    protected $process;

    /**
     * The change event callback.
     *
     * @var \Closure
     */
    protected $callback;

    /**
     * Watcher constructor.
     *
     * @param  \HuangYi\Watcher\Contracts\Command  $command
     * @return void
     */
    public function __construct(Command $command)
    {
        $this->command = $command;

        $this->init();
    }

    /**
     * Initialize.
     *
     * @return void
     */
    protected function init()
    {
        $command = $this->command->getCommand();

        $this->process = new Process(function ($process) use ($command) {
            $process->exec(...$command);
        }, true);
    }

    /**
     * Start the watcher.
     *
     * @param  bool  $start
     * @return void
     */
    public function watch($start = false)
    {
        if ($start) {
            $this->process->start();
        }

        swoole_event_add($this->process->pipe, function () {
            $outputs = $this->process->read();

            $this->fireCallback($outputs);
        });
    }

    /**
     * Fire the change event callback.
     *
     * @param  string  $outputs
     * @return void
     */
    protected function fireCallback($outputs)
    {
        if ($callback = $this->getCallback()) {
            call_user_func($callback, $outputs);
        }
    }

    /**
     * Get the callback.
     *
     * @return \Closure
     */
    public function getCallback()
    {
        if (! $this->callback) {
            return null;
        }

        return function ($outputs) {
            $events = $this->command->parseEvents($outputs);

            call_user_func($this->callback, $events);
        };
    }

    /**
     * Register a change event callback.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function onChange(Closure $callback)
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * Stop the watcher.
     *
     * @return void
     */
    public function stop()
    {
        $this->process->exit();

        Process::wait();
    }

    /**
     * Get the command instance.
     *
     * @return \HuangYi\Watcher\Contracts\Command
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * Get the process instance.
     *
     * @return \Swoole\Process
     */
    public function getProcess()
    {
        return $this->process;
    }
}
