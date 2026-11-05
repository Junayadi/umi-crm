<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_unauthenticated_home_request_redirects_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }
}
