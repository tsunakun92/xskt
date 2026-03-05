<?php

namespace Modules\Admin\Database\Seeders;

use Illuminate\Database\Seeder;

use Modules\Admin\Database\Seeders\Prd\PrdUserSeeder;
use Modules\Admin\Database\Seeders\Stg\StgUserSeeder;

class AdminDatabaseSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        // Always run these base seeders in every environment
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
        ]);
    }
}
