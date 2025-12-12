<?php

namespace Native\Desktop\Enums;

enum PermissionStatusEnum: string
{
    case NOT_DETERMINED = 'not-determined';
    case GRANTED = 'granted';
    case DENIED = 'denied';
    case RESTRICTED = 'restricted';
    case UNKNOWN = 'unknown';

    public function isGranted(): bool
    {
        return $this === self::GRANTED;
    }

    public function isDenied(): bool
    {
        return $this === self::DENIED;
    }

    public function isNotDetermined(): bool
    {
        return $this === self::NOT_DETERMINED;
    }

    public function needsRequest(): bool
    {
        return $this === self::NOT_DETERMINED;
    }
}
