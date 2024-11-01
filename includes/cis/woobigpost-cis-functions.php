<?php
	// Exit if accessed directly
	if ( !defined( 'ABSPATH' ) ) exit;

    function woobigpost_add_scripts() {
        wp_register_style( 'cis-css', WOO_BIGPOST_URL . 'css/woobigpostcis-public.css', array(), WOO_BIGPOST_VERSION );
        wp_register_script( 'cis-js', 'https://cis.bigpost.com.au/js/widget.js', array(), '1.0', false );        
        wp_enqueue_script('cis-js');
        wp_enqueue_style( 'cis-css' );
    }
	
	add_action( 'wp_enqueue_scripts', 'woobigpost_add_scripts');

	function woobigpost_create_widget(){

        global $product;
        $settings = woobigpost_get_plugin_settings();

        if($settings['cis_sync_order_only'] != "yes"){
            echo "<div id='bigpost-widget'></div>";
            
            ob_start();
            require_once( WOO_BIGPOST_DIR . '/includes/cis/woobigpost-cis-widget.php' );
            $html = ob_get_clean();
            echo $html;
        }
        
	}

	add_action( 'woocommerce_before_add_to_cart_quantity', 'woobigpost_create_widget' );
?>