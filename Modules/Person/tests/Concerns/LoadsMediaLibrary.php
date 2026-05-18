<?php

declare(strict_types=1);

namespace Modules\Person\Tests\Concerns;

trait LoadsMediaLibrary
{
    protected function loadMediaLibraryMigration(): void
    {
        if ($this->app['db']->connection()->getSchemaBuilder()->hasTable('media')) {
            return;
        }

        $this->artisan('vendor:publish', [
            '--provider' => 'Spatie\MediaLibrary\MediaLibraryServiceProvider',
            '--tag'      => 'medialibrary-migrations',
            '--force'    => true,
        ])->run();

        $this->artisan('migrate', ['--force' => true])->run();
    }
}
