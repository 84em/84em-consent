# 84EM Consent

A lightweight WordPress cookie consent banner for strictly necessary cookies only. Built specifically for 84em.com and sites that use only essential cookies (Cloudflare, security, functionality).

## Features

- **Simple & Lightweight**: ~2KB minified JS, ~1.8KB minified CSS
- **No Dependencies**: Pure JavaScript, no jQuery or external libraries
- **Strictly Necessary Only**: Simple consent for essential cookies only
- **Accessibility**: Full keyboard navigation, ARIA labels, focus management
- **Performance**: Minified assets with source maps, lazy loading
- **Privacy-First**: No tracking, no analytics, no marketing cookies

## Installation

### Via WordPress Admin

1. Run `./build.sh` to create the plugin ZIP
2. Go to **WordPress Admin → Plugins → Add New**
3. Click **Upload Plugin**
4. Choose `84em-consent-{version}.zip`
5. Click **Install Now** and activate

### Manual Installation

1. Upload the `84em-consent` folder to `/wp-content/plugins/`
2. Activate through the WordPress admin

## Configuration

Customize the banner via the `84em_consent_simple_config` filter:

```php
add_filter('84em_consent_simple_config', function($config) {
    $config['brand_name'] = '84EM';
    $config['accent_color'] = '#CC3000';
    $config['banner_text'] = 'We use only essential cookies for security and performance.';
    $config['policy_url'] = '/privacy-policy/';
    $config['cookie_version'] = '2025-09-13';
    $config['cookie_duration'] = 180; // days
    $config['show_for_logged_in'] = false;

    return $config;
});
```

## Development

### Build Process

```bash
# Install dependencies (first time only)
npm install

# Build minified assets
npm run build

# Create installable WordPress plugin ZIP
./build.sh

# Development mode (auto-rebuild on changes)
npm run dev
```

### Project Structure

```
84em-consent/
├── 84em-consent.php      # Main plugin file
├── assets/
│   ├── consent.css       # Source CSS
│   ├── consent.js        # Source JavaScript
│   ├── consent.min.css   # Minified CSS (generated)
│   └── consent.min.js    # Minified JavaScript (generated)
├── build.sh              # Build script for creating ZIP
├── package.json          # Node dependencies
└── README.md            # This file
```

## API

### PHP

```php
// Check if user has given consent
if (function_exists('e84_has_consent') && e84_has_consent()) {
    // User has accepted cookies
}
```

### JavaScript

```javascript
// Check consent status
if (window.e84ConsentAPI && window.e84ConsentAPI.hasConsent()) {
    // User has accepted
}

// Listen for consent
document.addEventListener('84em:consent:accepted', function(e) {
    console.log('Consent given:', e.detail);
});

// Reset consent (for testing)
window.e84ConsentAPI.resetConsent();
```

## Cookie Details

- **Name**: `84em_consent`
- **Purpose**: Store consent acceptance
- **Duration**: 180 days (configurable)
- **Data**: JSON with accepted status, version, timestamp

## Browser Support

- Modern browsers (Chrome 90+, Firefox 88+, Safari 14+)
- Mobile browsers (iOS Safari, Chrome Mobile)
- Graceful degradation for older browsers

## Author

Andrew Miller @ 84EM - [https://84em.com/](https://84em.com/)

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
