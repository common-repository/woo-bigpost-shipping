<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Admin Class
 *
 * Handles generic Admin functionality and AJAX requests.
 *
 * @package WooCommerce - Big Post Shipping
 * @since 1.0.0
 */
class Woo_BigPost_Shortcodes {

	public function __construct() {
		add_shortcode( 'wooboigpost_shipping_status', array($this, 'woobigpost_shipping_status_shortcode') );
        add_shortcode( 'woobigpost_shipping_status', array($this, 'woobigpost_shipping_status_shortcode') );
	}

	/**
	 * Shipping Status Shortcode
	 * Use: [wooboigpost_shipping_status][/wooboigpost_shipping_status]
	 */
	public function woobigpost_shipping_status_shortcode( $atts, $content ) {

		extract( shortcode_atts( array(
			'title'	=> esc_attr( "Shipping Status", 'woobigpost' ),
		), $atts ) );

		wp_enqueue_style( 'woobigpost-public-css' );
		wp_enqueue_script( 'woobigpost-public-js' );

		ob_start(); ?>

		<div class="custom-wrap" id="get-quote-product-page">
			<h2><?php echo $title; ?></h2>
			<hr>
			<div class="quote-row">
				<div class="quote-row-half">
					<strong><?php _e("Enter Consignment Number", 'woobigpost'); ?></strong></br>
					<input type="text" name="consignment_number" id="consignment_number" value="" class="regular-text" required>
					<p>
						<input type="submit" name="get_shipping_status_button" id="get_shipping_status_button" class="button button-primary" value="<?php _e("Generate Shipping Status", 'woobigpost'); ?>">
					</p>
					<img class="loader-shipping-status" id="loader-shipping-status"  src="<?php echo admin_url(); ?>images/spinner.gif" alt="<?php _e("loading...", 'woobigpost'); ?>" title="<?php _e("loading...", 'woobigpost'); ?>" style="display: none;">
				</div>
				<div class="quote-row-half quote-row-half-second">
					<strong>Currrent Status : </strong>
					<div id="shipping_status_div"></div>
				</div>
			</div>
		</div>

		<?php
		return ob_get_clean();
	}

}

return new Woo_BigPost_Shortcodes();