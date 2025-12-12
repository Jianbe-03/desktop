<?php

namespace Native\Desktop\Enums;

/**
 * System preference types for opening privacy settings.
 *
 * Note: Not all types are available on all platforms:
 * - macOS: All types except Windows-specific ones
 * - Windows: microphone, camera, screen, location, notifications, contacts,
 *            calendar, phone-calls, email, documents, pictures, videos, file-system
 * - Linux: Not supported
 */
enum SystemPreferenceTypeEnum: string
{
    // Cross-platform (macOS & Windows)
    case MICROPHONE = 'microphone';
    case CAMERA = 'camera';
    case SCREEN = 'screen';
    case LOCATION = 'location';
    case CONTACTS = 'contacts';
    case DOCUMENTS = 'documents';

    // macOS only
    case ACCESSIBILITY = 'accessibility';
    case FULL_DISK_ACCESS = 'full-disk-access';
    case DOWNLOADS = 'downloads';
    case PHOTOS = 'photos';
    case CALENDARS = 'calendars';
    case REMINDERS = 'reminders';

    // Windows only
    case NOTIFICATIONS = 'notifications';
    case CALENDAR = 'calendar';
    case PHONE_CALLS = 'phone-calls';
    case EMAIL = 'email';
    case PICTURES = 'pictures';
    case VIDEOS = 'videos';
    case FILE_SYSTEM = 'file-system';
}
