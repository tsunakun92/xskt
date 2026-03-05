<?php

namespace Tests\Unit\Utils;

use Exception;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

use App\Utils\SqlHandler;

class SqlHandlerTest extends TestCase {
    public function test_successful_transaction() {
        $result = SqlHandler::handleTransaction(function () {
            DB::table('users')->insert([
                'name'     => 'Test User',
                'email'    => 'test@example.com',
                'password' => bcrypt('password'),
                'username' => 'testuser',
                'role_id'  => 1,
                'status'   => 1,
            ]);

            return true;
        });

        $this->assertTrue($result);
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_failed_transaction() {
        $result = SqlHandler::handleTransaction(function () {
            throw new Exception('Test exception');
        });

        $this->assertFalse($result);
    }

    public function test_nested_transaction() {
        $result = SqlHandler::handleTransaction(function () {
            DB::table('users')->insert([
                'name'     => 'User 1',
                'email'    => 'user1@example.com',
                'password' => bcrypt('password'),
                'username' => 'user1',
                'role_id'  => 1,
                'status'   => 1,
            ]);

            return SqlHandler::handleTransaction(function () {
                DB::table('users')->insert([
                    'name'     => 'User 2',
                    'email'    => 'user2@example.com',
                    'password' => bcrypt('password'),
                    'username' => 'user2',
                    'role_id'  => 1,
                    'status'   => 1,
                ]);

                return true;
            });
        });

        $this->assertTrue($result);
        $this->assertDatabaseHas('users', ['email' => 'user1@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'user2@example.com']);
    }
}
