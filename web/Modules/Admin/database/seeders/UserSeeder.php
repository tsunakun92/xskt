<?php

namespace Modules\Admin\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

use App\Models\BaseModel;
use Modules\Admin\Models\User;

class UserSeeder extends Seeder {
    const ROLE_ADMIN_ID = '2';

    const ROLE_CLEANER_ID  = '3';

    /**
     * Run the database seeds.
     */
    public function run(): void {
        User::truncate();
        $now  = now();
        $data = [
            [
                'id'         => 1,
                'name'       => 'Super Admin',
                'email'      => 'superadmin@test.com',
                'username'   => 'sadmin',
                'password'   => Hash::make('sadminsadmin'),
                'role_id'    => 1,
                'status'     => BaseModel::STATUS_ACTIVE,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id'         => 2,
                'name'       => 'Admin',
                'email'      => 'admin@test.com',
                'username'   => 'admin',
                'password'   => Hash::make('adminadmin'),
                'role_id'    => self::ROLE_ADMIN_ID,
                'status'     => BaseModel::STATUS_ACTIVE,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id'         => 3,
                'name'       => 'User',
                'email'      => 'user@bisync.co.jp',
                'username'   => 'user',
                'password'   => Hash::make('useruser'),
                'role_id'    => self::ROLE_CLEANER_ID,
                'status'     => BaseModel::STATUS_ACTIVE,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
        User::insert($data);
    }
}
