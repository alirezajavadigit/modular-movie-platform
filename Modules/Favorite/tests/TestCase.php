<?php

namespace Modules\Favorite\Tests;

use Modules\Auth\Models\User;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
