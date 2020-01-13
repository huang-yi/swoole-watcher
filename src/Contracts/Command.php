<?php

namespace HuangYi\Watcher\Contracts;

interface Command
{
    /**
     * Get the executable command.
     *
     * @return array
     */
    public function getCommand(): array;

    /**
     * Parse events from the outputs.
     *
     * @param  string  $outputs
     * @return array
     */
    public function parseEvents(string $outputs): array;
}
