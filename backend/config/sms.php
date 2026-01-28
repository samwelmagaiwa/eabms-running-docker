<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SMS Service Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for SMS notifications
    | in the Muhimbili National Hospital system.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | SMS Provider Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your SMS provider settings here. Updated for KODA TECH
    | SMS provider with API key and secret key authentication.
    |
    */
    
    // Kilakona provider endpoints and credentials
    'base_url' => env('SMS_BASE_URL', 'https://messaging.kilakona.co.tz/'),
    'api_url' => env('SMS_API_URL', 'https://messaging.kilakona.co.tz/api/v1/vendor/message/send'),
    'api_key' => env('SMS_API_KEY', 'chimwege'),
    'api_secret' => env('SMS_API_SECRET', 'f5IZEc5o8PeXi9l5ilOo'),
    // Keep legacy secret_key for backward compatibility if referenced in code
    'secret_key' => env('SMS_SECRET_KEY', env('SMS_API_SECRET', 'f5IZEc5o8PeXi9l5ilOo')),
    'sender_id' => env('SMS_SENDER_ID', 'MLG'),
    'sender_name' => env('SMS_SENDER_NAME', 'MLG'),
    'message_type' => env('SMS_MESSAGE_TYPE', 'text'),
    'delivery_report_url' => env('SMS_DELIVERY_REPORT_URL'),
    'test_mode' => env('SMS_TEST_MODE', false),

    /*
    |--------------------------------------------------------------------------
    | SMS Service Settings
    |--------------------------------------------------------------------------
    |
    | General settings for SMS service behavior
    |
    */

    'enabled' => env('SMS_ENABLED', false),
    'timeout' => env('SMS_TIMEOUT', 30), // seconds
    'verify_ssl' => env('SMS_VERIFY_SSL', false),
    'retry_attempts' => env('SMS_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('SMS_RETRY_DELAY', 60), // seconds

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limits to prevent abuse and overwhelming the SMS API
    |
    */

    'rate_limit' => [
        'max_per_hour_per_number' => env('SMS_RATE_LIMIT_PER_HOUR', 5),
        'max_bulk_size' => env('SMS_MAX_BULK_SIZE', 100),
        'delay_between_bulk' => env('SMS_BULK_DELAY', 0.1), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Templates
    |--------------------------------------------------------------------------
    |
    | Predefined SMS message templates for different notification types
    |
    */

    'templates' => [
        'approval_pending' => "Dear {name}, your {type} request has been submitted (Ref: {ref}). You'll be notified once reviewed. - MNH IT",
        'approval_approved' => "Dear {name}, your {type} request has been APPROVED (Ref: {ref}). You can now proceed. - MNH IT",
        'approval_rejected' => "Dear {name}, your {type} request was REJECTED (Ref: {ref}). Reason: {reason}. Contact IT for help. - MNH IT",
        'approver_notification' => "New {type} request from {requester} ({department}) needs approval (Ref: {ref}). Please review in system. - MNH IT",
        'access_granted' => "ACCESS GRANTED: {requester} has {type} access (Ref: {ref}). Please assist as needed. - MNH IT",
        'system_maintenance' => "SYSTEM MAINTENANCE: {system} will be unavailable from {start} to {end}. Plan accordingly. - MNH IT",
        'bulk_announcement' => "{message} - MNH IT"
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how SMS attempts are logged
    |
    */

    'logging' => [
        'enabled' => env('SMS_LOGGING_ENABLED', true),
        'log_successful' => env('SMS_LOG_SUCCESSFUL', true),
        'log_failed' => env('SMS_LOG_FAILED', true),
        'log_level' => env('SMS_LOG_LEVEL', 'info'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Phone Number Formatting
    |--------------------------------------------------------------------------
    |
    | Configure phone number formatting for Tanzania
    |
    */

    'phone_format' => [
        'country_code' => '255',
        'remove_leading_zero' => true,
        'valid_prefixes' => ['6', '7'], // Tanzanian mobile prefixes
        'min_length' => 9,
        'max_length' => 12,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how SMS jobs are queued
    |
    */

    'queue' => [
        'enabled' => env('SMS_QUEUE_ENABLED', true),
        'queue_name' => env('SMS_QUEUE_NAME', 'sms'),
        'max_tries' => env('SMS_MAX_TRIES', 3),
        'backoff' => [60, 300, 900], // Retry delays in seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Development/Testing Settings
    |--------------------------------------------------------------------------
    |
    | Settings for development and testing environments
    |
    */

    'testing' => [
        'fake_send' => env('SMS_FAKE_SEND', false),
        'test_numbers' => explode(',', env('SMS_TEST_NUMBERS', '')),
        'log_to_file' => env('SMS_LOG_TO_FILE', false),
        'mock_responses' => env('SMS_MOCK_RESPONSES', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Emergency Settings
    |--------------------------------------------------------------------------
    |
    | Emergency contact and fallback settings
    |
    */

    'emergency' => [
        'admin_phone' => env('SMS_ADMIN_PHONE'),
        'fallback_enabled' => env('SMS_FALLBACK_ENABLED', false),
        'fallback_provider' => env('SMS_FALLBACK_PROVIDER'),
    ],

];