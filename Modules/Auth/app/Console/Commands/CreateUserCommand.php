<?php

namespace Modules\Auth\Console\Commands;

use Illuminate\Console\Command;
use Modules\Auth\Contracts\UserRepositoryInterface;
use Modules\Auth\DTOs\RegisterDTO;
use Tymon\JWTAuth\Facades\JWTAuth;

class CreateUserCommand extends Command
{
    protected $signature = 'auth:create-user
        {--name= : The name of the user}
        {--email= : The email address}
        {--phone= : The phone number}
        {--password= : The password}';

    protected $description = 'Create a new user and generate a JWT token';

    public function handle(UserRepositoryInterface $userRepository): int
    {
        $name = $this->option('name') ?? $this->ask('Name');
        $email = $this->option('email');
        $phone = $this->option('phone');

        if ($email === null && $phone === null) {
            $email = $this->ask('Email (leave blank to skip)');
            if (! $email) {
                $phone = $this->ask('Phone');
            }
        }

        $password = $this->option('password') ?? $this->secret('Password');

        $dto = new RegisterDTO(
            name: $name,
            email: $email ?: null,
            phone: $phone ?: null,
            password: $password,
            channel: $email ? 'email' : 'phone',
        );

        $user = $userRepository->create($dto);

        $token = JWTAuth::fromUser($user);

        $this->table(
            ['Field', 'Value'],
            [
                ['ID', $user->id],
                ['Name', $user->name],
                ['Email', $user->email ?? '—'],
                ['Phone', $user->phone ?? '—'],
            ],
        );

        $this->newLine();
        $this->info('JWT Token:');
        $this->line($token);

        return self::SUCCESS;
    }
}
