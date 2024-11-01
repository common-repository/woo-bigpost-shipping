<?php
/**
 * Plugin Name: Big Post Shipping for WooCommerce
 * Description: Big Post Shipping for WooCommerce – offer shipping rates in your shopping cart from www.bigpost.com.au
 * Version: 2.0.10
 * Author: FusedSoftware
 * Author URI: https://fusedsoftware.com/
 * WC requires at least: 3.4
 * WC tested up to: 6.1
 * Text Domain: woobigpost
 * Domain Path: languages
 * @package WooCommerce - Big Post Shipping
 * @category Core
 * @author FusedSoftware
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Basic plugin definitions
 *
 * @package WooCommerce - Big Post Shipping
 * @since 1.0.0
 */
if( !defined( 'WOO_BIGPOST_VERSION' ) ) {
    define( 'WOO_BIGPOST_VERSION', '2.0.10' ); // plugin version
}
if( !defined( 'WOO_BIGPOST_DIR' ) ) {
    define( 'WOO_BIGPOST_DIR', dirname( __FILE__ ) ); // plugin dir
}
if( !defined( 'WOO_BIGPOST_URL' ) ) {
    define( 'WOO_BIGPOST_URL', plugin_dir_url( __FILE__ ) ); // plugin url
}
if( !defined( 'WOO_BIGPOST_ADMIN' ) ) {
    define( 'WOO_BIGPOST_ADMIN', WOO_BIGPOST_DIR . '/includes/admin' ); // plugin admin dir
}
if( !defined( 'WOO_BIGPOST_WOOCOMMERCE_MIN_VERSION' ) ) {
    define( 'WOO_BIGPOST_WOOCOMMERCE_MIN_VERSION', '3.4' ); // plugin version
}


/**
 * Load Text Domain
 *
 * This gets the plugin ready for translation.
 *
 * @package WooCommerce - Big Post Shipping
 * @since 1.0.0
 */
function woobigpost_plugins_textdomain() {

    $locale = apply_filters( 'plugin_locale', get_locale(), 'woobigpost' );

    load_textdomain( 'woobigpost', WP_LANG_DIR . '/woo-bigpost-shipping/woobigpost-' . $locale . '.mo' );
    load_plugin_textdomain( 'woobigpost', false, WOO_BIGPOST_DIR . '/languages' );
}
add_action( 'plugins_loaded', 'woobigpost_plugins_textdomain' );

add_action('before_woocommerce_init', function(){
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
});

/**
 * Activation Hook
 *
 * Register plugin activation hook.
 *
 * @package WooCommerce - Big Post Shipping
 * @since 1.0.0
 */
register_activation_hook( __FILE__, 'woobigpost_plugin_install' );

/**
 * Plugin Setup (On Activation)
 *
 * Does the initial setup,
 * sets default values for the plugin options.
 *
 * @package WooCommerce - Big Post Shipping
 * @since 1.0.0
 */
function woobigpost_plugin_install() {
    global $wpdb;
}

/**
 * Deactivation Hook
 *
 * Register plugin deactivation hook.
 *
 * @package WooCommerce - Big Post Shipping
 * @since 1.0.0
 */
register_deactivation_hook( __FILE__, 'woobigpost_plugin_uninstall' );

/**
 * Plugin Setup (On Deactivation)
 *
 * Delete  plugin options.
 *
 * @package WooCommerce - Big Post Shipping
 * @since 1.0.0
 */
function woobigpost_plugin_uninstall() {
    global $wpdb;
}

// Action add to call code afte all plugins loaded
add_action( 'plugins_loaded', 'woobigpost_plugins_loaded', 999 );

/**
 * Load plugin after load WooCommerce
 *
 * @package WooCommerce - Big Post Shipping
 * @since 1.0.0
 */
function woobigpost_plugins_loaded() {

    // Check if WooCommerce is active
    if ( class_exists( 'Woocommerce' ) ) {

        // Include misc functions file
        require_once( WOO_BIGPOST_DIR . '/includes/woobigpost-misc-functions.php' );

        $settings = woobigpost_get_plugin_settings();

        if($settings['use_cis'] == "yes"){
            require_once( WOO_BIGPOST_DIR . '/includes/class-woobigpost-plugin-setup-check.php' );
            require_once( WOO_BIGPOST_DIR . '/includes/cis/woobigpost-cis-functions.php' );
            require_once( WOO_BIGPOST_DIR . '/includes/class-woobigpost-scripts.php' );
        }else{
            require_once( WOO_BIGPOST_DIR . '/includes/class-guzzle-multi-transfer.php' );

            require_once( WOO_BIGPOST_DIR . '/includes/class-woobigpost-plugin-setup-check.php' );

            require_once( WOO_BIGPOST_DIR . '/includes/class-woobigpost-shipping-quote.php' );

            require_once( WOO_BIGPOST_DIR . '/includes/class-woobigpost-free-shipping-check.php' );

            require_once( WOO_BIGPOST_DIR . '/includes/class-woobigpost-product-settings.php' );


            // Scripts class manage plugin scripts functionalities
            require_once( WOO_BIGPOST_DIR . '/includes/class-woobigpost-scripts.php' );

            // Shortcode class manage plugin all shortcodes functionalities
            require_once( WOO_BIGPOST_DIR . '/includes/class-woobigpost-shortcodes.php' );

            // Check if admin
            if( is_admin() ) {
                require_once( WOO_BIGPOST_ADMIN . '/class-woobigpost-admin.php' );
            }

            // Include frontend side functionalities
            if( ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) ) {

                // Public class for manage public side functionality
                require_once( WOO_BIGPOST_DIR . '/includes/class-woobigpost-public.php' );
            }
        }

        if(version_compare( woobigpost_woocommerce_version_number(), WOO_BIGPOST_WOOCOMMERCE_MIN_VERSION, "<" )){
            function woobigpost_admin_warning() {
                echo '<div class="error">';
                echo "<p><strong>" . __( 'The WooCommerce - Big Post Shipping Extension needs the WooCommerce version of at least 3.4 in order to work properly!', 'woobigpost' ) . "</strong></p>";
                echo '</div>';
            }
            add_action( 'admin_notices', 'woobigpost_admin_warning' );
        }

    } else {

        function woobigpost_admin_warning() {
            echo '<div class="error">';
            echo "<p><strong>" . __( 'The WooCommerce - Big Post Shipping Extension needs the WooCommerce plugin installed and activated!', 'woobigpost' ) . "</strong></p>";
            echo '</div>';
        }
        add_action( 'admin_notices', 'woobigpost_admin_warning' );
    }
}