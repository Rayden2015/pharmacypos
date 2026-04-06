<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Logger as MonologLogger;

/**
 * Registers {@see AppendRequestCorrelationProcessor} on the underlying Monolog instance.
 */
class ApplyRequestCorrelationTap
{
    /**
     * @param  \Illuminate\Log\Logger  $logger
     * @param  mixed  $level
     */
    public function __invoke($logger, $level = null): void
    {
        if (! $logger instanceof Logger) {
            return;
        }

        $monolog = $logger->getLogger();
        if (! $monolog instanceof MonologLogger) {
            return;
        }

        $monolog->pushProcessor(new AppendRequestCorrelationProcessor);
    }
}
