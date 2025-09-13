/**
 * 84EM Simple Consent Banner
 * Handles strictly necessary cookies only
 */
(function() {
    'use strict';

    const COOKIE_NAME = '84em_consent';
    const STORAGE_KEY = '84em_consent';

    // Wait for DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        const banner = document.getElementById('e84-consent-banner');
        const acceptBtn = document.getElementById('e84-consent-accept');
        const learnMoreBtn = document.getElementById('e84-consent-learn-more');

        if (!banner || !acceptBtn) return;

        // Check if consent already given
        const consent = getConsent();
        const needsConsent = !consent || consent.version !== e84Consent.version;

        if (needsConsent) {
            showBanner(banner);
        }

        // Handle accept button
        acceptBtn.addEventListener('click', function() {
            acceptConsent(banner);
        });

        // Handle learn more button
        if (learnMoreBtn) {
            learnMoreBtn.addEventListener('click', function() {
                const url = this.getAttribute('data-url');
                if (url) {
                    window.open(url, '_blank', 'noopener,noreferrer');
                }
            });
        }

        // Handle Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !banner.hidden) {
                acceptConsent(banner);
            }
        });
    }

    function showBanner(banner) {
        banner.hidden = false;
        banner.setAttribute('aria-hidden', 'false');

        // Focus management for accessibility
        const acceptBtn = banner.querySelector('#e84-consent-accept');
        if (acceptBtn) {
            acceptBtn.focus();
        }
    }

    function hideBanner(banner) {
        banner.hidden = true;
        banner.setAttribute('aria-hidden', 'true');
    }

    function acceptConsent(banner) {
        const consent = {
            accepted: true,
            version: e84Consent.version,
            timestamp: Date.now()
        };

        // Save to localStorage
        saveToStorage(consent);

        // Save to cookie
        saveToCookie(consent);

        // Hide banner with smooth animation if supported
        if ('animate' in banner) {
            banner.animate([
                { opacity: 1, transform: 'translateY(0)' },
                { opacity: 0, transform: 'translateY(100%)' }
            ], {
                duration: 300,
                easing: 'ease-out'
            }).onfinish = () => hideBanner(banner);
        } else {
            hideBanner(banner);
        }

        // Trigger custom event
        document.dispatchEvent(new CustomEvent('84em:consent:accepted', {
            detail: consent
        }));

        // Optional: Send to server via AJAX for server-side cookie setting
        if (e84Consent.ajaxUrl && e84Consent.nonce) {
            sendConsentToServer();
        }
    }

    function getConsent() {
        // Try localStorage first
        const stored = getFromStorage();
        if (stored) return stored;

        // Fall back to cookie
        return getFromCookie();
    }

    function saveToStorage(consent) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(consent));
        } catch (e) {
            console.warn('Could not save consent to localStorage:', e);
        }
    }

    function getFromStorage() {
        try {
            const data = localStorage.getItem(STORAGE_KEY);
            return data ? JSON.parse(data) : null;
        } catch (e) {
            return null;
        }
    }

    function saveToCookie(consent) {
        const days = parseInt(e84Consent.duration) || 180;
        const maxAge = days * 24 * 60 * 60;
        const value = encodeURIComponent(JSON.stringify(consent));

        let cookie = `${COOKIE_NAME}=${value}; Max-Age=${maxAge}; Path=${e84Consent.cookiePath || '/'}; SameSite=Lax`;

        if (e84Consent.isSecure) {
            cookie += '; Secure';
        }

        if (e84Consent.cookieDomain) {
            cookie += `; Domain=${e84Consent.cookieDomain}`;
        }

        document.cookie = cookie;
    }

    function getFromCookie() {
        const match = document.cookie.match(new RegExp('(?:^|;\\s*)' + COOKIE_NAME + '=([^;]+)'));
        if (!match) return null;

        try {
            return JSON.parse(decodeURIComponent(match[1]));
        } catch (e) {
            return null;
        }
    }

    function sendConsentToServer() {
        const formData = new FormData();
        formData.append('action', '84em_dismiss_consent');
        formData.append('nonce', e84Consent.nonce);

        fetch(e84Consent.ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        }).catch(function(error) {
            console.warn('Could not send consent to server:', error);
        });
    }

    // Public API
    window.e84ConsentAPI = {
        hasConsent: function() {
            const consent = getConsent();
            return consent && consent.accepted;
        },
        resetConsent: function() {
            localStorage.removeItem(STORAGE_KEY);
            document.cookie = `${COOKIE_NAME}=; Max-Age=0; Path=${e84Consent.cookiePath || '/'}`;
            location.reload();
        }
    };

})();