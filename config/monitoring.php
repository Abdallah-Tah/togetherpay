<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for monitoring, alerting, and observability.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Health Check Settings
    |--------------------------------------------------------------------------
    */

    'health_check' => [
        // Enable/disable health check endpoints
        'enabled' => env('HEALTH_CHECK_ENABLED', true),

        // Cache health check results for this many seconds
        'cache_ttl' => env('HEALTH_CHECK_CACHE_TTL', 60),

        // Checks to perform
        'checks' => [
            'database' => true,
            'redis' => true,
            'storage' => true,
            'queue' => true,
        ],

        // Alert if response time exceeds threshold (milliseconds)
        'response_time_threshold' => [
            'database' => 100,
            'redis' => 50,
            'storage' => 200,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Tracking (Sentry)
    |--------------------------------------------------------------------------
    */

    'sentry' => [
        'dsn' => env('SENTRY_LARAVEL_DSN'),
        'environment' => env('APP_ENV', 'production'),
        'release' => env('APP_VERSION', '1.0.0'),
        'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.1),
        'profiles_sample_rate' => (float) env('SENTRY_PROFILES_SAMPLE_RATE', 0.1),

        // Send PII (personally identifiable information)
        'send_default_pii' => false,

        // Breadcrumb settings
        'breadcrumbs' => [
            'sql_queries' => true,
            'sql_bindings' => false, // Don't log sensitive data
            'cache' => true,
            'redis' => true,
        ],

        // Don't report these exceptions
        'ignore_exceptions' => [
            \Illuminate\Auth\AuthenticationException::class,
            \Illuminate\Validation\ValidationException::class,
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    */

    'apm' => [
        'enabled' => env('APM_ENABLED', false),
        'service_name' => env('APM_SERVICE_NAME', 'togetherpay-api'),
        'environment' => env('APP_ENV', 'production'),

        // Transaction sample rate (0.0 to 1.0)
        'sample_rate' => (float) env('APM_SAMPLE_RATE', 0.1),

        // Slow query threshold (milliseconds)
        'slow_query_threshold' => 1000,

        // Track these metrics
        'metrics' => [
            'requests' => true,
            'database_queries' => true,
            'redis_commands' => true,
            'queue_jobs' => true,
            'external_requests' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */

    'logging' => [
        // Log level for production (debug, info, notice, warning, error, critical, alert, emergency)
        'level' => env('LOG_LEVEL', 'warning'),

        // Channels to use in production
        'channels' => env('LOG_STACK', 'daily'),

        // Log file retention (days)
        'retention_days' => 14,

        // Log slow queries
        'slow_queries' => [
            'enabled' => true,
            'threshold_ms' => 1000,
        ],

        // Log failed jobs
        'failed_jobs' => true,

        // Log API requests (for debugging)
        'api_requests' => env('LOG_API_REQUESTS', false),

        // Redact sensitive fields from logs
        'redact_fields' => [
            'password',
            'password_confirmation',
            'token',
            'api_key',
            'secret',
            'credit_card',
            'cvv',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Alerting
    |--------------------------------------------------------------------------
    */

    'alerting' => [
        // Slack webhook for critical alerts
        'slack' => [
            'enabled' => env('SLACK_ALERTS_ENABLED', false),
            'webhook_url' => env('SLACK_WEBHOOK_URL'),
            'channel' => env('SLACK_CHANNEL', '#alerts'),
            'username' => 'TogetherPay Monitor',
        ],

        // Email alerts
        'email' => [
            'enabled' => env('EMAIL_ALERTS_ENABLED', false),
            'recipients' => explode(',', env('ALERT_EMAIL_RECIPIENTS', 'ops@togetherpay.app')),
        ],

        // Alert thresholds
        'thresholds' => [
            'error_rate_per_minute' => 10,
            'failed_jobs_per_hour' => 50,
            'response_time_ms' => 2000,
            'database_connections' => 80, // Percentage
            'disk_usage' => 90, // Percentage
            'memory_usage' => 90, // Percentage
        ],

        // Alert on specific errors
        'alert_on' => [
            'database_connection_failure' => true,
            'redis_connection_failure' => true,
            'queue_failure' => true,
            'payment_api_failure' => true,
            'webhook_validation_failure' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics Collection
    |--------------------------------------------------------------------------
    */

    'metrics' => [
        'enabled' => env('METRICS_ENABLED', true),

        // Store metrics in Redis for this many seconds
        'ttl' => 3600, // 1 hour

        // Collect these metrics
        'collect' => [
            'api_requests' => true,
            'database_queries' => true,
            'cache_hits_misses' => true,
            'queue_jobs' => true,
            'payment_requests' => true,
            'user_registrations' => true,
            'login_attempts' => true,
        ],

        // Export metrics to external systems
        'export' => [
            'prometheus' => env('PROMETHEUS_ENABLED', false),
            'cloudwatch' => env('CLOUDWATCH_ENABLED', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Uptime Monitoring
    |--------------------------------------------------------------------------
    */

    'uptime' => [
        // External uptime monitoring services
        'services' => [
            'pingdom' => env('PINGDOM_ENABLED', false),
            'uptimerobot' => env('UPTIMEROBOT_ENABLED', false),
        ],

        // Endpoints to monitor
        'endpoints' => [
            'https://api.togetherpay.app/api/health/alive',
            'https://api.togetherpay.app/up',
        ],

        // Expected response time (seconds)
        'expected_response_time' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Monitoring
    |--------------------------------------------------------------------------
    */

    'database' => [
        'monitor_connections' => true,
        'alert_on_connection_threshold' => 80, // Percentage

        'monitor_slow_queries' => true,
        'slow_query_threshold_ms' => 1000,

        'monitor_deadlocks' => true,
        'monitor_replication_lag' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Monitoring
    |--------------------------------------------------------------------------
    */

    'queue' => [
        'monitor_size' => true,
        'alert_on_size_threshold' => 1000,

        'monitor_wait_time' => true,
        'alert_on_wait_time_minutes' => 10,

        'monitor_failed_jobs' => true,
        'alert_on_failed_jobs_threshold' => 50,
    ],

];
