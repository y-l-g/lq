<?php

namespace Pogo\Queue\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class InstallCommand extends Command
{
    protected $signature = 'pogo:queue:install';
    protected $description = 'Install the Pogo Queue components and configuration';

    public function handle()
    {
        $this->info('Installing Pogo Queue...');
        $this->publishStubs();
        $this->configureQueueDriver();
        $this->updateEnvFile();

        $this->newLine();
        $this->info('Installation complete.');
        $this->comment('Run Octane with: php artisan octane:start --server=frankenphp --caddyfile=Caddyfile');
    }

    protected function publishStubs()
    {
        if (!file_exists(public_path('queue-worker.php'))) {
            copy(__DIR__ . '/../../stubs/queue-worker.php', public_path('queue-worker.php'));
            $this->comment('Created public/queue-worker.php');
        } else {
            $this->warn('public/queue-worker.php already exists.');
        }

        if (!file_exists(base_path('Caddyfile'))) {
            copy(__DIR__ . '/../../stubs/Caddyfile', base_path('Caddyfile'));
            $this->comment('Created Caddyfile example.');
        } else {
            $this->warn('Caddyfile already exists. Please manually add the configuration.');
        }
    }

    protected function configureQueueDriver()
    {
        $configPath = config_path('queue.php');

        if (!file_exists($configPath)) {
            $this->error('config/queue.php not found. Please run "php artisan config:publish queue" first.');
            return;
        }

        $content = file_get_contents($configPath);
        if (Str::contains($content, "'driver' => 'pogo'")) {
            $this->warn('Pogo driver configuration already exists in config/queue.php.');
            return;
        }

        $pogoConfig = <<<PHP
        
        'pogo' => [
            'driver' => 'pogo',
            'queue' => 'default',
            'retry_after' => 90,
        ],
PHP;

        if (Str::contains($content, "'connections' => [")) {
            $content = Str::replaceFirst("'connections' => [", "'connections' => [" . $pogoConfig, $content);
            file_put_contents($configPath, $content);
            $this->info('Injected "pogo" driver into config/queue.php.');
        } else {
            $this->error('Could not find "connections" array in config/queue.php. Please add the pogo driver manually.');
        }
    }

    protected function updateEnvFile()
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            return;
        }

        $content = file_get_contents($envPath);

        if (Str::contains($content, 'QUEUE_CONNECTION=')) {
            $content = preg_replace('/^QUEUE_CONNECTION=.*$/m', 'QUEUE_CONNECTION=pogo', $content);
            $this->info('Updated QUEUE_CONNECTION to "pogo" in .env file.');
        } else {
            $content .= "\nQUEUE_CONNECTION=pogo\n";
            $this->info('Added QUEUE_CONNECTION=pogo to .env file.');
        }

        file_put_contents($envPath, $content);
    }
}