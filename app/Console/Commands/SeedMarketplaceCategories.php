<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SeedMarketplaceCategories extends Command
{
    protected $signature = 'markethub:seed-categories';

    protected $description = 'Ensure standard marketplace categories exist without deleting data';

    public function handle(): int
    {
        foreach (['Electronics', 'Home & Kitchen', 'Fashion', 'Books', 'Sports'] as $name) {
            Category::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }

        $this->info('Marketplace categories are ready.');

        return self::SUCCESS;
    }
}
