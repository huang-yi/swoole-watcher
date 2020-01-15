<?php

namespace HuangYi\Watcher\Contracts;

interface Command
{
    /**
     * Get the executable binary.
     *
     * @return string
     */
    public function getBinary(): string;

    /**
     * Get the command arguments.
     *
     * @return array
     */
    public function getArguments(): array;

    /**
     * Parse events from the outputs.
     *
     * @param  string  $outputs
     * @return array
     */
    public function parseEvents(string $outputs): array;
}
