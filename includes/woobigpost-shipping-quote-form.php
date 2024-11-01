<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

global $post, $product, $woocommerce;

$settings = woobigpost_get_plugin_settings();
//$packages = get_active_packages();

// Check if shipping is not enabled
if( empty($settings['enabled']) || $settings['enabled'] != 'yes' ) return;

if($settings['order_sync_only'] == "Yes") return;

$uniqueID = 'woobigpost-shipping-quote-form-' . uniqid();
$class = 'woobigpost-shipping-quote-form';
$title = 'Quick Quote';
$idWrapper = 'bigpost_checkout_form';

if (is_product()) {
    $idWrapper = 'bigpost_single_product_form';
} 
if (is_cart()) {
    $idWrapper = 'bigpost_cart_form';
}

// Global Settings
$options = array();
$options['shipping_types']		= isset( $settings['shipping_types'] ) ? $settings['shipping_types'] : array();
$options['forklift_option']		= isset( $settings['forklift_option'] ) ? $settings['forklift_option'] : array();
$options['authority_option']	= isset( $settings['authority_option'] ) ? $settings['authority_option'] : array();

$weight = 0;
$productid = '';
$over40 = false;
if( is_product() ) {

    $productid	= $post->ID;
    $options = woobigpost_merge_product_settings( $productid, $options );

    if(! isset($options['shipping_types'])){
        $options['shipping_types'] = $settings['shipping_types'];
    }
    
    $class .= ' sqf-single-product-page';

    // Find weight
    //$weight = $product->get_weight(); old code
    $rows = get_post_meta( $productid, '_carton_items', true );
    if( !empty($rows) ) {
        foreach (json_decode($rows) as $row) {
            //$weight += ( intval($row->weight) ); old code
            if(( intval($row->weight) ) > 30){
                $over40 = true;
                break;
            }

        }
    }
} elseif( is_cart() ) {
    $class .= ' sqf-cart-page';
} elseif( is_checkout() ) {
    $class .= ' sqf-checkout-page';
}

// Check if not product
if( ! is_product() ) {

    $items = $woocommerce->cart->get_cart();

    $shipping_types = $options['shipping_types'];
    $authopt = array();
    foreach( $items as $item ) {
        $product_id = $item['product_id'];

        $show_plugin_value  = get_post_meta($product_id, '_show_plugin', true);

        if($show_plugin_value == "0"){
            continue; //exclude products where bigpost quote box is hidden
        }

        if(isset($item['variation_id']) && $item['variation_id'] > 0){
            $rows = get_post_meta( $item['variation_id'], '_carton_items', true );
        }else{
            $rows = get_post_meta( $product_id, '_carton_items', true );
        }

        if( !empty($rows) ) {
            foreach( json_decode($rows) as $row ) {
                //$weight += ( intval($row->weight) );
                if(( intval($row->weight) ) > 30){
                    $over40 = true;
                    break;
                }
            }
        }

        $use_admin_setting	= get_post_meta( $product_id, '_use_admin_setting', true );
        if( $use_admin_setting != 'Yes' && $use_admin_setting != '') {

            $product_stypes = get_post_meta( $product_id, '_shipping_type', true );
            $product_stypes = json_decode( $product_stypes );

            foreach( $shipping_types as $key => $stype ) {
                if( ! in_array($stype, $product_stypes) ) {
                    unset( $shipping_types[$key] );
                }
            }
        }

        $Authority_option = get_post_meta( $product_id, '_authority_option', true );
        if( $Authority_option != 'global' ) {
            $authopt[] = $Authority_option;
        } else {
            $authopt[] = $options['authority_option'];
        }
    }

    $options['shipping_types'] = $shipping_types;
    //$options['authority_option'] = 'Yes';
    if( in_array('No', $authopt) ) {
        $options['authority_option'] = 'No';
    }
}

$buyer_is_business = WC()->session->get( 'woobigpost-buyer_is_business' );
$leave_auth = WC()->session->get( 'woobigpost-leave_authority' );
$has_forklift = WC()->session->get( 'woobigpost-has_forklift' );
$have_confirm = WC()->session->get( 'woobigpost-have_confirm' );

$tosuburb = WC()->session->get( 'woobigpost-tosuburb' );
$tostate = WC()->session->get( 'woobigpost-tostate' );
$topostcode = WC()->session->get( 'woobigpost-topostcode' );
$topostcodeid = WC()->session->get( 'woobigpost-topostcodeid' );
$todepotid = WC()->session->get( 'woobigpost-todepotid' );


// This is your shipping 

if (is_product()) {
    $shippingPrices_Display = '';
    if($settings['product_page'] == "popup"){
        require_once( WOO_BIGPOST_DIR . '/includes/template-qoute-form/popup.php' );
    }else{
        require_once( WOO_BIGPOST_DIR . '/includes/template-qoute-form/default.php' );
    }
}else {
  $show_all_options = false; //(isset($settings['show_all_options']) && $settings['show_all_options'] == "Yes")?true:false;
  $post_data = WC()->session->get('woobigpost-post_data');
  $shippingPrices_Display = '';

  if (!empty($post_data)) {
    $shippingPrices_Display = WC()->session->get( 'WooBigPost_shippingPrices_Display' );
  }
  
  if($show_all_options){
    //echo "<tr><td><input type='hidden' id='show_all_options' value='1' /></td></tr>";

  }else{
    require_once( WOO_BIGPOST_DIR . '/includes/template-qoute-form/cart.php' );  
  }
  
}

?>

<!-- <div id="woobigpost-suburb-modal" class="modal">


    <div class="modal-content">
        <div class="modal-header">
            <h2 class="header-title">List of Suburbs</h2>
            <button class="close"><span >&times;</span></button>
        </div>
        <div class="modal-body">
            <div id="woobigpost-ajax-loader" style="background-image: url( <?php echo admin_url(); ?>images/spinner.gif );"></div>
            <ul id="sqf-suburb-list" style="display: none;"></ul>
        </div>

    </div>

</div> -->