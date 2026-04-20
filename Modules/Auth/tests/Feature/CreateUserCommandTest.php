<?php

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_user_when_all_options_provided(): void
    {
        $this->artisan('auth:create-user', [
            '--name' => 'CLI User',
            '--email' => 'cli@example.com',
            '--password' => 'password123',
        ])
            ->expectsOutputToContain('CLI User')
            ->expectsOutputToContain('cli@example.com')
            ->expectsOutputToContain('JWT Token:')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'email' => 'cli@example.com',
            'name' => 'CLI User',
        ]);
    }

    public function test_command_prompts_interactively_for_missing_options(): void
    {
        $this->artisan('auth:create-user')
            ->expectsQuestion('Name', 'Interactive User')
            ->expectsQuestion('Email (leave blank to skip)', 'interactive@example.com')
            ->expectsQuestion('Password', 'password123')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'email' => 'interactive@example.com',
            'name' => 'Interactive User',
        ]);
    }

    public function test_command_outputs_fresh_jwt_token(): void
    {
        $this->artisan('auth:create-user', [
            '--name' => 'Token User',
            '--email' => 'token-cli@example.com',
            '--password' => 'password123',
        ])
            ->expectsOutputToContain('JWT Token:')
            ->assertSuccessful();
    }
}
