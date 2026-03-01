<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

test('registration requires password with minimum 8 characters', function () {
    postJson('/api/v1/auth/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Short1!',
        'password_confirmation' => 'Short1!',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

test('registration requires password with uppercase letter', function () {
    postJson('/api/v1/auth/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'alllowercase123!',
        'password_confirmation' => 'alllowercase123!',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

test('registration requires password with lowercase letter', function () {
    postJson('/api/v1/auth/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'ALLUPPERCASE123!',
        'password_confirmation' => 'ALLUPPERCASE123!',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

test('registration requires password with number', function () {
    postJson('/api/v1/auth/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'NoNumbers!',
        'password_confirmation' => 'NoNumbers!',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

test('registration requires password with special character', function () {
    postJson('/api/v1/auth/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'NoSpecial1',
        'password_confirmation' => 'NoSpecial1',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

test('registration requires password confirmation', function () {
    postJson('/api/v1/auth/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'ValidPass123!',
        'password_confirmation' => 'DifferentPass123!',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

test('registration succeeds with valid password meeting all requirements', function () {
    postJson('/api/v1/auth/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'ValidPass123!',
        'password_confirmation' => 'ValidPass123!',
    ])->assertSuccessful();

    $user = User::where('email', 'test@example.com')->first();
    expect($user)->not->toBeNull();
    expect(Hash::check('ValidPass123!', $user->password))->toBeTrue();
});

test('password update requires all 6 requirements', function () {
    $user = User::factory()->create([
        'password' => 'OldPassword123!',
    ]);

    $this->actingAs($user, 'sanctum');

    // Test minimum length
    putJson('/api/v1/auth/password', [
        'current_password' => 'OldPassword123!',
        'password' => 'Short1!',
        'password_confirmation' => 'Short1!',
    ])->assertUnprocessable();

    // Test uppercase requirement
    putJson('/api/v1/auth/password', [
        'current_password' => 'OldPassword123!',
        'password' => 'alllowercase123!',
        'password_confirmation' => 'alllowercase123!',
    ])->assertUnprocessable();

    // Test lowercase requirement
    putJson('/api/v1/auth/password', [
        'current_password' => 'OldPassword123!',
        'password' => 'ALLUPPERCASE123!',
        'password_confirmation' => 'ALLUPPERCASE123!',
    ])->assertUnprocessable();

    // Test number requirement
    putJson('/api/v1/auth/password', [
        'current_password' => 'OldPassword123!',
        'password' => 'NoNumbers!',
        'password_confirmation' => 'NoNumbers!',
    ])->assertUnprocessable();

    // Test special character requirement
    putJson('/api/v1/auth/password', [
        'current_password' => 'OldPassword123!',
        'password' => 'NoSpecial123',
        'password_confirmation' => 'NoSpecial123',
    ])->assertUnprocessable();

    // Test valid password
    putJson('/api/v1/auth/password', [
        'current_password' => 'OldPassword123!',
        'password' => 'NewValidPass123!',
        'password_confirmation' => 'NewValidPass123!',
    ])->assertSuccessful();

    $user->refresh();
    expect(Hash::check('NewValidPass123!', $user->password))->toBeTrue();
});

test('password reset requires all 6 requirements', function () {
    $user = User::factory()->create();
    $token = \Illuminate\Support\Facades\Password::createToken($user);

    // Test minimum length
    postJson('/api/v1/auth/password/reset', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'Short1!',
        'password_confirmation' => 'Short1!',
    ])->assertUnprocessable();

    // Test uppercase requirement
    postJson('/api/v1/auth/password/reset', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'alllowercase123!',
        'password_confirmation' => 'alllowercase123!',
    ])->assertUnprocessable();

    // Test valid password
    $token = \Illuminate\Support\Facades\Password::createToken($user);
    postJson('/api/v1/auth/password/reset', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NewValidPass123!',
        'password_confirmation' => 'NewValidPass123!',
    ])->assertSuccessful();

    $user->refresh();
    expect(Hash::check('NewValidPass123!', $user->password))->toBeTrue();
});
