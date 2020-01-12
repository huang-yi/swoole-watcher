<?php

namespace HuangYi\Watcher;

use HuangYi\Watcher\Contracts\Command;

class Watcher
{
    /**
     * Get the executable command.
     *
     * @var \HuangYi\Watcher\Contracts\Command
     */
    protected $command;

    /**
     * Watcher constructor.
     *
     * @param  \HuangYi\Watcher\Contracts\Command  $command
     * @return void
     */
    public function __construct(Command $command)
    {
        $this->command = $command;
    }

    public function watch()
    {

    }
}
