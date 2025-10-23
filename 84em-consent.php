<?php
/**
 * Plugin Name:         84EM Consent
 * Plugin URI:          https://84em.com/
 * Description:         A WordPress plugin that provides a simple cookie consent banner
 * Version:             1.2.0
 * Author:              84EM
 * Requires at least:   6.8
 * Requires PHP:        8.0
 * Author URI:          https://84em.com/
 * License:             MIT
 * License URI:         https://opensource.org/licenses/MIT
 */

namespace EightyFourEM\Consent;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SimpleConsent {

    private static ?SimpleConsent $instance = null;
    private array $config;

    public static function init() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initializes the class by setting up configuration, registering necessary hooks for displaying a banner,
     * and defining AJAX handlers for user interactions.
     *
     * @return void
     */
    private function __construct() {
        $this->config = $this->get_config();

        if ( ! is_admin() && $this->should_show_banner() ) {
            add_action(
                hook_name: 'wp_enqueue_scripts',
                callback: [ $this, 'enqueue_assets' ]
            );

            add_action(
                hook_name: 'wp_footer',
                callback: [ $this, 'render_banner' ],
                priority: 100 )
            ;
        }

        add_action(
            hook_name: 'wp_ajax_84em_dismiss_consent',
            callback: [ $this, 'ajax_dismiss' ]
        );

        add_action(
            hook_name: 'wp_ajax_nopriv_84em_dismiss_consent',
            callback: [ $this, 'ajax_dismiss' ]
        );
    }

    /**
     * Retrieves the configuration for the consent banner, applying filters to modify the default values.
     *
     * @return array Returns an associative array of configuration settings including default values such as
     * Brand name, accent color, logo URL, policy URL, banner text, and cookie duration.
     */
    private function get_config(): array {
        $defaults = [
            'brand_name'         => get_bloginfo( 'name' ),
            'accent_color'       => '#CC3000',
            'logo_url'           => '',
            'policy_url'         => get_privacy_policy_url() ?: '/privacy-policy/',
            'show_for_logged_in' => false,
            'cookie_version'     => '2025-09-15',
            'banner_text'        => __( 'We use only essential cookies for security and performance.', '84em-consent' ),
            'cookie_duration'    => 180, // days
        ];

        return apply_filters( '84em_consent_simple_config', $defaults );
    }

    /**
     * Determines whether the banner should be displayed based on the current user
     * state and page context.
     *
     * @return bool Returns true if the banner should be displayed, false otherwise.
     */
    private function should_show_banner(): bool {
        if ( is_user_logged_in() && ! $this->config['show_for_logged_in'] ) {
            return false;
        }

        if ( is_privacy_policy() ) {
            return false;
        }

        return true;
    }

    /**
     * Enqueues necessary CSS and JavaScript assets for the plugin, applies custom styles,
     * and localizes the script with dynamic data for client-side interaction.
     *
     * @return void
     */
    public function enqueue_assets(): void {
        wp_enqueue_style(
            handle: '84em-consent',
            src: plugin_dir_url( __FILE__ ) . 'assets/consent.min.css',
            ver: $this->config['cookie_version']
        );

        $custom_css = sprintf(
            ':root { --e84-consent-accent: %s; }',
            esc_attr( $this->config['accent_color'] )
        );

        wp_add_inline_style(
            handle: '84em-consent',
            data: $custom_css
        );

        wp_enqueue_script(
            handle: '84em-consent',
            src: plugin_dir_url( __FILE__ ) . 'assets/consent.min.js',
            ver: $this->config['cookie_version'],
            args: [ 'in_footer' => true, 'strategy' => 'async' ],
        );

        wp_localize_script(
            handle: '84em-consent',
            object_name: 'e84Consent',
            l10n: [
                'version'     => $this->config['cookie_version'],
                'duration'    => $this->config['cookie_duration'],
                'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
                'nonce'       => wp_create_nonce( '84em-consent-nonce' ),
                'isSecure'    => is_ssl(),
                'cookiePath'  => COOKIEPATH,
                'cookieDomain' => COOKIE_DOMAIN,
            ]
        );
    }

    /**
     * Renders the HTML structure for a cookie consent banner, including text, buttons,
     * and optional logo and policy link, with accessibility features.
     *
     * @return void
     */
    public function render_banner(): void {
        ?>
        <div id="e84-consent-banner"
             class="e84-consent-banner"
             role="region"
             aria-label="<?php esc_attr_e( 'Cookie consent', '84em-consent' ); ?>"
             aria-live="polite"
             hidden>
            <div class="e84-consent-container">
                <div class="e84-consent-content">
                    <?php if ( ! empty( $this->config['logo_url'] ) ) : ?>
                        <img src="<?php echo esc_url( $this->config['logo_url'] ); ?>"
                             alt=""
                             class="e84-consent-logo"
                             width="24"
                             height="24"
                             loading="lazy"
                             decoding="async">
                    <?php endif; ?>

                    <p id="e84-consent-text" class="e84-consent-text">
                        <?php echo esc_html( $this->config['banner_text'] ); ?>
                    </p>
                </div>

                <div class="e84-consent-buttons">
                    <button type="button"
                            id="e84-consent-accept"
                            class="e84-consent-button e84-consent-button-primary"
                            aria-describedby="e84-consent-text">
                        <?php esc_html_e( text: 'OK', domain: '84em-consent' ); ?>
                    </button>

                    <?php if ( $this->config['policy_url'] ) : ?>
                        <button type="button"
                                id="e84-consent-learn-more"
                                class="e84-consent-button e84-consent-button-secondary"
                                data-url="<?php echo esc_url( $this->config['policy_url'] ); ?>">
                            <?php esc_html_e( text: 'Learn More', domain: '84em-consent' ); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Handles the dismissal of the cookie consent by verifying the nonce,
     * setting a server-side cookie as a backup, and sending a successful JSON response.
     *
     * @return void
     */
    public function ajax_dismiss(): void {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], '84em-consent-nonce' ) ) {
            wp_die( message: 'Invalid nonce' );
        }

        $duration = $this->config['cookie_duration'] * DAY_IN_SECONDS;
        $secure = is_ssl();

        setcookie(
            name: '84em_consent',
            value: wp_json_encode( [
                'accepted'  => true,
                'version'   => $this->config['cookie_version'],
                'timestamp' => time(),
            ] ),
            expires_or_options: time() + $duration,
            path: COOKIEPATH,
            domain: COOKIE_DOMAIN,
            secure: $secure,
            httponly: true
        );

        wp_send_json_success();
    }
}

add_action(
    hook_name: 'init',
    callback: [ '\\EightyFourEM\\Consent\\SimpleConsent', 'init' ]
);

if ( ! function_exists( 'e84_has_consent' ) ) {
    /**
     * Checks if the user has provided consent based on the presence and content of the consent cookie.
     *
     * @return bool Returns true if the consent cookie exists and the 'accepted' field is set to a truthy value, otherwise false.
     */
    function e84_has_consent(): bool {
        if ( ! isset( $_COOKIE['84em_consent'] ) ) {
            return false;
        }

        $data = json_decode( wp_unslash( $_COOKIE['84em_consent'] ), true );
        return ! empty( $data['accepted'] );
    }
}
