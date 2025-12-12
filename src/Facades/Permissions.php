<?php

namespace Native\Desktop\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string status(string $type)
 * @method static bool isGranted(string $type)
 * @method static bool isDenied(string $type)
 * @method static bool isNotDetermined(string $type)
 * @method static bool request(string $type)
 * @method static void openSystemPreferences(string $type)
 * @method static bool ensure(string $type)
 *
 * @see \Native\Desktop\Permissions
 */
class Permissions extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Native\Desktop\Permissions::class;
    }
}
