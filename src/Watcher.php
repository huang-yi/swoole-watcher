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
    protected $onChangeCallback;

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
        $this->process = new Process(function ($process) {
            $process->exec(...$this->command->getCommand());

            swoole_event_add($process->pipe, function () use ($process) {
                $outputs = $process->read();

                if ($callback = $this->getOnChangeCallback()) {
                    call_user_func($callback, $outputs);
                }
            });
        }, true);
    }

    /**
     * Start the watcher.
     *
     * @return void
     */
    public function watch()
    {
        $this->process->start();
    }

    /**
     * Get the callback.
     *
     * @return \Closure
     */
    public function getOnChangeCallback()
    {
        if (! $this->onChangeCallback) {
            return null;
        }

        return function ($outputs) {
            $events = $this->command->parseEvents($outputs);

            call_user_func($this->onChangeCallback, $events);
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
        $this->onChangeCallback = $callback;

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
