<?php

namespace Haybea\Trashcan\Console\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'trashcan:install';

    protected $description = 'Install Laravel Trashcan';

    public function handle(): void
    {
        $this->info('Installing Laravel Trashcan...');

        // Publish config
        $this->call('vendor:publish', [
            '--tag' => 'trashcan-config',
        ]);

        // Publish migrations
        $this->call('vendor:publish', [
            '--tag' => 'trashcan-migrations',
        ]);

        // Run migrations
        if ($this->confirm('Would you like to run migrations now?', true)) {
            $this->call('migrate');
        }

        $this->info('Laravel Trashcan installed successfully.');
        $this->info('Visit /trashcan to view your dashboard.');
    }
}