<?php

namespace App\Logging;

use Illuminate\Log\Logger;

class NoiseFilter
{
    /**
     * Invoke Monolog tap to filter out noisy log records before writing.
     *
     * @param  \Illuminate\Log\Logger  $logger
     * @return void
     */
    public function __invoke(Logger $logger)
    {
        foreach ($logger->getHandlers() as $handler) {
            $handler->pushProcessor(function ($record) {
                // Get levels and messages supporting both Monolog 2 (array) and Monolog 3 (LogRecord object)
                $levelName = is_array($record) 
                    ? ($record['level_name'] ?? '') 
                    : ($record->level->name ?? '');

                $message = is_array($record) 
                    ? ($record['message'] ?? '') 
                    : ($record->message ?? '');

                $level = strtoupper((string)$levelName);
                $isLowPriority = in_array($level, ['DEBUG', 'INFO', 'NOTICE'], true);
                if (!$isLowPriority) {
                    return $record;
                }

                // Message-only filters (keep list short and focused on high-volume noise).
                $remove = [
                    // Generic request lifecycle noise
                    'API Request Started',
                    'API Request Completed',
                    'prepareForValidation',
                    'Running validation',
                    'DB transaction started',
                    'DB transaction committed',
                    'Cache hit',
                    'Cache miss',
                    'Sending response',
                    'Handling request',

                    // Security health checks
                    '\xF0\x9F\x94\x92 Security features enabled',
                    '🔒 Security features enabled',

                    // Common success logs
                    'Getting access request timeline',
                    'Getting request by ID',
                    'Getting ICT officers list',
                    'Requests loaded successfully',
                    'Fetching',
                ];

                foreach ($remove as $noise) {
                    if ($message !== '' && str_contains($message, $noise)) {
                        return null; // Skip writing the log
                    }
                }

                return $record;
            });
        }
    }
}

