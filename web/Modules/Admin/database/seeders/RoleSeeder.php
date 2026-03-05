<?php

namespace Modules\Admin\Database\Seeders;

use Illuminate\Database\Seeder;

use Modules\Admin\Models\Role;

class RoleSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        Role::truncate();
        $data = [
            [
                'name'       => 'Super Admin',
                'code'       => Role::ROLE_SUPER_ADMIN_CODE,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Admin',
                'code'       => Role::ROLE_ADMIN_CODE,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Customer',
                'code'       => Role::ROLE_CUSTOMER_CODE,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        Role::insert($data);
    }
}
