<?php

namespace App\Facades;

use App\Services\ApiResponseService;
use Illuminate\Support\Facades\Facade;

final class ApiResponse extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ApiResponseService::class;
    }
}
