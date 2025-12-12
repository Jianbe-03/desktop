import express from 'express';
import { systemPreferences, shell } from 'electron';

const router = express.Router();

/**
 * Get the current media access status for a given media type
 * 
 * Media types: 'microphone', 'camera', 'screen'
 * Returns: 'not-determined', 'granted', 'denied', 'restricted', 'unknown'
 * 
 * Platform support:
 * - macOS: Full support for microphone, camera, and screen
 * - Windows: Support for microphone and camera (screen always returns 'granted')
 * - Linux: Always returns 'granted' (no unified permission system)
 */
router.get('/media-access-status/:mediaType', (req, res) => {
    const { mediaType } = req.params;
    
    if (!['microphone', 'camera', 'screen'].includes(mediaType)) {
        return res.status(400).json({
            error: `Invalid media type: ${mediaType}. Must be one of: microphone, camera, screen`,
        });
    }

    // Linux has no unified permission system
    if (process.platform === 'linux') {
        return res.json({
            result: 'granted',
        });
    }

    // Windows and macOS both support getMediaAccessStatus
    // Note: On Windows, screen always returns 'granted'
    try {
        const status = systemPreferences.getMediaAccessStatus(mediaType as 'microphone' | 'camera' | 'screen');
        res.json({
            result: status,
        });
    } catch (e) {
        res.status(400).json({
            error: e.message,
        });
    }
});

/**
 * Request access to camera or microphone
 * 
 * Platform support:
 * - macOS: Shows system dialog for microphone/camera (10.14 Mojave or newer)
 * - Windows: Returns current status (no programmatic request available)
 * - Linux: Always returns true (no permission system)
 * 
 * Note: Screen recording cannot be requested programmatically on any platform
 */
router.post('/ask-for-media-access', async (req, res) => {
    const { mediaType } = req.body;

    if (!['microphone', 'camera'].includes(mediaType)) {
        return res.status(400).json({
            error: `Invalid media type: ${mediaType}. Must be one of: microphone, camera`,
        });
    }

    // Linux has no permission prompts
    if (process.platform === 'linux') {
        return res.json({
            result: true,
        });
    }

    // Windows doesn't support askForMediaAccess, but we can check current status
    if (process.platform === 'win32') {
        try {
            const status = systemPreferences.getMediaAccessStatus(mediaType as 'microphone' | 'camera');
            res.json({
                result: status === 'granted',
            });
        } catch (e) {
            res.status(400).json({
                error: e.message,
            });
        }
        return;
    }

    // macOS - can actually prompt for permission
    try {
        const granted = await systemPreferences.askForMediaAccess(mediaType as 'microphone' | 'camera');
        res.json({
            result: granted,
        });
    } catch (e) {
        res.status(400).json({
            error: e.message,
        });
    }
});

/**
 * Open System Settings/Preferences to the privacy pane for the given type
 * 
 * Platform support:
 * - macOS: Opens System Settings to specific privacy pane
 * - Windows: Opens Windows Settings to privacy page
 * - Linux: Not supported (no unified settings app)
 */
router.post('/open-system-preferences', async (req, res) => {
    const { type } = req.body;

    if (process.platform === 'linux') {
        return res.status(400).json({
            error: 'Opening system preferences is not supported on Linux (no unified settings app)',
        });
    }

    if (process.platform === 'win32') {
        // Windows Settings URIs
        const windowsUrls: Record<string, string> = {
            'microphone': 'ms-settings:privacy-microphone',
            'camera': 'ms-settings:privacy-webcam',
            'screen': 'ms-settings:privacy-broadcastingandrecording',
            'location': 'ms-settings:privacy-location',
            'notifications': 'ms-settings:privacy-notifications',
            'contacts': 'ms-settings:privacy-contacts',
            'calendar': 'ms-settings:privacy-calendar',
            'phone-calls': 'ms-settings:privacy-phonecalls',
            'email': 'ms-settings:privacy-email',
            'documents': 'ms-settings:privacy-documents',
            'pictures': 'ms-settings:privacy-pictures',
            'videos': 'ms-settings:privacy-videos',
            'file-system': 'ms-settings:privacy-broadfilesystemaccess',
        };

        const url = windowsUrls[type];

        if (!url) {
            return res.status(400).json({
                error: `Invalid preference type for Windows: ${type}. Valid types: ${Object.keys(windowsUrls).join(', ')}`,
            });
        }

        try {
            await shell.openExternal(url);
            res.sendStatus(200);
        } catch (e) {
            res.status(400).json({
                error: e.message,
            });
        }
        return;
    }

    // macOS System Preferences URLs
    const macUrls: Record<string, string> = {
        'microphone': 'x-apple.systempreferences:com.apple.preference.security?Privacy_Microphone',
        'camera': 'x-apple.systempreferences:com.apple.preference.security?Privacy_Camera',
        'screen': 'x-apple.systempreferences:com.apple.preference.security?Privacy_ScreenCapture',
        'accessibility': 'x-apple.systempreferences:com.apple.preference.security?Privacy_Accessibility',
        'full-disk-access': 'x-apple.systempreferences:com.apple.preference.security?Privacy_AllFiles',
        'documents': 'x-apple.systempreferences:com.apple.preference.security?Privacy_DocumentsFolder',
        'downloads': 'x-apple.systempreferences:com.apple.preference.security?Privacy_DownloadsFolder',
        'photos': 'x-apple.systempreferences:com.apple.preference.security?Privacy_Photos',
        'contacts': 'x-apple.systempreferences:com.apple.preference.security?Privacy_Contacts',
        'calendars': 'x-apple.systempreferences:com.apple.preference.security?Privacy_Calendars',
        'reminders': 'x-apple.systempreferences:com.apple.preference.security?Privacy_Reminders',
        'location': 'x-apple.systempreferences:com.apple.preference.security?Privacy_LocationServices',
    };

    const url = macUrls[type];

    if (!url) {
        return res.status(400).json({
            error: `Invalid preference type for macOS: ${type}. Valid types: ${Object.keys(macUrls).join(', ')}`,
        });
    }

    try {
        await shell.openExternal(url);
        res.sendStatus(200);
    } catch (e) {
        res.status(400).json({
            error: e.message,
        });
    }
});

export default router;
