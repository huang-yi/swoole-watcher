<?php

namespace HuangYi\Watcher\Contracts;

use Closure;

interface Watcher
{
    /**
     * Start the watcher.
     *
     * @return void
     */
    public function watch();

    /**
     * Stop the watcher.
     *
     * @return void
     */
    public function stop();

    /**
     * Register a change event callback.
     *
     * @param  \Closure  $closure
     * @return mixed
     */
    public function onChange(Closure $closure);
}
