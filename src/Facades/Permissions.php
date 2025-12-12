<?php

namespace Native\Desktop\Facades;

use Illuminate\Support\Facades\Facade;
use Native\Desktop\Enums\PermissionStatusEnum;
use Native\Desktop\Enums\PermissionTypeEnum;
use Native\Desktop\Enums\SystemPreferenceTypeEnum;

/**
 * @method static array platform()
 * @method static bool isMacOS()
 * @method static bool isWindows()
 * @method static bool isLinux()
 * @method static PermissionStatusEnum status(PermissionTypeEnum|string $type)
 * @method static bool isGranted(PermissionTypeEnum|string $type)
 * @method static bool isDenied(PermissionTypeEnum|string $type)
 * @method static bool isNotDetermined(PermissionTypeEnum|string $type)
 * @method static array all()
 * @method static bool request(PermissionTypeEnum|string $type)
 * @method static bool requestMicrophone()
 * @method static bool requestCamera()
 * @method static void openSystemPreferences(SystemPreferenceTypeEnum|string $type)
 * @method static array accessibilityStatus(bool $prompt = false)
 * @method static bool hasAccessibility(bool $prompt = false)
 * @method static bool ensure(PermissionTypeEnum|string $type)
 * @method static bool has(PermissionTypeEnum|string $type)
 * @method static bool canRecordScreen()
 * @method static bool canUseCamera()
 * @method static bool canUseMicrophone()
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
