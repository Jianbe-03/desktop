<?php

namespace Native\Desktop;

use Native\Desktop\Client\Client;

class Permissions
{
    public function __construct(protected Client $client) {}

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
    public function status(string $type): string
    {
        if (!in_array($type, ['microphone', 'camera', 'screen'])) {
            throw new \InvalidArgumentException("Invalid permission type: {$type}. Must be one of: microphone, camera, screen");
        }

        $result = $this->client->get("permissions/media-access-status/{$type}")->json('result');

        return $result;
    }

    /**
     * Check if permission is granted for a given media type.
     */
    public function isGranted(string $type): bool
    {
        return $this->status($type) === 'granted';
    }

    /**
     * Check if permission is denied for a given media type.
     */
    public function isDenied(string $type): bool
    {
        return $this->status($type) === 'denied';
    }

    /**
     * Check if permission has not been determined yet (user hasn't been prompted).
     */
    public function isNotDetermined(string $type): bool
    {
        return $this->status($type) === 'not-determined';
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
    public function request(string $type): bool
    {
        if (!in_array($type, ['microphone', 'camera', 'screen'])) {
            throw new \InvalidArgumentException("Invalid permission type: {$type}. Must be one of: microphone, camera, screen");
        }

        if ($type === 'screen') {
            // Screen recording cannot be requested programmatically
            // Open system preferences instead
            $this->openSystemPreferences('screen');

            return false;
        }

        return $this->client->post('permissions/ask-for-media-access', [
            'mediaType' => $type,
        ])->json('result', false);
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
    public function openSystemPreferences(string $type): void
    {
        $this->client->post('permissions/open-system-preferences', [
            'type' => $type,
        ]);
    }

    /**
     * Shorthand method to check permission and request if not determined.
     *
     * Returns true if permission is granted (either already or after requesting).
     * Returns false if permission is denied or restricted.
     *
     * For screen recording, this will open system preferences if not granted.
     */
    public function ensure(string $type): bool
    {
        $status = $this->status($type);

        if ($status === 'granted') {
            return true;
        }

        if ($status === 'not-determined') {
            return $this->request($type);
        }

        // Permission is denied or restricted, open system preferences
        $this->openSystemPreferences($type);

        return false;
    }
}
