<?php

namespace HuangYi\Watcher;

use Closure;
use HuangYi\Watcher\Contracts\Command;
use Swoole\Process;

class Watcher
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
     * The default callback.
     *
     * @var \Closure
     */
    protected $changeCallback;

    /**
     * The events callbacks.
     *
     * @var \Closure
     */
    protected $callbacks = [];

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
            $process->exec(
                $this->command->getBinary(),
                $this->command->getArguments()
            );
        }, true);
    }

    /**
     * Start the watcher.
     *
     * @return void
     */
    public function start()
    {
        $this->process->start();

        swoole_event_add($this->process->pipe, function () {
            $outputs = $this->process->read();

            $this->fireCallbacks($outputs);
        });
    }

    /**
     * Fire the callbacks.
     *
     * @param  string  $outputs
     * @return void
     */
    protected function fireCallbacks($outputs)
    {
        if ($callback = $this->getCallback()) {
            call_user_func($callback, $outputs);
        }
    }

    /**
     * Get the callback.
     *
     * @return \Closure|null
     */
    public function getCallback()
    {
        if (! $this->changeCallback && ! $this->callbacks) {
            return null;
        }

        return function ($outputs) {
            $events = $this->command->parseEvents($outputs);

            if ($this->changeCallback) {
                call_user_func($this->changeCallback, $events);
            }

            if ($this->callbacks) {
                foreach ($this->callbacks as $event => $callback) {
                    foreach ($events as $item) {
                        if ($item[1] | $event === $event) {
                            call_user_func($callback, $item[0]);
                        }
                    }
                }
            }
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
        $this->changeCallback = $callback;

        return $this;
    }

    /**
     * Register an event callback.
     *
     * @param  int  $event
     * @param  \Closure  $callback
     * @return $this
     */
    public function on(int $event, Closure $callback)
    {
        $this->callbacks[$event] = $callback;

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
