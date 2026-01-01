<?php

use Laravel\Octane\ApplicationFactory;
use Laravel\Octane\FrankenPhp\FrankenPhpClient;
use Laravel\Octane\Worker;
use Illuminate\Queue\WorkerOptions;
use Acme\FrankenPHPQueue\FrankenPHPJob;

if ((!($_SERVER['FRANKENPHP_WORKER'] ?? false)) || !function_exists('frankenphp_handle_request')) {
    echo 'FrankenPHP must be in worker mode to use this script.';
    exit(1);
}

ignore_user_abort(true);

$basePath = $_SERVER['APP_BASE_PATH'] ?? $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__, 4);

if (!file_exists($basePath . '/bootstrap/app.php')) {
    fwrite(STDERR, "Application path not found at: $basePath\n");
    exit(1);
}

require_once $basePath . '/vendor/autoload.php';

$frankenPhpClient = new FrankenPhpClient();

$worker = tap(new Worker(
    new ApplicationFactory($basePath),
    $frankenPhpClient
))->boot();

$requestCount = 0;
$maxRequests = $_ENV['MAX_REQUESTS'] ?? $_SERVER['MAX_REQUESTS'] ?? 1000;
$queueOptions = new WorkerOptions();

try {
    $handleRequest = static function ($payload) use ($worker, $queueOptions) {
        try {
            $app = $worker->application();
            $connection = $app['queue']->connection('frankenphp');

            $job = new FrankenPHPJob(
                $app,
                $connection,
                $payload,
                'default'
            );

            $app['queue.worker']->process('frankenphp', $job, $queueOptions);

        } catch (Throwable $e) {
            error_log("Worker Critical Error: " . $e->getMessage());
            if ($worker) {
                try {
                    report($e);
                } catch (Throwable $ex) {
                    // Silent fail
                }
            }
        }
    };

    while ($requestCount < $maxRequests && frankenphp_handle_request($handleRequest)) {
        $requestCount++;
    }
} finally {
    $worker?->terminate();
    gc_collect_cycles();
}