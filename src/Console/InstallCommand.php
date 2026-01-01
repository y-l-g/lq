<?php

namespace Pogo\Queue\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'pogo:queue:install';
    protected $description = 'Install the Pogo Queue proxy script';

    public function handle()
    {
        $this->info('Installing Pogo Queue...');

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

        $this->newLine();
        $this->info('Installation complete.');
        $this->info('1. Add QUEUE_CONNECTION=pogo to your .env');
        $this->info('2. Run Octane with: php artisan octane:start --server=frankenphp --caddyfile=Caddyfile');
    }
}