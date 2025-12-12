<?php

use Illuminate\Support\Facades\Http;
use Native\Desktop\Enums\PermissionStatusEnum;
use Native\Desktop\Enums\PermissionTypeEnum;
use Native\Desktop\Enums\SystemPreferenceTypeEnum;
use Native\Desktop\Facades\Permissions;

beforeEach(function () {
    config()->set('nativephp-internal.api_url', 'https://api.example.com');
});

it('can get platform information', function () {
    Http::fake([
        '*/permissions/platform' => Http::response([
            'platform' => 'darwin',
            'isMacOS' => true,
            'isWindows' => false,
            'isLinux' => false,
        ], 200),
    ]);

    $platform = Permissions::platform();

    expect($platform['platform'])->toBe('darwin');
    expect($platform['isMacOS'])->toBeTrue();
    expect($platform['isWindows'])->toBeFalse();
    expect($platform['isLinux'])->toBeFalse();
});

it('can check if running on macOS', function () {
    Http::fake([
        '*/permissions/platform' => Http::response([
            'platform' => 'darwin',
            'isMacOS' => true,
            'isWindows' => false,
            'isLinux' => false,
        ], 200),
    ]);

    expect(Permissions::isMacOS())->toBeTrue();
    expect(Permissions::isWindows())->toBeFalse();
    expect(Permissions::isLinux())->toBeFalse();
});

it('can check if running on Windows', function () {
    Http::fake([
        '*/permissions/platform' => Http::response([
            'platform' => 'win32',
            'isMacOS' => false,
            'isWindows' => true,
            'isLinux' => false,
        ], 200),
    ]);

    expect(Permissions::isMacOS())->toBeFalse();
    expect(Permissions::isWindows())->toBeTrue();
    expect(Permissions::isLinux())->toBeFalse();
});

it('can get permission status for microphone', function () {
    Http::fake([
        '*/permissions/media-access-status/microphone' => Http::response(['result' => 'granted'], 200),
    ]);

    $status = Permissions::status(PermissionTypeEnum::MICROPHONE);

    expect($status)->toBe(PermissionStatusEnum::GRANTED);
    expect($status->isGranted())->toBeTrue();
});

it('can get permission status using string', function () {
    Http::fake([
        '*/permissions/media-access-status/camera' => Http::response(['result' => 'not-determined'], 200),
    ]);

    $status = Permissions::status('camera');

    expect($status)->toBe(PermissionStatusEnum::NOT_DETERMINED);
    expect($status->needsRequest())->toBeTrue();
});

it('can check if permission is granted', function () {
    Http::fake([
        '*/permissions/media-access-status/microphone' => Http::response(['result' => 'granted'], 200),
    ]);

    expect(Permissions::isGranted(PermissionTypeEnum::MICROPHONE))->toBeTrue();
});

it('can check if permission is denied', function () {
    Http::fake([
        '*/permissions/media-access-status/camera' => Http::response(['result' => 'denied'], 200),
    ]);

    expect(Permissions::isDenied(PermissionTypeEnum::CAMERA))->toBeTrue();
});

it('can get all permission statuses', function () {
    Http::fake([
        '*/permissions/all-statuses' => Http::response([
            'microphone' => 'granted',
            'camera' => 'not-determined',
            'screen' => 'denied',
        ], 200),
    ]);

    $statuses = Permissions::all();

    expect($statuses['microphone'])->toBe(PermissionStatusEnum::GRANTED);
    expect($statuses['camera'])->toBe(PermissionStatusEnum::NOT_DETERMINED);
    expect($statuses['screen'])->toBe(PermissionStatusEnum::DENIED);
});

it('can request microphone permission', function () {
    Http::fake([
        '*/permissions/ask-for-media-access' => Http::response(['result' => true], 200),
    ]);

    $granted = Permissions::requestMicrophone();

    expect($granted)->toBeTrue();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'permissions/ask-for-media-access')
            && $request['mediaType'] === 'microphone';
    });
});

it('can request camera permission', function () {
    Http::fake([
        '*/permissions/ask-for-media-access' => Http::response(['result' => false], 200),
    ]);

    $granted = Permissions::requestCamera();

    expect($granted)->toBeFalse();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'permissions/ask-for-media-access')
            && $request['mediaType'] === 'camera';
    });
});

it('opens system preferences for screen recording instead of requesting', function () {
    Http::fake([
        '*/permissions/open-system-preferences' => Http::response([], 200),
    ]);

    $result = Permissions::request(PermissionTypeEnum::SCREEN);

    expect($result)->toBeFalse();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'permissions/open-system-preferences')
            && $request['type'] === 'screen';
    });
});

it('can open system preferences', function () {
    Http::fake([
        '*/permissions/open-system-preferences' => Http::response([], 200),
    ]);

    Permissions::openSystemPreferences(SystemPreferenceTypeEnum::ACCESSIBILITY);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'permissions/open-system-preferences')
            && $request['type'] === 'accessibility';
    });
});

it('can check canRecordScreen', function () {
    Http::fake([
        '*/permissions/media-access-status/screen' => Http::response(['result' => 'granted'], 200),
    ]);

    expect(Permissions::canRecordScreen())->toBeTrue();
});

it('can check canUseCamera', function () {
    Http::fake([
        '*/permissions/media-access-status/camera' => Http::response(['result' => 'denied'], 200),
    ]);

    expect(Permissions::canUseCamera())->toBeFalse();
});

it('can check canUseMicrophone', function () {
    Http::fake([
        '*/permissions/media-access-status/microphone' => Http::response(['result' => 'granted'], 200),
    ]);

    expect(Permissions::canUseMicrophone())->toBeTrue();
});

it('has helper method to check permission', function () {
    Http::fake([
        '*/permissions/media-access-status/microphone' => Http::response(['result' => 'granted'], 200),
    ]);

    expect(Permissions::has(PermissionTypeEnum::MICROPHONE))->toBeTrue();
});

it('ensure returns true if already granted', function () {
    Http::fake([
        '*/permissions/media-access-status/microphone' => Http::response(['result' => 'granted'], 200),
    ]);

    expect(Permissions::ensure(PermissionTypeEnum::MICROPHONE))->toBeTrue();

    Http::assertNotSent(function ($request) {
        return str_contains($request->url(), 'ask-for-media-access');
    });
});

it('ensure requests permission if not determined', function () {
    Http::fake([
        '*/permissions/media-access-status/camera' => Http::response(['result' => 'not-determined'], 200),
        '*/permissions/ask-for-media-access' => Http::response(['result' => true], 200),
    ]);

    expect(Permissions::ensure(PermissionTypeEnum::CAMERA))->toBeTrue();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'ask-for-media-access');
    });
});

it('ensure opens system preferences if denied', function () {
    Http::fake([
        '*/permissions/media-access-status/camera' => Http::response(['result' => 'denied'], 200),
        '*/permissions/open-system-preferences' => Http::response([], 200),
    ]);

    expect(Permissions::ensure(PermissionTypeEnum::CAMERA))->toBeFalse();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'open-system-preferences');
    });
});

it('can check accessibility status', function () {
    Http::fake([
        '*/permissions/accessibility-status' => Http::response([
            'result' => true,
            'supported' => true,
        ], 200),
    ]);

    $status = Permissions::accessibilityStatus();

    expect($status['result'])->toBeTrue();
    expect($status['supported'])->toBeTrue();
});

it('can check accessibility status with prompt', function () {
    Http::fake([
        '*/permissions/accessibility-status?prompt=true' => Http::response([
            'result' => false,
            'supported' => true,
        ], 200),
    ]);

    $status = Permissions::accessibilityStatus(prompt: true);

    expect($status['result'])->toBeFalse();
});

it('can check hasAccessibility', function () {
    Http::fake([
        '*/permissions/accessibility-status' => Http::response([
            'result' => true,
            'supported' => true,
        ], 200),
    ]);

    expect(Permissions::hasAccessibility())->toBeTrue();
});

it('can open Windows settings', function () {
    Http::fake([
        '*/permissions/open-system-preferences' => Http::response([], 200),
    ]);

    Permissions::openSystemPreferences(SystemPreferenceTypeEnum::FILE_SYSTEM);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'permissions/open-system-preferences')
            && $request['type'] === 'file-system';
    });
});
