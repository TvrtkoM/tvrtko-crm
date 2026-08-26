<?php

use Database\Seeders\DatabaseSeeder;

test('register route no longer exists', function () {
    $this->get('/register')->assertNotFound();
});

test('seeded demo user can log in and reach the dashboard', function () {
    $this->seed(DatabaseSeeder::class);

    $response = $this->post(route('login.store'), [
        'email' => 'demo@example.com',
        'password' => 'DemoUser',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});
