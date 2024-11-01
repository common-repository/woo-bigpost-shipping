<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Script Class
 *
 * Manage script and style enqueue
 * functionalities
 */
class Woo_BigPost_Script {
     private $plugin_setup_check;
	public function __construct() {
        $this->plugin_setup_check = woobigpost_get_plugin_settings();
		// Add Admin side scripts
		add_action( 'admin_enqueue_scripts', array($this, 'woobigpost_adminside_scripts') );

        if(isset($this->plugin_setup_check['plugin_setup']['api_creds']) && $this->plugin_setup_check['plugin_setup']['api_creds'] == 'true' && isset($this->plugin_setup_check['plugin_setup']['pickup_locations']) && $this->plugin_setup_check['plugin_setup']['pickup_locations'] == 'true') {
		// Add public side scripts

        	$settings = woobigpost_get_plugin_settings();

        	if($settings['use_cis'] != "yes"){
			    add_action( 'wp_enqueue_scripts', array($this, 'woobigpost_frontside_scripts'), 20 );
			}
        }
	}

	/**
	 * Manage styles and scripts,
	 * at admin side
	 */
	public function woobigpost_adminside_scripts( $hook_sufix = '' ) {

		$allow_screens = array( 'woocommerce_page_wc-settings', 'product', 'woocommerce_page_woobigpost-product-box-migration' );
		$screen = get_current_screen()->id;

		// Current sections
		$settings_sec = isset( $_GET['section'] ) ? $_GET['section'] : '';

		// Register styles
		wp_register_style( 'woobigpost-admin-css', WOO_BIGPOST_URL . 'css/woobigpost-admin.css', array(), WOO_BIGPOST_VERSION );

		// Register scripts
		wp_register_script( 'woobigpost-admin-js', WOO_BIGPOST_URL . 'js/woobigpost-admin.js', array( 'jquery' ), WOO_BIGPOST_VERSION, true );

		if( in_array( $screen, $allow_screens) && 
		  ($settings_sec == 'woobigpost' || $hook_sufix == 'post.php' || $hook_sufix == 'woocommerce_page_woobigpost-product-box-migration') ) {

			// Enqueue styles
			wp_enqueue_style( 'woobigpost-admin-css' );

			// Enqueue scripts
			wp_enqueue_script( 'woobigpost-admin-js' );
		}
	}

	/**
	 * Manage styles and scripts,
	 * at frontend side
	 */
	public function woobigpost_frontside_scripts() {
        global $woocommerce;
        $items = $woocommerce->cart->get_cart();
        $disabled_items = count_disabled_items($items,$this->plugin_setup_check);

        if(( is_cart() || is_checkout()) && ($disabled_items == count($items) && $this->plugin_setup_check['order_sync_only'] != "Yes")){
           return;
        }
        // Register styles
		wp_register_style( 'woobigpost-public-css', WOO_BIGPOST_URL . 'css/woobigpost-public.css', array(), WOO_BIGPOST_VERSION );
		
		// Register scripts
		wp_register_script( 'woobigpost-public-js', WOO_BIGPOST_URL . 'js/woobigpost-public.js', array( 'jquery' ), WOO_BIGPOST_VERSION, true );

		$page = '';
		if( is_product() ) $page = 'product';
		elseif( is_cart() ) $page = 'cart';
		elseif( is_checkout() ) $page = 'checkout';

		// localize script
		wp_localize_script( 'woobigpost-public-js', 'WooBP', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'page_is' => $page
		) );

		if( is_product() || is_cart() || is_checkout() ) {

			// Enqueue styles
			wp_enqueue_style( 'woobigpost-public-css' );

			// Enqueue scripts
			wp_enqueue_script( 'woobigpost-public-js' );
		}
	}
}

return new Woo_BigPost_Script();