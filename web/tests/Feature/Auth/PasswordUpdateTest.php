<?php

use Illuminate\Support\Facades\Hash;

use Modules\Admin\Database\Seeders\UserSeeder;
use Modules\Admin\Models\User;

beforeEach(function () {
    $this->seed(UserSeeder::class);
});

test('password can be updated', function () {
    $user = User::find(1);

    $response = $this
        ->actingAs($user)
        ->from('/admin/profile')
        ->put('/password', [
            'current_password'      => 'sadminsadmin',
            'password'              => 'new-password',
            'password_confirmation' => 'new-password',
            '_token'                => csrf_token(),
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/admin/profile');

    $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
});

test('correct password must be provided to update password', function () {
    $user = User::find(1);

    $response = $this
        ->actingAs($user)
        ->from('/admin/profile')
        ->put('/password', [
            'current_password'      => 'wrong-password',
            'password'              => 'new-password',
            'password_confirmation' => 'new-password',
            '_token'                => csrf_token(),
        ]);

    $response
        ->assertSessionHasErrorsIn('updatePassword', 'current_password')
        ->assertRedirect('/admin/profile');
});
