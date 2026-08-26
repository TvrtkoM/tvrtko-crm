<?php

use App\Models\User;

test('the home route redirects guests to login', function () {
    $this->get(route('home'))->assertRedirect(route('login'));
});

test('the home route redirects authenticated users to the dashboard', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('home'))->assertRedirect(route('dashboard'));
});
