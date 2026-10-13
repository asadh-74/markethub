<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SeedDemo extends Command
{
    protected $signature = 'markethub:seed-demo';

    protected $description = 'Seed demonstration accounts only when the local demo database is empty';

    public function handle(): int
    {
        if (config('database.default') !== 'sqlite') {
            $this->error('Demo accounts must only be seeded in a disposable SQLite database.');
            return self::FAILURE;
        }

        if (User::query()->exists()) {
            $this->info('Existing accounts found; preserving demo data.');
            return self::SUCCESS;
        }

        $result = Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\DatabaseSeeder', '--force' => true]);
        $this->output->write(Artisan::output());

        return $result === 0 ? self::SUCCESS : self::FAILURE;
    }
}
