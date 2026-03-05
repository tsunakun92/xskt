<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

use Modules\Admin\Database\Seeders\AdminDatabaseSeeder;
use Modules\Crm\Database\Seeders\CrmDatabaseSeeder;
use Modules\Hr\Database\Seeders\HrDatabaseSeeder;

class DatabaseSeeder extends Seeder {
    /**
     * Seed the application's database.
     */
    public function run(): void {
        $this->call([
            AdminDatabaseSeeder::class,
            CrmDatabaseSeeder::class,
            HrDatabaseSeeder::class,
        ]);

        // clear cache
        Cache::flush();
    }
}
