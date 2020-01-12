<?php

namespace HuangYi\Watcher\Contracts;

interface Command
{
    /**
     * Get the executable command.
     *
     * @return string
     */
    public function getCommand(): string;
}
