<?php

use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

use Modules\Admin\Models\User;

test('email verification screen can be rendered', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get('/verify-email');

    $response->assertStatus(200);
});

test('email can be verified', function () {
    $user = User::factory()->unverified()->create();

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(route('admin', absolute: false) . '?verified=1');
});

test('email is not verified with invalid hash', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')]
    );

    $this->actingAs($user)->get($verificationUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('verified user is redirected from verification screen', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/verify-email');

    $response->assertRedirect(route('admin', absolute: false));
});

test('verification notification can be sent', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)
        ->post('/email/verification-notification');

    $response->assertSessionHas('status', 'verification-link-sent');
});

test('verification notification not sent to verified user', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post('/email/verification-notification');

    $response->assertRedirect(route('admin', absolute: false));
});

test('already verified user is redirected from verification endpoint', function () {
    $user = User::factory()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    $response->assertRedirect(route('admin', absolute: false) . '?verified=1');
});
