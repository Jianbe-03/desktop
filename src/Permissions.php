<?php

namespace Native\Desktop;

use Native\Desktop\Client\Client;
use Native\Desktop\Enums\PermissionStatusEnum;
use Native\Desktop\Enums\PermissionTypeEnum;
use Native\Desktop\Enums\SystemPreferenceTypeEnum;

class Permissions
{
    public function __construct(protected Client $client) {}

    /**
     * Get the current platform information.
     *
     * @return array{platform: string, isMacOS: bool, isWindows: bool, isLinux: bool}
     */
    public function platform(): array
    {
        return $this->client->get('permissions/platform')->json();
    }

    /**
     * Check if we're running on macOS.
     */
    public function isMacOS(): bool
    {
        return $this->platform()['isMacOS'];
    }

    /**
     * Check if we're running on Windows.
     */
    public function isWindows(): bool
    {
        return $this->platform()['isWindows'];
    }

    /**
     * Check if we're running on Linux.
     */
    public function isLinux(): bool
    {
        return $this->platform()['isLinux'];
    }

    /**
     * Get the current permission status for a given media type.
     *
     * Returns the status of the permission: granted, denied, not-determined, restricted, or unknown.
     *
     * Platform support:
     * - macOS: Full support for microphone, camera, and screen
     * - Windows: Support for microphone and camera (screen always returns 'granted')
     * - Linux: Always returns 'granted' (no unified permission system)
     */
    public function status(PermissionTypeEnum|string $type): PermissionStatusEnum
    {
        $type = $type instanceof PermissionTypeEnum ? $type->value : $type;

        $result = $this->client->get("permissions/media-access-status/{$type}")->json('result');

        return PermissionStatusEnum::from($result);
    }

    /**
     * Check if permission is granted for a given media type.
     */
    public function isGranted(PermissionTypeEnum|string $type): bool
    {
        return $this->status($type)->isGranted();
    }

    /**
     * Check if permission is denied for a given media type.
     */
    public function isDenied(PermissionTypeEnum|string $type): bool
    {
        return $this->status($type)->isDenied();
    }

    /**
     * Check if permission has not been determined yet (user hasn't been prompted).
     */
    public function isNotDetermined(PermissionTypeEnum|string $type): bool
    {
        return $this->status($type)->isNotDetermined();
    }

    /**
     * Get all permission statuses at once.
     *
     * Returns an array with keys: microphone, camera, screen
     *
     * Platform support:
     * - macOS: Actual permission status from system
     * - Windows: Actual permission status for microphone/camera, 'granted' for screen
     * - Linux: Always 'granted' for all
     *
     * @return array<string, PermissionStatusEnum>
     */
    public function all(): array
    {
        $results = $this->client->get('permissions/all-statuses')->json();

        return [
            'microphone' => PermissionStatusEnum::from($results['microphone']),
            'camera' => PermissionStatusEnum::from($results['camera']),
            'screen' => PermissionStatusEnum::from($results['screen']),
        ];
    }

    /**
     * Request permission for camera or microphone.
     *
     * Platform support:
     * - macOS: Shows system dialog asking the user to grant permission
     * - Windows: Returns current permission status (no programmatic request available)
     * - Linux: Always returns true (no permission system)
     *
     * Note: Screen recording permission cannot be requested programmatically on any platform.
     * Use openSystemPreferences('screen') to guide the user to enable it manually.
     */
    public function request(PermissionTypeEnum|string $type): bool
    {
        $type = $type instanceof PermissionTypeEnum ? $type->value : $type;

        if ($type === 'screen') {
            // Screen recording cannot be requested programmatically
            // Open system preferences instead
            $this->openSystemPreferences(SystemPreferenceTypeEnum::SCREEN);

            return false;
        }

        return $this->client->post('permissions/ask-for-media-access', [
            'mediaType' => $type,
        ])->json('result', false);
    }

    /**
     * Request microphone permission.
     */
    public function requestMicrophone(): bool
    {
        return $this->request(PermissionTypeEnum::MICROPHONE);
    }

    /**
     * Request camera permission.
     */
    public function requestCamera(): bool
    {
        return $this->request(PermissionTypeEnum::CAMERA);
    }

    /**
     * Open System Settings/Preferences to the appropriate permission pane.
     *
     * This is useful for permissions that cannot be requested programmatically,
     * such as screen recording, accessibility, or full disk access.
     *
     * Platform support:
     * - macOS: Opens System Settings to specific privacy pane
     * - Windows: Opens Windows Settings to privacy page
     * - Linux: Not supported (no unified settings app)
     */
    public function openSystemPreferences(SystemPreferenceTypeEnum|string $type): void
    {
        $type = $type instanceof SystemPreferenceTypeEnum ? $type->value : $type;

        $this->client->post('permissions/open-system-preferences', [
            'type' => $type,
        ]);
    }

    /**
     * Check if the app is a trusted accessibility client.
     *
     * This is useful for features that require accessibility permissions,
     * such as global keyboard shortcuts or screen reading.
     *
     * Note: This only works on macOS. On other platforms, this returns true.
     *
     * @param  bool  $prompt  Whether to show a prompt to the user if not trusted (macOS only)
     * @return array{result: bool, supported: bool}
     */
    public function accessibilityStatus(bool $prompt = false): array
    {
        $query = $prompt ? '?prompt=true' : '';

        return $this->client->get("permissions/accessibility-status{$query}")->json();
    }

    /**
     * Check if the app has accessibility permissions.
     *
     * @param  bool  $prompt  Whether to show a prompt to the user if not trusted (macOS only)
     */
    public function hasAccessibility(bool $prompt = false): bool
    {
        return $this->accessibilityStatus($prompt)['result'];
    }

    /**
     * Shorthand method to check permission and request if not determined.
     *
     * Returns true if permission is granted (either already or after requesting).
     * Returns false if permission is denied or restricted.
     *
     * For screen recording, this will open system preferences if not granted.
     */
    public function ensure(PermissionTypeEnum|string $type): bool
    {
        $status = $this->status($type);

        if ($status->isGranted()) {
            return true;
        }

        if ($status->needsRequest()) {
            return $this->request($type);
        }

        // Permission is denied or restricted, open system preferences
        $typeEnum = $type instanceof PermissionTypeEnum ? $type : PermissionTypeEnum::from($type);
        $preferenceType = SystemPreferenceTypeEnum::from($typeEnum->value);
        $this->openSystemPreferences($preferenceType);

        return false;
    }

    /**
     * Alias for status() - check if the app has permission.
     */
    public function has(PermissionTypeEnum|string $type): bool
    {
        return $this->isGranted($type);
    }

    /**
     * Check if screen recording permission is granted.
     */
    public function canRecordScreen(): bool
    {
        return $this->isGranted(PermissionTypeEnum::SCREEN);
    }

    /**
     * Check if camera permission is granted.
     */
    public function canUseCamera(): bool
    {
        return $this->isGranted(PermissionTypeEnum::CAMERA);
    }

    /**
     * Check if microphone permission is granted.
     */
    public function canUseMicrophone(): bool
    {
        return $this->isGranted(PermissionTypeEnum::MICROPHONE);
    }
}
