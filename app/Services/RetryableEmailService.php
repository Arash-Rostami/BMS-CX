<?php

namespace App\Services;

use Illuminate\Support\Facades\Notification;


use Exception;

class RetryableEmailService
{
    private static int $maxRetries = 4;
    private static int $attempt = 1;

    public static function dispatchEmail($service, ...$arguments): void
    {
        for (self::$attempt = 1; self::$attempt <= self::$maxRetries; self::$attempt++) {
            try {
                // Send the email notification
                Notification::send(...$arguments);

                break;
            } catch (Exception $e) {

                logger()->error('Email notification failed (attempt ' . self::$attempt . '): ' . $e->getMessage());

                sleep(2);
            }
        }

        if (self::$attempt > self::$maxRetries) {
            logger()->error('Email notification exhausted all retries for ' . $service);
        }
    }

}
