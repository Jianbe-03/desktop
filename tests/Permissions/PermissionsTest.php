<?php

use Illuminate\Support\Facades\Http;
use Native\Desktop\Facades\Permissions;

beforeEach(function () {
    config()->set('nativephp-internal.api_url', 'https://api.example.com');
});

it('can get permission status for microphone', function () {
    Http::fake([
        '*/permissions/media-access-status/microphone' => Http::response(['result' => 'granted'], 200),
    ]);

    $status = Permissions::status('microphone');

    expect($status)->toBe('granted');
});

it('can get permission status for camera', function () {
    Http::fake([
        '*/permissions/media-access-status/camera' => Http::response(['result' => 'denied'], 200),
    ]);

    $status = Permissions::status('camera');

    expect($status)->toBe('denied');
});

it('can get permission status for screen', function () {
    Http::fake([
        '*/permissions/media-access-status/screen' => Http::response(['result' => 'not-determined'], 200),
    ]);

    $status = Permissions::status('screen');

    expect($status)->toBe('not-determined');
});

it('can check if permission is granted', function () {
    Http::fake([
        '*/permissions/media-access-status/microphone' => Http::response(['result' => 'granted'], 200),
    ]);

    expect(Permissions::isGranted('microphone'))->toBeTrue();
});

it('can check if permission is denied', function () {
    Http::fake([
        '*/permissions/media-access-status/camera' => Http::response(['result' => 'denied'], 200),
    ]);

    expect(Permissions::isDenied('camera'))->toBeTrue();
});

it('can check if permission is not determined', function () {
    Http::fake([
        '*/permissions/media-access-status/screen' => Http::response(['result' => 'not-determined'], 200),
    ]);

    expect(Permissions::isNotDetermined('screen'))->toBeTrue();
});

it('can request microphone permission', function () {
    Http::fake([
        '*/permissions/ask-for-media-access' => Http::response(['result' => true], 200),
    ]);

    $result = Permissions::request('microphone');

    expect($result)->toBeTrue();
});

it('can request camera permission', function () {
    Http::fake([
        '*/permissions/ask-for-media-access' => Http::response(['result' => false], 200),
    ]);

    $result = Permissions::request('camera');

    expect($result)->toBeFalse();
});

it('can request screen permission', function () {
    Http::fake([
        '*/permissions/open-system-preferences' => Http::response([], 200),
    ]);

    $result = Permissions::request('screen');

    expect($result)->toBeFalse();
});

it('can open system preferences', function () {
    Http::fake([
        '*/permissions/open-system-preferences' => Http::response([], 200),
    ]);

    Permissions::openSystemPreferences('microphone');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.example.com/permissions/open-system-preferences' &&
               $request['type'] === 'microphone';
    });
});

it('can ensure permission', function () {
    Http::fake([
        '*/permissions/media-access-status/microphone' => Http::response(['result' => 'not-determined'], 200),
        '*/permissions/ask-for-media-access' => Http::response(['result' => true], 200),
    ]);

    $result = Permissions::ensure('microphone');

    expect($result)->toBeTrue();
});

it('can ensure permission when already granted', function () {
    Http::fake([
        '*/permissions/media-access-status/camera' => Http::response(['result' => 'granted'], 200),
    ]);

    $result = Permissions::ensure('camera');

    expect($result)->toBeTrue();
});

it('can ensure permission when denied', function () {
    Http::fake([
        '*/permissions/media-access-status/screen' => Http::response(['result' => 'denied'], 200),
        '*/permissions/open-system-preferences' => Http::response([], 200),
    ]);

    $result = Permissions::ensure('screen');

    expect($result)->toBeFalse();
});