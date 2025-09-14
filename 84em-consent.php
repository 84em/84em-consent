<?php
/**
 * Plugin Name:     84EM Consent
 * Plugin URI:      https://84em.com/
 * Description:     A WordPress plugin that provides a simple cookie consent banner
 * Version:         1.1.0
 * Author:          84EM
 * Author URI:      https://84em.com/
 */

namespace EightyFourEM\Consent;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SimpleConsent {

    private static $instance = null;
    private $config = [];

    public static function init() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->config = $this->get_config();

        if ( ! is_admin() && $this->should_show_banner() ) {
            add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
            add_action( 'wp_footer', [ $this, 'render_banner' ], 100 );
        }

        // AJAX handler for dismissal
        add_action( 'wp_ajax_84em_dismiss_consent', [ $this, 'ajax_dismiss' ] );
        add_action( 'wp_ajax_nopriv_84em_dismiss_consent', [ $this, 'ajax_dismiss' ] );
    }

    private function get_config() {
        $defaults = [
            'brand_name'         => get_bloginfo( 'name' ),
            'accent_color'       => '#CC3000',
            'logo_url'           => '',
            'policy_url'         => get_privacy_policy_url() ?: '/privacy-policy/',
            'show_for_logged_in' => false,
            'cookie_version'     => '2025-09-13',
            'banner_text'        => __( 'We use only essential cookies for security and performance.', '84em-consent' ),
            'cookie_duration'    => 180, // days
        ];

        return apply_filters( '84em_consent_simple_config', $defaults );
    }

    private function should_show_banner() {
        // Don't show for logged-in users if configured
        if ( is_user_logged_in() && ! $this->config['show_for_logged_in'] ) {
            return false;
        }

        // Don't show on privacy policy page
        if ( is_privacy_policy() ) {
            return false;
        }

        return true;
    }

    public function enqueue_assets() {
        // Enqueue minimized CSS
        wp_enqueue_style(
            '84em-consent',
            plugin_dir_url( __FILE__ ) . 'assets/consent.min.css',
            [],
            $this->config['cookie_version']
        );

        // Add custom CSS properties
        $custom_css = sprintf(
            ':root { --e84-consent-accent: %s; }',
            esc_attr( $this->config['accent_color'] )
        );
        wp_add_inline_style( '84em-consent', $custom_css );

        // Enqueue minimized JS with proper dependencies
        wp_enqueue_script(
            '84em-consent',
            plugin_dir_url( __FILE__ ) . 'assets/consent.min.js',
            [],
            $this->config['cookie_version'],
            true
        );

        // Localize script with configuration
        wp_localize_script( '84em-consent', 'e84Consent', [
            'version'     => $this->config['cookie_version'],
            'duration'    => $this->config['cookie_duration'],
            'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
            'nonce'       => wp_create_nonce( '84em-consent-nonce' ),
            'isSecure'    => is_ssl(),
            'cookiePath'  => COOKIEPATH,
            'cookieDomain' => COOKIE_DOMAIN,
        ] );
    }

    public function render_banner() {
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
                        <?php esc_html_e( 'OK', '84em-consent' ); ?>
                    </button>

                    <?php if ( $this->config['policy_url'] ) : ?>
                        <button type="button"
                                id="e84-consent-learn-more"
                                class="e84-consent-button e84-consent-button-secondary"
                                data-url="<?php echo esc_url( $this->config['policy_url'] ); ?>">
                            <?php esc_html_e( 'Learn More', '84em-consent' ); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    public function ajax_dismiss() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], '84em-consent-nonce' ) ) {
            wp_die( 'Invalid nonce' );
        }

        // Set cookie server-side as backup
        $duration = $this->config['cookie_duration'] * DAY_IN_SECONDS;
        $secure = is_ssl();

        setcookie(
            '84em_consent',
            wp_json_encode( [
                'accepted' => true,
                'version'  => $this->config['cookie_version'],
                'timestamp' => time(),
            ] ),
            time() + $duration,
            COOKIEPATH,
            COOKIE_DOMAIN,
            $secure,
            true // httpOnly
        );

        wp_send_json_success();
    }
}

// Initialize the plugin
add_action( 'init', [ __NAMESPACE__ . '\SimpleConsent', 'init' ] );

// Helper function for checking consent
if ( ! function_exists( 'e84_has_consent' ) ) {
    function e84_has_consent() {
        if ( ! isset( $_COOKIE['84em_consent'] ) ) {
            return false;
        }

        $data = json_decode( wp_unslash( $_COOKIE['84em_consent'] ), true );
        return ! empty( $data['accepted'] );
    }
}
