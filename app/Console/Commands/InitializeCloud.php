<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class InitializeCloud extends Command
{
    protected $signature = 'monoverse:initialize';

    protected $description = 'Apply migrations and seed an empty database without resetting data';

    public function handle(): int
    {
        if ($this->call('migrate', ['--force' => true]) !== 0) {
            return self::FAILURE;
        }

        return $this->call('db:seed', ['--force' => true]);
    }
}
