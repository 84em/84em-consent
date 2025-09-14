# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

84EM Consent is a lightweight WordPress plugin that provides a simple cookie consent banner for sites using only strictly necessary cookies (no tracking, analytics, or marketing cookies). Built for 84em.com and similar privacy-focused sites.

## Key Architecture

The plugin follows WordPress coding standards with a simple, single-class architecture:

- **Main Plugin Class**: `EightyFourEM\Consent\SimpleConsent` - Singleton pattern for initialization
- **Frontend Assets**: Separate CSS/JS files that are minified during build
- **AJAX Handler**: Server-side cookie setting as backup to client-side storage
- **Dual Storage**: Uses both localStorage and cookies for redundancy

## Build and Development Commands

```bash
# Install dependencies (first time only)
npm install

# Build minified assets with source maps
npm run build

# Development mode with file watching
npm run dev

# Create installable WordPress plugin ZIP
./build.sh

# Clean build artifacts
npm run clean

# Full release (build + package)
npm run release
```

## Testing Approach

Manual testing is required for this plugin. Key areas to test:

1. **Banner Display**: Verify banner appears for new users, hidden after consent
2. **Cookie Storage**: Check both localStorage and cookie are set correctly
3. **Version Bumping**: Test that changing `cookie_version` re-shows banner
4. **Accessibility**: Keyboard navigation, screen reader compatibility
5. **Mobile Responsiveness**: Test on various viewport sizes

Test consent state using browser console:
```javascript
// Check consent status
window.e84ConsentAPI.hasConsent()

// Reset consent for testing
window.e84ConsentAPI.resetConsent()
```

## Plugin Configuration

The plugin uses a filter-based configuration system. All settings are applied via the `84em_consent_simple_config` filter in theme's functions.php or a custom plugin.

Available configuration options:
- `brand_name`: Site name displayed in banner
- `accent_color`: Primary button and accent color (hex)
- `banner_text`: Consent message text
- `policy_url`: Link to privacy policy
- `cookie_version`: Version string to trigger re-consent
- `cookie_duration`: Days before consent expires (default: 180)
- `show_for_logged_in`: Show banner for logged-in users (default: false)

## File Structure

```
84em-consent/
├── 84em-consent.php      # Main plugin file with SimpleConsent class
├── assets/
│   ├── consent.css       # Source CSS (edited manually)
│   ├── consent.js        # Source JavaScript (edited manually)
│   ├── consent.min.css   # Generated - do not edit
│   └── consent.min.js    # Generated - do not edit
├── build.sh              # Build script for creating plugin ZIP
└── package.json          # Node dependencies and build scripts
```

## Important Development Patterns

1. **Namespace**: Always use `EightyFourEM\Consent` namespace
2. **Asset Versioning**: Use `cookie_version` config for cache busting
3. **Build Process**: Never edit `.min.*` files directly - they are generated
4. **WordPress Hooks**: Primary hooks used:
   - `wp_enqueue_scripts`: Asset loading
   - `wp_footer`: Banner HTML rendering
   - `wp_ajax_*`: AJAX consent handling

## API Usage

### PHP Helper Function
```php
if (function_exists('e84_has_consent') && e84_has_consent()) {
    // User has accepted cookies
}
```

### JavaScript API
```javascript
// Check consent
if (window.e84ConsentAPI && window.e84ConsentAPI.hasConsent()) {
    // User has accepted
}

// Listen for consent event
document.addEventListener('84em:consent:accepted', function(e) {
    console.log('Consent given:', e.detail);
});
```

## Cookie Details

- **Name**: `84em_consent`
- **Duration**: 180 days (configurable)
- **Data Structure**: JSON with `accepted`, `version`, and `timestamp` fields
- **Storage**: Both localStorage and cookie for redundancy
- **Security**: HttpOnly flag set server-side, SameSite=Lax

## Deployment

The plugin uses a shell script for creating distributable packages:

1. Run `./build.sh` to create versioned ZIP file
2. ZIP includes only production files (no source CSS/JS, no node_modules)
3. Version number extracted from plugin header automatically
4. Install via WordPress admin or manually extract to `/wp-content/plugins/`

## Security Considerations

- Nonce verification on AJAX requests
- Escaped output in all rendered HTML
- No external dependencies or third-party services
- Minimal JavaScript with no eval() or dynamic code execution
- Cookie set with appropriate security flags based on SSL status