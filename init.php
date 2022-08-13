<?php

use LukeTowers\EasyAudit\Classes\ActivityLogger;

if (!function_exists('audit')) {
    /**
     * Log the event // NOTE: Probably horribly messy and inefficient, mvp it first
     *
     * @param string $event The name of the event to log
     * @param string $description The human-readable description of the event to log
     * @param Model $subject The model the event is being performed on
     * @param Model $source The model the event is being performed by
     * @param array $properties The additional properties to store with the log entry
     * @param string $logName The log to log this activity to
     */
    function audit($event = '', $description = '', $subject = null, $source = null, $properties = array(), $logName = null)
    {
        $logger = new ActivityLogger();

        // Default the source to the currently logged in backend user (if there is one)
        // ensuring that impersonators are logged as the true source of the activity
        $user = \BackendAuth::getRealUser();
        if ($user) {
            $logger->by($user);
        }

        // Populate the event information in this instance
        if (!empty($event)) {
            $logger->event($event);
        }

        if (!empty($description)) {
            $logger->description($description);
        }

        if ($logger->validateModel($subject)) {
            $logger->for($subject);
        }

        if ($logger->validateModel($source)) {
            $logger->by($source);
        }

        if (!empty($properties)) {
            $logger->properties($properties);
        }

        if (!empty($logName)) {
            $logger->inLog($logName);
        }

        return $logger;
    }
}
