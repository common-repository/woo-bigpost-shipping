<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Manage plugin Misc functions
 *
 * @package WooCommerce - Big Post Shipping
 * @since 1.0.0
 */

/**
 * Return plugin settings
 */
function woobigpost_get_plugin_settings() {
    return get_option( 'woocommerce_woobigpost_settings' );
}

/**
 * Call Shipping Method class file
 */
function woobigpost_shipping_method_class() {
    if( ! class_exists('Woo_BigPost_Shipping_Method') ) {
        require_once( WOO_BIGPOST_DIR . '/includes/class-woobigpost-shipping-method.php' );
    }
}
add_action( 'woocommerce_shipping_init', 'woobigpost_shipping_method_class' );

function woobigpost_add_shipping_method($methods) {
    $methods['woobigpost'] = 'Woo_BigPost_Shipping_Method';
    return $methods;
}
add_filter( 'woocommerce_shipping_methods', 'woobigpost_add_shipping_method' );

# add this in your plugin file and that's it, the calculate_shipping method of your shipping plugin class will be called again
function woobigpost_checkout_update_order_review( $post_data ) {
    WC()->cart->calculate_shipping();
    return;
}
add_action( 'woocommerce_checkout_update_order_review', 'woobigpost_checkout_update_order_review' );

function woobigpost_remote_api_request($endpoint = '', $data_to_send = array(), $request_method = 'GET'){
    $settings = woobigpost_get_plugin_settings();

    $url = $settings['api_url'];

    if($url == "https://staging.bigpost.com.au/"){
        $url = "https://stagingapiv2.bigpost.com.au";
    }

    if( !empty($endpoint) ) {
        $url .= $endpoint;
    }

    $api_key = $settings['api_key'];

    if(strpos($url, 'staging') !== false){
        $api_key = $settings['testing_api_key'];
    }

    $args = array(
        'method' => $request_method,
        'headers'=>array(
            'Content-Type'=>'application/json; charset=utf-8',
            'Accesstoken' => $api_key

        ),
        'cookies' => array(),
        'data_format' => 'body',
        'timeout' => 60
    );

    if( $request_method == 'POST' ) {
        if( is_array($data_to_send) && !empty($data_to_send) ) {
            $args['body'] = json_encode($data_to_send, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            $args['headers']['Content-Length'] = strlen($args['body']);
        }
    }



    $response = wp_remote_request( $url, $args );
    return $response;

}

/**
 * Main send Big Post API requist
 */
function woobigpost_send_api_request( $endpoint = '', $data_to_send = array(), $request_method = 'GET' ) {

    $settings = woobigpost_get_plugin_settings();

    $url = $settings['api_url'];
    if( !empty($endpoint) ) {
        $url .= $endpoint;
    }

    $api_key = $settings['api_key'];

    if(strpos($url, 'staging') !== false){
        $api_key = $settings['testing_api_key'];
    }

    $headers = array(
        'Content-Type'=>'application/json',
        'Accesstoken'=>$api_key
    );

    $args = array(
        'headers' => $headers
    );

    if($request_method == 'POST') {
        $args['body'] = json_encode($data_to_send);
        $server_output = wp_remote_post( $url, $args );
    }else{
        $server_output = wp_remote_get( $url, $args );
    }

    return wp_remote_retrieve_body($server_output);
}

/**
 * Get Suburb suggestion list
 */
function woobigpost_get_suburb_list( $suburb, $type = 'to' ) {

    $params = urlencode( $suburb );

    $endpoint = "/api/suburbs?q=".htmlentities( $params );
    $result     = woobigpost_remote_api_request( $endpoint, '', 'GET' );
    $body = wp_remote_retrieve_body($result);
    $suburbs    = array();
    $suburbList = '<div id="woobigpost-suburb-loader" style="display: none; width: 100%;height: 100%;position: absolute;background: url(/wp-content/plugins/woo-bigpost-shipping/images/spinner.gif) no-repeat center rgba(255,255,255,.6);text-align: center;"></div>';
    $index = isset($_REQUEST['index']) ? '-' . esc_attr($_REQUEST['index']) : '';


    if($body != false || !empty($body)){
        $suburbs = json_decode($body);
    }

    if( !empty($suburbs) ) {

        $inc = 1;
        $li_class   = 'from-suburb-item';

        if( $type == 'to' ) {
            $li_class = 'to-suburb-item';
        }

        foreach( $suburbs as $suburb ) {
            $suburbList .= '<div class="radio-item">
                <label for="woo-bp-suburb-'.$inc.$index.'">
                    <input type="radio" id="woo-bp-suburb-'.$inc.$index.'" name="woo_bp_suburb" class="woo-bp-suburb-item" value="'. $suburb->PostcodeId .'" data-suburb="'.$suburb->Suburb.'" data-state="'.$suburb->State.'" data-postcode="'.$suburb->Postcode.'">
                    <strong>'.$suburb->Suburb.' '.$suburb->State.'</strong> - '.$suburb->Postcode.'
                </label>
            </div>';
            // $suburbList .= '<li data-suburb="'.$suburb->Suburb.'" data-state="'.$suburb->State.'" data-postcode="'.$suburb->Postcode.'" data-postcodeid="'.$suburb->PostcodeId.'" class="'.$li_class.'"><strong>'.$suburb->Suburb.' '.$suburb->State.'</strong> - '.$suburb->Postcode.'</li>';

            //if( $inc == 10 ) { break; }
            $inc++;
        }
    } else {
        $suburbList = 'No suburb found, try again!';
    }

    return $suburbList;
}

/**
 * Find Depot ID
 */
function woobigpost_get_depot( $suburl = '', $postcode = '' ) {

    if( empty($suburb) ) {
        $suburb     = WC()->session->get( 'woobigpost-tosuburb' );
        $postcode   = WC()->session->get( 'woobigpost-topostcode' );
    }

    $params     = "?p=" . urlencode( $postcode ) . "&s=" . urlencode( $suburb );
    $endpoint   = "/api/depots".  $params;

    //$result = woobigpost_send_api_request( $endpoint, '', 'GET' );
    $result = woobigpost_remote_api_request( $endpoint, '', 'GET' );
    $body = wp_remote_retrieve_body($result);
    $depot = array();

    if($body != false || !empty($body)){
        $depot = json_decode($body);
    }

    $depotId = 0;
    if( count($depot) ) {
        $depotId = $depot[0]->DepotId;
    }

    return $depotId;
}

/*
function woobigpost_shipping_charges_for_single_product() {
    global $product;

    $carton_items = get_post_meta( $product->get_id(), '_carton_items', true );

    if( !empty($carton_items) ) { 
        echo woobigpost_shipping_quote_form_html();
    }
}
add_action( 'woocommerce_after_single_product_summary', 'woobigpost_shipping_charges_for_single_product' );*/

// Display Quick Quote On cart page
/*add_action( 'woocommerce_after_cart_table', 'woobigpost_shipping_charges_for_cart' );
function woobigpost_shipping_charges_for_cart() {
    echo woobigpost_shipping_quote_form_html();
}*/

// Display Quick Quote On checkout page
/*add_action( 'woocommerce_checkout_after_customer_details', 'woobigpost_shipping_charges_for_checkout' );
function woobigpost_shipping_charges_for_checkout() {
    echo woobigpost_shipping_quote_form_html();
}*/

/**
 * Quote Form HTML output
 */
function woobigpost_shipping_quote_form_html($page = '') {
    ob_start();
    require_once( WOO_BIGPOST_DIR . '/includes/woobigpost-shipping-quote-form.php' );
    return ob_get_clean();
}

function woobigpost_merge_product_settings( $product_id, $options = array() ) {

    $use_admin_setting  = get_post_meta( $product_id, '_use_admin_setting', true );
    if( $use_admin_setting != 'Yes' && $use_admin_setting != '' ) {
        $ShippingType = json_decode(get_post_meta( $product_id, '_shipping_type', true ));
        $options['shipping_types'] = $ShippingType;
    }

    $Authority_option = get_post_meta( $product_id, '_authority_option', true );
    if( $Authority_option != 'global' && $Authority_option != '' ) {
        $options['authority_option'] = $Authority_option;
    }

    return $options;
}



/**
 * Get Shipping prices options
 */
function woobigpost_get_shipping_prices( $result, $qType = 'product', $post_data) {
    if(empty($post_data)) return;

    $settings = woobigpost_get_plugin_settings();
    $buyer_is_business = (isset($post_data['buyer_is_business']))? $post_data['buyer_is_business']: WC()->session->get('woobigpost-buyer_is_business');
    $type = $buyer_is_business == 1  ? 'BUSINESS' : 'HDS'; //added 02/11/2018
    
    $has_forklift = (isset($post_data['has_forklift']))?sanitize_text_field($post_data['has_forklift']):WC()->session->get('woobigpost-has_forklift');

    if(isset($post_data['has_forklift']) && $has_forklift == 0 && $type == 'BUSINESS'){
        $type = 'HDS';
    }


    WC()->session->set_customer_session_cookie( true );

    $output = '';
    if( ! empty($result->Errors) ) {
        $output .= "<div class='shipping-type-result not-found'><label>No shipping options found, try again.</label></div>";
        return $output;
    }

    $shipping_types = $settings['shipping_types'];
    $product_stypes = array();
    if( $qType == 'product' ) {
        $p_id = $post_data['productId'];
        if($post_data['parent_product'] > 0){
            $p_id = $post_data['parent_product'];
        }

        $admin_setting_product = get_post_meta( $p_id, '_use_admin_setting', true );
        if( $admin_setting_product != 'Yes' && $admin_setting_product != '' ) {
            $product_stypes = get_post_meta( $p_id, '_shipping_type', true );
            $shipping_types = json_decode( $product_stypes );
        }
    } else {
        global $woocommerce;

        $items = $woocommerce->cart->get_cart();
        foreach( $items as $item ) {
            $product_id = ( isset($item['product_id']) ) ? $item['product_id'] : '';

            $use_admin_setting  = get_post_meta( $product_id, '_use_admin_setting', true );
            if( $use_admin_setting != 'Yes' && $use_admin_setting != '' ) {

                $product_stypes = get_post_meta( $product_id, '_shipping_type', true );
                $product_stypes = json_decode( $product_stypes );
                foreach( $shipping_types as $key => $stype ) {
                    if( ! in_array($stype, $product_stypes) ) {
                        unset( $shipping_types[$key] );
                    }
                }
            }
        }
    }

    $shippings = $shipping_types;

    $DeliveryOptions = $result->Object->DeliveryOptions;

    $inc = 1;
    $dflag = 0;
    $jobType = 0;

    foreach( array_reverse($DeliveryOptions) as $option ) {

        $havec = WC()->session->get('woobigpost-have_confirm');
        if($option->JobType === 'HDS' && $havec == 0 && $settings['authority_option'] != "Always") continue;

        /* determines if there is a previous HDS job type so as to pre append the "alternate method"*/
        if(($option->JobType === 'HDS' or $option->JobType === 'BUSINESS') and count($option->CarrierOptions) > 0) {
            $jobType++;
        }

        //print_r($option->JobType.'<br>'.'HDS COUNT: '.$jobType);

        /* check method enabled from backend  */
        if( !empty($shippings) && in_array($option->JobType, $shippings) ) {

            /*if(($type == $option->JobType) || $option->JobType == 'DEPOT'){
                $p_id = isset($post_data['productId']) ? $post_data['productId']: 0;
                $price_data = woobigpost_show_pricing_for_shipping( $option, $qType,  $p_id);
            }*/

            /* get shipping price for current delivery type */
            $p_id = isset($post_data['productId']) ? $post_data['productId']: 0;
            $price_data = woobigpost_show_pricing_for_shipping( $option, $qType,  $p_id);

            $selected_option = '';
            if( $inc == 1 ) {
                if( $qType == 'cart' ) {

                    WC()->session->set( 'Shipping_Method_Selected', array(
                        'CarrierName'   => $price_data['CarrierName'],
                        'CarrierId'     => $price_data['CarrierId'],
                        'ServiceCode'     => $price_data['ServiceCode'],
                        'Charge'        => $price_data['Charge'],
                        'Tax'           => $price_data['Tax'],
                        'Total'         => $price_data['Total'],
                        'ShippingType'  => $option->JobType,
                        'LeaveAuth'     => $price_data['RequiresAuthorityToLeave'],
                    ) );

                } else {
                    $selected_option = 'checked="checked"';
                }
            } else {
                $selected_option = '';
            }

            //Overwrites the HOME title for HDS if it is a business customer, but a HDS delivery

            $title = woobigpost_getLabel($option->JobType, WC()->session->get('woobigpost-buyer_is_business'), WC()->session->get('woobigpost-has_forklift'));
            $shippingDescription = $title['description'];
            if(isset($title['flag']) && $jobType > 0) $dflag = $title['flag'];
            $title = $title['label'].': ';

            if( is_array($price_data) && !empty($price_data) ) {
                if ($dflag == 1 && isset($option->JobType) && $option->JobType == 'DEPOT')  $output .= '<div class="alternate-label">Or alternatively...</div>';
                /* print calculated margin amount result here */
                $output .= "<div class='shipping-type-result woobigpost-sqf-fields'><h4>".$settings['shipping_'.strtolower($option->JobType) .'_label']."</h4>";

                $new_shipping_rate = $price_data['RawTotal'];
                if(isset($settings['tax_exclusive_widget']) && $settings['tax_exclusive_widget'] == 'yes'){
                    $new_shipping_rate = $price_data['Charge'];
                }else{
                    if($qType != 'product'){
                        if(isset($settings['tax_exclusive_cart']) && $settings['tax_exclusive_cart'] == 'yes'){
                            $tax = $price_data['Total'] * .1;
                            $new_shipping_rate = $price_data['Total'] + number_format($tax,2);
                        }                    
                    }    
                }

                if($qType == 'product' && $settings['free_shipping_config'] != 'disabled'){
                    $free_shipping_check = new Woo_BigPost_Free_Shipping_Check();
                    $new_shipping_rate = $free_shipping_check->check_product_if_free($post_data,$new_shipping_rate);
                }

                if($qType != 'product' &&  $settings['free_shipping_config'] != 'disabled'){
                    $new_shipping_rate = isset($post_data['new_rates'][$option->JobType]) && !isset($post_data['new_rates']['Results'])? $post_data['new_rates'][$option->JobType]: $new_shipping_rate;
                }

                $output .= woobigpost_print_calculated_advance_margin_product( $price_data['CarrierId'], $price_data['CarrierName'], $price_data['Charge'], $price_data['Tax'], $price_data['Total'], $selected_option, $title, $option->JobType, $price_data['RequiresAuthorityToLeave'], $new_shipping_rate);

                $shippingDesc = '';
                $shippingAddress = '';

                if( isset($option->JobType) && $option->JobType == 'DEPOT' ) {
                    $depot_address = woobigpost_export_depot_address( $option->DepotLocality->Postcode, $option->DepotLocality->Suburb, $option->DepotLocality->Id );

                    $shippingDesc .= $settings['shipping_'.strtolower($option->JobType) .'_description'];

                    $shippingAdrs = $depot_address;
                    $shippingAddress .= ( $shippingAdrs ) ? 'For Pick Up At: '.$option->DepotLocality->Suburb.' Depot' : '';
                } else {
                    if( isset($option->JobType) ){

                        $shippingDesc_ = $shippingDescription;

                        if(isset($post_data['leave_auth']) && $post_data['leave_auth'] == '1' && $option->JobType == 'HDS'){
                            $shippingDesc_ = isset($settings['shipping_hds_atl_description']) && !empty($settings['shipping_hds_atl_description']) ? $settings['shipping_hds_atl_description'] : "Your goods will be left in a safe location nearest to your front door.";
                        }

                        if((isset($post_data['buyer_is_business']) && $post_data['buyer_is_business'] == '1') && (isset($post_data['has_forklift']) && $post_data['has_forklift'] == '0')){
                            $shippingDesc_ = isset($settings['business_no_forklift']) && !empty($settings['business_no_forklift']) ? $settings['business_no_forklift'] : "Your goods will be delivered to your business with a tailgate service.";
                        }

                        $shippingDesc .= $shippingDesc_;
                        $shippingAddress .= "";
                    }
                }

                $eta_arr = explode("-", $price_data['Eta']);
                $eta = $eta_arr[2]."/".$eta_arr[1]."/".$eta_arr[0];
                
                if($settings['display_eta'] == "Yes"){

                    $eta_margin = $settings['eta_margin'];
                    if($eta_margin != ""){
                        $newdate = strtotime ( '+'.$eta_margin.' day' , strtotime ( $price_data['Eta'] ) ) ;
                        $eta = date ( 'd/m/Y' , $newdate );
                    }

                    $shippingDesc .= "<br />ETA: ".$eta;
                }
                
                $output .= "<p>" . $shippingDesc . "</p><p>" . $shippingAddress . "</p></div>";
            }
        }
        $inc++;
    }

    /*if( $qType == 'cart' ) {
        $bigpostShip = new Woo_BigPost_Shipping_Method();
        $bigpostShip->calculate_shipping();

        return wc_cart_totals_shipping_html();
    }*/
    //WC()->cart->calculate_totals();
    return $output;
}

function woobigpost_getLabel($JobType,$isBiz=0,$fork=0) {
    $settings = woobigpost_get_plugin_settings();
    $title = array();
    //Overwrites the HOME title for HDS if it is a business customer, but a HDS delivery
    if( $JobType == 'BUSINESS' || ($isBiz == 1 && $fork == 0 &&  $JobType == 'HDS')) {
        $title['label'] = 'Shipping To You - Business';
        $title['flag'] = 1;
        $title['description'] = $settings['shipping_business_description'];
    } elseif( $JobType == 'HDS') {
        $title['label'] = 'Shipping To You - Home';
        $title['flag'] = 1;
        $title['description'] = $settings['shipping_'.strtolower($JobType) .'_description'];
    }  elseif( $JobType == 'DEPOT' ) {
        $title['label'] = 'Shipping To Your Nearest Depot';
        $title['description'] = $settings['shipping_'.strtolower($JobType) .'_description'];
    } elseif( $JobType == '' ) {
        $title['label'] = '';
        $title['description'] = "";
    }

    return $title;
}

/**
 *
 */
function woobigpost_show_pricing_for_shipping( $result = array(), $page_type = 'product', $product_id = 0 ) {

    $settings = woobigpost_get_plugin_settings();

    $output = array();

    $JobType            = $result->JobType;
    $CarrierOptions     = $result->CarrierOptions;

    if( !empty($CarrierOptions) ) {

        $option = $CarrierOptions[0];
        $requires_auth = isset($option->RequiresAuthorityToLeave) ? $option->RequiresAuthorityToLeave : "";
        $inc = 1; $selected_option = '';
        $new_amount = 0;

        $is_margin      = $settings['shipping_margin'];
        $shippingMode   = $settings['margin_type'];
        $simpeMargin    = $settings['simple_margin_value'];
        $marginAction   = $settings['margin_action'];
        $marginType     = $settings['margin_fixed_percent'];

        /* Calculate shipping margin for single prodcut to returned amount from bigpost */
        if( $is_margin == 'Yes' ) {

            $amount = floatval( $option->Total );
            $raw_amount = floatval( $option->Total );
            $raw_charge = $option->Charge;

            if($settings['tax_exclusive_cart'] == "yes"){
                $amount = floatval( $option->Charge );
            }

            /* margin mode Simple or Advance */
            if( $shippingMode && $shippingMode == 'Simple' ) {

                if( isset($simpeMargin) ) {
                    /* calculate simple margin for products */
                    $new_amount = woobigpost_calculate_margin_amount( $amount, $simpeMargin, $marginAction, $marginType );
                    $raw_amount = woobigpost_calculate_margin_amount( $raw_amount, $simpeMargin, $marginAction, $marginType );
                    $raw_charge = woobigpost_calculate_margin_amount( $raw_charge, $simpeMargin, $marginAction, $marginType );

                    /* round off total */
                    $new_amount = woobigpost_round_off_amount( $new_amount, $settings['shipping_price_round'] );
                    $raw_amount = woobigpost_round_off_amount( $raw_amount, $settings['shipping_price_round'] );
                    $raw_charge = woobigpost_round_off_amount( $raw_charge, $settings['shipping_price_round'] );
                }
            } else {

                if( $page_type != 'product' ) {

                    global $woocommerce;
                    $data_items = array();

                    $items = $woocommerce->cart->get_cart();
                    $carton_rows = 0;
                    foreach( $items as $item ) {
                        $rows = get_post_meta( $item['product_id'], '_carton_items', true );
                        if( !empty($rows) ) {
                            if( count(json_decode($rows)) ) {
                                $carton_rows += count( json_decode($rows) );
                            }
                        }
                    }
                } else {
                    $rows = get_post_meta( $product_id, '_carton_items', true );
                    if( !empty($rows) ) {
                        if( count(json_decode($rows)) ) {
                            $carton_rows = count( json_decode($rows) );
                        }
                    }
                }

                $advanceMargin = $settings['advanced_margin_values'];
                if( count($advanceMargin) ) {
                    foreach( $advanceMargin as $record ) {
                        /* check range for no of cartons for Product */
                        if( $carton_rows >= $record['range_from'] && $carton_rows <= $record['range_end'] ) {
                            $new_amount = woobigpost_calculate_margin_amount( $amount, $record['value'], $marginAction, $marginType );
                            $new_amount = woobigpost_round_off_amount( $new_amount, $settings['shipping_price_round'] );

                            $raw_amount = woobigpost_calculate_margin_amount( $raw_amount, $record['value'], $marginAction, $marginType );
                            $raw_amount = woobigpost_round_off_amount( $raw_amount, $settings['shipping_price_round'] );
                            $raw_charge = woobigpost_round_off_amount( $raw_charge, $settings['shipping_price_round'] );
                            break;
                        }
                    }
                }
            }

            $output = array(
                'CarrierName'   => $option->CarrierName,
                'CarrierId'     => $option->CarrierId,
                'ServiceCode'     => $option->ServiceCode,
                'Charge'        => $raw_charge,
                'RawTotal'      => $raw_amount,
                'Tax'           => $option->Tax,
                'Total'         => $new_amount,
                'ShippingType'  => $JobType,
                'RequiresAuthorityToLeave' => $requires_auth,
                'Eta' => $option->Eta
            );
        } else {

            $new_amount = woobigpost_round_off_amount( $option->Total, $settings['shipping_price_round'] );
            $raw_amount = woobigpost_round_off_amount( $option->Total, $settings['shipping_price_round'] );
            if(isset($settings['tax_exclusive_cart']) && $settings['tax_exclusive_cart'] == "yes"){
                $new_amount = woobigpost_round_off_amount( $option->Charge, $settings['shipping_price_round'] );
                #$taxed_placeholder = ($new_amount * .1) + $new_amount;
                #$new_amount = round($taxed_placeholder,2,PHP_ROUND_HALF_DOWN) - ($new_amount * .1);

                //$new_amount = $new_amount / 1.1;
            }
            

            $output = array(
                'CarrierName'   => $option->CarrierName,
                'CarrierId'     => $option->CarrierId,
                'ServiceCode'     => $option->ServiceCode,
                'Charge'        => $option->Charge,
                'RawTotal'      => $raw_amount,
                'Tax'           => $option->Tax,
                'Total'         => $new_amount,
                'ShippingType'  => $JobType,
                'RequiresAuthorityToLeave' =>$requires_auth,
                'Eta' => $option->Eta
            );

        }
    }
    return $output;
}

function woobigpost_calculate_margin_amount( $amount = '', $margin_value = '', $margin_action = '', $margin_type = '' ) {

    $margin_value = floatval($margin_value);
    $margin_action = $margin_action;
    $margin_type = $margin_type;

    if( $margin_action == 'Add' ) {
        if( $margin_type == '$' ) {
            $new_amount = floatval( $margin_value + $amount );
        } else {
            $new_amount = floatval( $amount + (($margin_value*$amount)/100) );
        }
    } else {
        if( $margin_type == '$' ) {
            $new_amount = floatval( $amount -$margin_value );
        } else {
            $new_amount = floatval( $amount - (($margin_value*$amount)/100) );
        }
    }

    return $new_amount;
}

function woobigpost_round_off_amount( $new_amount, $round_shipping_value ) {
    
    if( $round_shipping_value == "RoundUp" ) {
        $new_amount = ceil($new_amount);
    } elseif( $round_shipping_value == "RoundDown" ) {
        $new_amount = floor($new_amount);
    } elseif( $round_shipping_value == "UpDown" ) {
        $new_amount = round( $new_amount, 0 );
    } else {
        $new_amount = $new_amount;
    }
    
    return $new_amount;
}

function woobigpost_print_calculated_advance_margin( $CarrierId, $CarrierName, $Charge, $Tax, $new_amount, $selected_option = '', $title, $shippingtype, $authority = "" ) {

    $string =  '<strong for="' . $shippingtype . '_carrierId_' . $CarrierId . '"><input name="bigpost_shipping_method" type="radio" id="' . $shippingtype  . '_carrierId_' . $CarrierId . '" data-carrierid="' . $CarrierId . '" data-carriername="' . esc_attr(addslashes($CarrierName)) . '" data-charge="' . $Charge . '" data-tax="' . $Tax . '"  data-total="' . $new_amount . '" data-shippingtype="' . $shippingtype . '" class="bigpost-carrier-id" ' . $selected_option . ' value="' . $CarrierId . '" data-authority="'.$authority.'"> <span class="lbl_inner">' . $title . '$' . number_format($new_amount, 2).'</span></strong>';

    return $string;
}

function woobigpost_print_calculated_advance_margin_product( $CarrierId, $CarrierName, $Charge, $Tax, $new_amount, $selected_option = '', $title, $shippingtype, $authority = "", $new_rate) {
    $shipping_rate = $new_rate == 0 ? 'FREE' : '$' . number_format($new_rate, 2);
    //$shipping_rate = '$' . number_format($new_rate, 2);
    $string =  '<strong for="' . $shippingtype . '_carrierId_' . $CarrierId . '" data-original="'.$new_amount.'"> <span class="lbl_inner" style="padding-left:0;"><span class="'.strtolower($shippingtype).'-text">' . $title ."</span>".$shipping_rate.'</span></strong>';
    return $string;
}

function woobigpost_export_depot_address( $post_code ='', $suburb = '', $depot_id = '', $return = false ) {

    $parameters = http_build_query(array(
        'p'=>$post_code,
        's'=>$suburb
    ));
    //$endpoint = "/api/depots?p=" . $post_code . '&s=' . $suburb;
    $endpoint = "/api/depots?".$parameters;
    //$depots   = woobigpost_send_api_request( $endpoint, '', 'GET');
    $depots = woobigpost_remote_api_request( $endpoint, '', 'GET');
    $body = wp_remote_retrieve_body($depots);
    $depots = array();

    if($body != false || !empty($body)){
        $depots = json_decode($body);
    }

    $address = '';
    if( isset($depots) && count($depots) > 0 ){
        foreach( $depots as $depot ) {
            $depotId = $depot->DepotId;
            if( $depot_id == $depotId ) {
                $address = $depot->DepotName . ', ' . $depot->Address . ', ' . $depot->Suburb . ', ' . $depot->State . ' - ' . $depot->Postcode;

                if( $return === true ) {
                    $address = $depot;
                }
            } else {
                continue;
            }
        }
    }
    return $address;
}


/* Get consignment number from Bigpost and save when order placced */
//add_action( 'woocommerce_checkout_order_processed', 'woocommerce_checkout_order_processed_func',  10, 1 );
$order_status = woobigpost_get_plugin_settings();
$order_status = isset($order_status['order_trigger_status'])?$order_status['order_trigger_status']:'';
$order_pushing_stage =  !empty($order_status) ? $order_status : array('processing', 'on-hold');
foreach($order_pushing_stage as $stage){
        add_action( 'woocommerce_order_status_'.$stage, 'woocommerce_checkout_order_processed_func',  10, 1 );
}

function woocommerce_checkout_order_processed_func($order_id) {
    global $woocommerce;
    
    $settings = woobigpost_get_plugin_settings();
    
    $consignment_number = get_post_meta($order_id, 'order_consignment_number', true);
    if(!empty($consignment_number)){
        return;
    }

    $order = wc_get_order( $order_id );

    if($settings['use_cis'] == "yes"){
        $cis_response = create_order_in_cis($order, $order_id);

        update_post_meta($order_id, 'order_consignment_number', print_r($cis_response, true));
        #update_post_meta($order_id, 'order_consignment_number', $consignment_number);
        #update_post_meta($order_id, 'order_Job_created', $order_created);
        #update_post_meta($order_id, 'order_job_id', $job_id);
        return;
    }

    $order->get_shipping_methods();

    if(!$order->has_shipping_method('woobigpost') && $settings['order_sync_only'] != "Yes") { //don'e send request to bigpost if shipping method is not woobigpost
        return;
    }


    $data = $data_to_send = array();
    $endpoint = 'api/createjob';

    $data_items = $data = array();
    $order_data = $order->get_data();

    $CarrierId = 0;
    $ShippingType = '';
    $BuyerLocality = '';
    $PickupLocality = '';
    $BuyerIsBusiness = 0;
    $HasForklift = 0;
    foreach( $order->get_items('shipping') as $key => $val ){
        $CarrierId = $val->get_meta('CarrierId');
        $ServiceCode = $val->get_meta('ServiceCode');
        $ShippingType = $val->get_meta('ShippingType');
        $BuyerLocality = $val->get_meta('BuyerLocality');
        $LeaveAuth = $val->get_meta('LeaveAuth');
        $PickupLocality = $val->get_meta('PickupLocality');
        $BuyerIsBusiness = $val->get_meta('BuyerIsBusiness');
        $HasForklift= $val->get_meta('HasForklift');
    }
    
    if($settings['order_sync_only'] == "Yes"){
        
        $pickup = $settings['bigpost_warehouse_locations'];
        $default_warehouse = $settings['default_warehouse'];

        $PickupLocality = array();
        $PickupLocality['Name'] = $pickup[$default_warehouse]['from_name'];
        $PickupLocality['Address'] = $pickup[$default_warehouse]['from_address'];
        $PickupLocality['LocalityId'] = $pickup[$default_warehouse]['from_postcode_id'];
        $PickupLocality['Locality'] = array(
            'Suburb' => $pickup[$default_warehouse]['from_suburb_addr'],
            'Postcode' => $pickup[$default_warehouse]['from_post_code'],
            'State' => $pickup[$default_warehouse]['from_state'],
            'Id' => $pickup[$default_warehouse]['from_postcode_id']
        );
        
        $BuyerLocality = array();
        $BuyerLocality['Id'] = get_post_meta($order_id, '_locality_id', true);
        $BuyerLocality['Suburb'] = $order_data['shipping']['city'];
        $BuyerLocality['Postcode'] = $order_data['shipping']['postcode'];
        $BuyerLocality['State'] = $order_data['shipping']['state'];
        
        $CarrierId = $settings['default_carrier_id'];
        $ServiceCode = $settings['default_service_code'];
        $ShippingType = $settings['default_shipping_type'];
        $LeaveAuth = $settings['default_leave_auth'];
        $BuyerIsBusiness = $ShippingType=="BUSINESS"?1:0;
        $HasForklift= $settings['default_has_forklift'];
    }

    $items = $order->get_items();
    
    $can_consolidated = $settings['is_advanced_mode'];

    //order items
    foreach( $items as $item ) {
        $item_product = $item->get_product();
        if($item_product){
            $parent_id = $item_product->get_parent_id() == 0 ? $item_product->get_id() : $item_product->get_parent_id(); //check if variation or not
            $show_plugin_value  = get_post_meta($parent_id, '_show_plugin', true);

            if($show_plugin_value == "0" && $settings['order_sync_only'] != "Yes"){
                continue; //exclude products where bigpost quote box is hidden
            }

            $rows = get_post_meta($item_product->get_id(), '_carton_items', true);
            $product_title = html_entity_decode( get_the_title($item_product->get_id()), ENT_QUOTES, 'UTF-8' );
            $item_quantity = $item->get_quantity();

            if( !empty($rows) ) {
                foreach( json_decode($rows) as $row ) {

                    if( $can_consolidated == 'Yes' && !empty($row->consolidated) ) {
                        $can_consolidated = $row->consolidated;
                    }

                    $data_items[] = array(
                        'ItemType'          =>  intval($row->packaging_type),
                        'Description'       => woobigpost_trim_text($product_title, 50, false, true),
                        'Length'            => floatval($row->length),
                        'Width'             => floatval($row->width),
                        'Height'            => floatval($row->height),
                        'Weight'            => floatval($row->weight),
                        'Quantity'          => $item_quantity,
                        'Consolidatable'    => ( $can_consolidated == 'Yes' ) ? true : false
                    );
                }
            }
        }
    }

    $jobType = $ShippingType == 'HDS'?3:2;
    $data = $data_items;

    $BuyerLocalityId = (isset($BuyerLocality['Id']))?$BuyerLocality['Id']:'';
    $BuyerSuburb = (isset($BuyerLocality['Suburb']))?$BuyerLocality['Suburb']:'';
    $BuyerPostcode = (isset($BuyerLocality['Postcode']))?$BuyerLocality['Postcode']:'';
    $BuyerState = (isset($BuyerLocality['State']))?$BuyerLocality['State']:'';

    $customer_address = $order_data['shipping']['first_name']." ".$order_data['shipping']['last_name'].", ".$order_data['shipping']['address_1'].", ".$order_data['shipping']['address_2'].", ".$order_data['shipping']['city']." ".$order_data['shipping']['state'].$order_data['shipping']['postcode'];
    $customer_name = $order_data['billing']['first_name']." ".$order_data['billing']['last_name'];
    $customer_company = isset($order_data['shipping']['company']) && !empty($order_data['shipping']['company']) ? $order_data['shipping']['company'] : $order_data['billing']['company'];
    $data_to_send   = array(
        'JobId'                     => $order_id,
        'JobDate'                   => date('Y-m-d H:i:s'),
        'JobType'                   => $jobType,
        'ContactName'               => $customer_name,
        'BuyerEmail'                => $order_data['billing']['email'],
        'BuyerMobilePhone'          => $order_data['billing']['phone'],
        'BuyerOtherPhone'           => $order_data['billing']['phone'],
        'CarrierId'                 => absint($CarrierId),
        'servicecode'               => $ServiceCode,
        'Reference'                 => "Big Post $order_id",
        'ContainsDangerousGoods'    => false,
        'SpecialInstructions'       => "No Instructions",
        'PickupLocation'            => array(
            'Name'      => $PickupLocality['Name'],
            'Address'   => $PickupLocality['Address'],
            "LocalityId"    => absint($PickupLocality['LocalityId']),
            'Locality'  => $PickupLocality['Locality']
        ),
        'BuyerLocation'  => array(
            'Name'      => !empty($customer_company) ? $customer_company : $customer_name,
            'Address'   => $ShippingType == 'DEPOT' ? $BuyerLocality['DepotAddress']['company'] : $order_data['shipping']['address_1'],
            'AddressLineTwo'  => $ShippingType == 'DEPOT' ? $BuyerLocality['DepotAddress']['address_1']: $order_data['shipping']['address_2'],
            'Locality'  => array(
                "Suburb"    => $ShippingType == 'DEPOT' ? $BuyerLocality['DepotAddress']['city'] : $BuyerSuburb,
                "Postcode"  => $ShippingType == 'DEPOT' ? $BuyerLocality['DepotAddress']['postcode'] : $BuyerPostcode,
                "State"     => $ShippingType == 'DEPOT' ? $BuyerLocality['DepotAddress']['state']: $BuyerState
            )
        ),
        'Items'     => $data
    );

    if($ShippingType != 'DEPOT'){
        $data_to_send['BuyerLocation']['LocalityId'] = absint($BuyerLocalityId);
        $data_to_send['BuyerLocation']['Locality']['Id'] = absint($BuyerLocalityId);
    }


    $data_to_send['BuyerIsBusiness'] = ($BuyerIsBusiness == 1)?true:false;
    $data_to_send['BuyerHasForklift'] = ($HasForklift == 1)?true:false;



    if($ShippingType == 'DEPOT'){
        $data_to_send['JobType'] = 1;
        $BuyerToDepotId = (isset($BuyerLocality['ToDepotId']))?$BuyerLocality['ToDepotId']:'';
        $data_to_send['DepotId'] = (int)$BuyerToDepotId;

    }

    if( $ShippingType == 'HDS'){
        $data_to_send['JobType'] = 3;
        $data_to_send['DepotId'] = 0;
        $data_to_send['AuthorityToLeave'] = ($LeaveAuth == '1')?1:0;
    }

    $data_to_send['sourceType'] = 2;

    //$server_output = woobigpost_send_api_request($endpoint, $data_to_send, 'POST'); old code
    $server_output = woobigpost_remote_api_request($endpoint,$data_to_send,'POST');
    $body = wp_remote_retrieve_body($server_output);

    if($body != false || !empty($body)){
        $server_response = $body;
    } else {
        $server_response = array(
            'code'=> wp_remote_retrieve_response_code( $server_output ),
            'message' => wp_remote_retrieve_response_message($server_output)
        );

        $server_response = json_encode($server_response);
    }

    update_post_meta( $order_id, 'bigpost_order_payload', json_encode($data_to_send, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    update_post_meta( $order_id, 'bigpost_order_response', $server_response);

    if(is_object(json_decode($body)) || $body != false) {
        $result = json_decode($server_response);
        if(empty($result->Errors)) {

            $job_id                     = $result->Object->JobId;
            $consignment_number     = $result->Object->CarrierConsignmentNumber;
            //WC()->session->set("CarrierConsignmentNumber", $consignment_number);
            $order_created = date('Y-m-d H:i:s');
            update_post_meta($order_id, 'order_consignment_number', $consignment_number);
            update_post_meta($order_id, 'order_Job_created', $order_created);
            update_post_meta($order_id, 'order_job_id', $job_id);
        }else{
            $msg = 'From - ' . site_url();
            $msg .= 'Data Sent - ' . json_encode($data_to_send);
            $msg .= 'API Server Response - ' . $server_response;
        }
    }

    if( isset(WC()->session) ){
        if(WC()->session->__isset('Shipping_Method_Selected')){
            WC()->session->__unset( 'Shipping_Method_Selected' );    
        }
        
        if(WC()->session->__isset('Shipping_Method_Data')){
            WC()->session->__unset( 'Shipping_Method_Data' );
        }

        if(WC()->session->__isset('woobigpost-buyer_is_business')){
            WC()->session->__unset( 'woobigpost-buyer_is_business' );
        }

        if(WC()->session->__isset('woobigpost-leave_authority')){
            WC()->session->__unset( 'woobigpost-leave_authority' );
        }

        if(WC()->session->__isset('woobigpost-has_forklift')){
            WC()->session->__unset( 'woobigpost-has_forklift' );
        }

        if(WC()->session->__isset('woobigpost-tosuburb')){
            WC()->session->__unset( 'woobigpost-tosuburb' );
        }

        if(WC()->session->__isset('woobigpost-tostate')){
            WC()->session->__unset( 'woobigpost-tostate' );
        }

        if(WC()->session->__isset('woobigpost-topostcode')){
            WC()->session->__unset( 'woobigpost-topostcode' );
        }

        if(WC()->session->__isset('woobigpost-topostcodeid')){
            WC()->session->__unset( 'woobigpost-topostcodeid' );
        }

        if(WC()->session->__isset('woobigpost-todepotid')){
            WC()->session->__unset( 'woobigpost-todepotid' );
        }

        if(WC()->session->__isset('woobigpost-cheapest_warehouse_index')){
            WC()->session->__unset( 'woobigpost-cheapest_warehouse_index' );
        }

        if(WC()->session->__isset('woobigpost-cheapest_depot_warehouse_index')){
            WC()->session->__unset( 'woobigpost-cheapest_depot_warehouse_index' );
        }

        if(WC()->session->__isset('woobigpost-WooBigPost_shippingPrices_Display')){
            WC()->session->__unset( 'WooBigPost_shippingPrices_Display');
        }

        if(WC()->session->__isset('shipping_bigpost_methods')){
            WC()->session->__unset('shipping_bigpost_methods');
        }

        if(WC()->session->__isset('shipping_calculated_cost_bigpost')){
            WC()->session->__unset('shipping_calculated_cost_bigpost');
        }

        if(WC()->session->__isset('woobigpost-post_data')){
            WC()->session->__unset('woobigpost-post_data');
        }

        if(WC()->session->__isset('free_disabled_items')){
            WC()->session->__unset( 'free_disabled_items');
        }

        WC()->session->set( 'WooBigPost_shippingPrices_Display', null );

        WC()->customer->set_shipping_state(null);
        WC()->customer->set_shipping_city(null);
        WC()->customer->set_shipping_postcode( null);
    }


    //return $order_id;
}

add_action( 'woocommerce_admin_order_data_after_billing_address', 'woobigpost_display_consignment_number_for_order_func', 10, 1 );

function woobigpost_display_consignment_number_for_order_func($order){
    if( get_post_meta($order->get_id(), 'order_consignment_number', true) ) {
        echo '<p><strong>'.__('Carrier Consignment Number').':</strong> <br/>' . get_post_meta($order->get_id(), 'order_consignment_number', true) . '</p>';
    }
}

//add_filter('woocommerce_package_rates', 'woobigpost_update_shipping_costs_based_on_cart_session_custom_data', 10, 2);

function woobigpost_get_cheapest_delivery_opt($delivery_options, $qType = 'product', $post_data){
    $buyer_is_business = (isset($post_data['buyer_is_business']))?$post_data['buyer_is_business']: WC()->session->get('woobigpost-buyer_is_business');
    $has_forklift = (isset($post_data['has_forklift']))?  (int)$post_data['has_forklift']: (int)WC()->session->get('woobigpost-has_forklift');
    $type = $buyer_is_business == 1 ? 'BUSINESS' : 'HDS';

    /*if(isset($post_data['has_forklift']) && $has_forklift == 0 && $type == 'BUSINESS'){
        $type = 'HDS';
    }*/

    $settings = woobigpost_get_plugin_settings();
    $shipping_types = $settings['shipping_types'];
    $item_types = array();

    if( $qType == 'product' ) {
        $p_id = sanitize_text_field($_POST['productId']);
        if($post_data['parent_product'] > 0){
            $p_id = sanitize_text_field($post_data['parent_product']);
        }

        $admin_setting_product = get_post_meta( $p_id, '_use_admin_setting', true );
        if( $admin_setting_product != 'Yes' && $admin_setting_product != '' ) {
            $product_stypes = get_post_meta( $p_id, '_shipping_type', true );
            $shipping_types = json_decode( $product_stypes );
        }

        $rows = get_post_meta($p_id, '_carton_items', true);

        if($rows){
            foreach( json_decode($rows) as $row ) {
                $item_types[] = $row->packaging_type;
            }
        }

    } else {
        global $woocommerce;

        $items = $woocommerce->cart->get_cart();
        
        foreach( $items as $item ) {
            $product_id = ( isset($item['product_id']) ) ? $item['product_id'] : '';

            $use_admin_setting  = get_post_meta( $product_id, '_use_admin_setting', true );
            if( $use_admin_setting != 'Yes' && $use_admin_setting != '' ) {

                $product_stypes = get_post_meta( $product_id, '_shipping_type', true );
                $product_stypes = json_decode( $product_stypes );
                foreach( $shipping_types as $key => $stype ) {
                    if( ! in_array($stype, $product_stypes) ) {
                        unset( $shipping_types[$key] );
                    }
                }
            }

            $rows = get_post_meta($product_id, '_carton_items', true);

            if($rows){
                foreach( json_decode($rows) as $row ) {
                    $item_types[] = $row->packaging_type;
                }    
            }
            
        }
    }

    if(!$shipping_types){
        $shipping_types = $settings['shipping_types'];
    }

    $shippings = $shipping_types;

    $min = array();
    $mins = array();
    $min_depot = array();
    $show_all_options = false; //(isset($settings['show_all_options']) && $settings['show_all_options'] == "Yes")?true:false;
    
    if(!empty($delivery_options)){
        foreach($delivery_options as $key=>$option){
			
            if(isset($option->Object)){
                $object = $option->Object;
				
                foreach($object->DeliveryOptions as $jobType){
					
                    //restrict carriers here
                    if($settings['restrict_carriers'] == "Yes"){
                        foreach($jobType->CarrierOptions as $ck => $co){

                            if($settings['restrict_carrier_'.$co->CarrierId] == "yes"){
                                $allowed_item_types = $settings['allowed_items_'.$co->CarrierId];
                                if(!$allowed_item_types){
                                    $allowed_item_types = [];
                                }
                                $matched_item_types = array_intersect($item_types, $allowed_item_types);

                                if(count($matched_item_types) < count($item_types)){
                                    unset($jobType->CarrierOptions[$ck]);
                                }
                            }

                            if($settings['restrict_carrier_'.$co->CarrierId] == "no"){
                                unset($jobType->CarrierOptions[$ck]);
                            }
                        }    
                    }

					$jobType->CarrierOptions = array_values($jobType->CarrierOptions);
					//echo "<pre>"; print_r($jobType->CarrierOptions); echo "</pre>";
                    if( !empty($shippings) && in_array($jobType->JobType, $shippings) ) {

                        if(isset($jobType->JobType) && ($jobType->JobType != "DEPOT")){ //check if its business or home
                            //get min total which is the first in the array because it is already sorted from lowest to highest
                            if(isset($settings['tax_exclusive_widget']) && $settings['tax_exclusive_widget'] == "yes"){
                                $min[$key] = !empty($jobType->CarrierOptions) ? $jobType->CarrierOptions[0]->Charge : "";
                            }else{
                                $min[$key] = !empty($jobType->CarrierOptions) ? $jobType->CarrierOptions[0]->Total : "";    
                            }

                            if($jobType->JobType == $type && $show_all_options == false){
                                break;
                            }else{

                                $jobkey = $jobType->JobType;
                                if($jobType->JobType == "HDS" && $jobType->CarrierOptions[0]->RequiresAuthorityToLeave){
                                    $jobkey = "HDS_ATL";
                                }
                                
                                if(!isset($mins[$jobkey])){
                                    $mins[$jobkey] = array();
                                    $mins[$jobkey][$key] = $min[$key];

                                    break;
                                }
                            }
                            
                            //break;
                        } else { //try depot
                            if(isset($settings['tax_exclusive_widget']) && $settings['tax_exclusive_widget'] == "yes"){
                                $min[$key] = !empty($jobType->CarrierOptions) ? $jobType->CarrierOptions[0]->Charge : "";
                            }else{
                                $min[$key] = !empty($jobType->CarrierOptions) ? $jobType->CarrierOptions[0]->Total : "";
                            }
                        }
                        //get all the cheapest rates from the all locations to be filtered down below
                        if($jobType->JobType == 'DEPOT'){
                            if(isset($settings['tax_exclusive_widget']) && $settings['tax_exclusive_widget'] == "yes"){
                                $min_depot[$key] = !empty($jobType->CarrierOptions) ? $jobType->CarrierOptions[0]->Charge : "";
                            }else{
                                $min_depot[$key] = !empty($jobType->CarrierOptions) ? $jobType->CarrierOptions[0]->Total : "";
                            }
                        }
                    }

                }
            }
        }
        $data_of_delivery_type = false;

        if(!empty($min) && is_array($min)){
            //get the key of the min total from the minimum array
            if(array_filter($min)){
                $min_key = array_keys($min, @min(array_filter($min))); //this returns the index of the array that has the min total from the carrier options
                WC()->session->set( 'woobigpost-cheapest_warehouse_index', $min_key);
                //get the key of the locations which has the cheapest price for depot option
                
                $data_of_delivery_type = $delivery_options[$min_key[0]];

                if($show_all_options){
                    $DeliveryOptions = array();
                    foreach($mins as $k=>$m){
                        $mins_key = array_keys($m, @min(array_filter($m)));
                        $DeliveryOptions[] = $delivery_options[$mins_key[0]]->Object->DeliveryOptions;
                    }

                    $data_of_delivery_type->Object->DeliveryOptions = array();
                    $has_depot = false;
                    foreach($DeliveryOptions as $DeliveryOption){
                        foreach($DeliveryOption as $do){
                            if($has_depot && $do->JobType == "DEPOT"){
                                continue;
                            }

                            if(!$has_depot && $do->JobType == "DEPOT"){
                                $has_depot = true;
                            }

                            $data_of_delivery_type->Object->DeliveryOptions[] = $do;    
                        }
                    }
                }
            }
            
            //PROCESS $data_of_delivery_type to include both hds and business

            //$data_of_delivery_type = $delivery_options[$min_key_hds[0]];

            //$data_of_delivery_type->Object->DeliveryOptions[2] = $delivery_options[$min_key_bus[0]]->Object->DeliveryOptions[1];
             //array_merge($delivery_options[$min_key_hds[0]], $delivery_options[$min_key_bus[0]]);
             //get the data with the cheapest option by delivery type ie home or business

            
        }
        update_option('delivery_options', print_r([$mins, $delivery_options], true));

        if(!empty($min_depot)){

            $min_depot_key = array_keys($min_depot, @min(array_filter($min_depot)));
            WC()->session->set( 'woobigpost-cheapest_depot_warehouse_index', $min_depot_key);
            
            if(is_array($min_depot_key) && isset($min_depot_key[0]))
            $data_of_depot = $delivery_options[$min_depot_key[0]]; //get the data with the cheapest option by delivery type ie home or business

            //time to manipulate the array
            $depot_data = "";
            if ($data_of_depot) {
                foreach($data_of_depot->Object->DeliveryOptions as $opt){
                    if($opt->JobType == 'DEPOT'){
                        $depot_data = $opt;
                        break;
                    }
                }
            }
            
            if(!$data_of_delivery_type){
                $data_of_delivery_type = $delivery_options[$min_depot_key[0]];
            }

            //update the original array
            if ($data_of_delivery_type) {
               foreach($data_of_delivery_type->Object->DeliveryOptions as $key=>$opt){
                    if($opt->JobType == 'DEPOT'){
                        $data_of_delivery_type->Object->DeliveryOptions[$key] = $depot_data;
                        break;
                    }
                }
            }
        }

        update_option('data_of_delivery_type', print_r($data_of_delivery_type, true));
        
        return $data_of_delivery_type;
    }
}

function woobigpost_get_selected_warehouse($warehouses){
    $settings = woobigpost_get_plugin_settings();
    $all_warehouses = $settings['bigpost_warehouse_locations'];
    $warehouse_arr = array();

    if(is_array($warehouses) && !empty($warehouses)){
        foreach($warehouses as $warehouse){
            $warehouse_arr[$warehouse] = $all_warehouses[$warehouse];
        }
    }

    return $warehouse_arr;
}

function woobigpost_woocommerce_version_number() {
    // If get_plugins() isn't available, require it
    if ( ! function_exists( 'get_plugins' ) )
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

    // Create the plugins folder and file variables
    $plugin_folder = get_plugins( '/' . 'woocommerce' );
    $plugin_file = 'woocommerce.php';

    // If the plugin version number is set, return it
    if ( isset( $plugin_folder[$plugin_file]['Version'] ) ) {
        return $plugin_folder[$plugin_file]['Version'];

    } else {
        // Otherwise return null
        return NULL;
    }
}

/**
 * @return bool
 */
function bp_woocommerce_version_check() {
    if(version_compare( woobigpost_woocommerce_version_number(), WOO_BIGPOST_WOOCOMMERCE_MIN_VERSION, "<" )){
        return false;
    } else {
        return true;
    }
}

/**
 * @param $input
 * @param $length
 * @param bool $ellipses
 * @param bool $strip_html
 * @return string
 */
function woobigpost_trim_text($input, $length, $ellipses = true, $strip_html = true) {
    //strip tags, if desired
    if ($strip_html) {
        $input = strip_tags($input);
    }

    //no need to trim, already shorter than trim length
    if (strlen($input) <= $length) {
        return $input;
    }

    //find last space within length
    $last_space = strrpos(substr($input, 0, $length), ' ');
    $trimmed_text = substr($input, 0, $last_space);

    //add ellipses (...)
    if ($ellipses) {
        $trimmed_text .= '...';
    }

    return $trimmed_text;
}

function woobigpost_get_shipping_rates( $result, $qType = 'product', $post_data){
    $settings = woobigpost_get_plugin_settings();
    $type = $post_data['buyer_is_business']  ? 'BUSINESS' : 'HDS'; //added 02/11/2018
    $has_forklift = (isset($post_data['has_forklift']))?sanitize_text_field($post_data['has_forklift']):WC()->session->get('woobigpost-has_forklift');

    if(isset($post_data['has_forklift']) && $has_forklift == 0 && $type == 'BUSINESS'){
        $type = 'HDS';
    }

    echo $type;

    $output = array();

    WC()->session->set_customer_session_cookie( true );

    $shipping_types = $settings['shipping_types'];

    if( $qType == 'product' ) {
        $p_id = sanitize_text_field($_POST['productId']);
        if($post_data['parent_product'] > 0){
            $p_id = sanitize_text_field($post_data['parent_product']);
        }

        $admin_setting_product = get_post_meta( $p_id, '_use_admin_setting', true );
        if( $admin_setting_product != 'Yes' && $use_admin_setting != '' ) {
            $product_stypes = get_post_meta( $p_id, '_shipping_type', true );
            $shipping_types = json_decode( $product_stypes );
        }
    } else {
        global $woocommerce;

        $items = $woocommerce->cart->get_cart();
        foreach( $items as $item ) {
            $product_id = ( isset($item['product_id']) ) ? $item['product_id'] : '';

            $use_admin_setting  = get_post_meta( $product_id, '_use_admin_setting', true );
            if( $use_admin_setting != 'Yes' && $use_admin_setting != '' ) {

                $product_stypes = get_post_meta( $product_id, '_shipping_type', true );
                $product_stypes = json_decode( $product_stypes );
                foreach( $shipping_types as $key => $stype ) {
                    if( ! in_array($stype, $product_stypes) ) {
                        unset( $shipping_types[$key] );
                    }
                }
            }
        }
    }

    $shippings = $shipping_types;

    $DeliveryOptions = $result->Object->DeliveryOptions;

    $inc = 1;
    $dflag = 0;
    $jobType = 0;

    foreach( array_reverse($DeliveryOptions) as $option ) {

        $havec = WC()->session->get('woobigpost-have_confirm');
        if($option->JobType === 'HDS' &&  $havec == 0) continue;

        /* determines if there is a previous HDS job type so as to pre append the "alternate method"*/
        if(($option->JobType === 'HDS' or $option->JobType === 'BUSINESS') and count($option->CarrierOptions) > 0) {
            $jobType++;
        }

        if( !empty($shippings) && in_array($option->JobType, $shippings) ) {
            /* get shipping price for current delivery type */
            if(($type == $option->JobType) || $option->JobType == 'DEPOT'){
                $price_data = woobigpost_show_pricing_for_shipping( $option, $qType, sanitize_text_field($_POST['productId']) );
            }

            $title = woobigpost_getLabel($option->JobType, WC()->session->get('woobigpost-buyer_is_business'), WC()->session->get('woobigpost-has_forklift'));
            $shippingDescription = $title['description'];

            if(isset($title['flag']) && $jobType > 0) $dflag = $title['flag'];
            $title = $title['label'].': ';

            if( is_array($price_data) && !empty($price_data) ) {
                if($qType == "product" && $type == "BUSINESS" && $option->JobType == 'DEPOT'){ continue; }
                if ($dflag == 1 && isset($option->JobType) && $option->JobType == 'DEPOT')  $output .= '<div class="alternate-label">Or alternatively...</div>';
                /* print calculated margin amount result here */
                $output .= "<div class='shipping-type-result woobigpost-sqf-fields'><h4>".$settings['shipping_'.strtolower($option->JobType) .'_label']."</h4>";
                $free_shipping_check = new Woo_BigPost_Free_Shipping_Check();
                $new_shipping_rate = $price_data['Total'];

                $new_shipping_rate = $price_data['RawTotal'];
                if(isset($settings['tax_exclusive_widget']) && $settings['tax_exclusive_widget'] == 'yes'){
                    $new_shipping_rate = $price_data['Charge'];
                }

                if($qType == 'product'){
                    $new_shipping_rate = $free_shipping_check->check_product_if_free($_POST,$price_data['Total']);
                }

                if($qType == 'cart'){
                    $new_shipping_rate = $free_shipping_check->check_cart_if_free($price_data['Total'],$post_data['mixed']);
                }

                $output .= woobigpost_print_calculated_advance_margin_product( $price_data['CarrierId'], $price_data['CarrierName'], $price_data['Charge'], $price_data['Tax'], $price_data['Total'], '', $title, $option->JobType, $price_data['RequiresAuthorityToLeave'], $new_shipping_rate);

                $shippingDesc = '';
                $shippingAddress = '';

                if( isset($option->JobType) && $option->JobType == 'DEPOT' ) {
                    $depot_address = woobigpost_export_depot_address( $option->DepotLocality->Postcode, $option->DepotLocality->Suburb, $option->DepotLocality->Id );

                    $shippingDesc .= $settings['shipping_'.strtolower($option->JobType) .'_description'];

                    $shippingAdrs = $depot_address;
                    $shippingAddress .= ( $shippingAdrs ) ? 'For Pick Up At: '.$option->DepotLocality->Suburb.' Depot' : '';
                } else {
                    if( isset($option->JobType) ){

                        $shippingDesc_ = $shippingDescription;

                        if(isset($post_data['leave_auth']) && $post_data['leave_auth'] == '1' && $option->JobType == 'HDS'){
                            $shippingDesc_ = $settings['shipping_hds_atl_description'];
                        }

                        $shippingDesc .= $shippingDesc_;
                        $shippingAddress .= "";
                    }
                }
                $output .= "<p>" . $shippingDesc . "</p><p>" . $shippingAddress . "</p></div>";
            }
        }
        $inc++;
}
}

/**
 * @param $disabled_items
 * @param $total_items
 * @return bool
 */
function woobigpost_process_free_shipping($disabled_items, $total_items){
    $settings = woobigpost_get_plugin_settings();
    if($settings['free_shipping_config'] != 'disabled'){
        if($settings['free_shipping_config'] == 'enable_specific_items' && count($disabled_items) < count($total_items)){
            return true;
        } else if($settings['free_shipping_config'] == 'enable_all'){
            return true;
        } else {
            return false;
        }

    } else {
        return false;
    }
}

//create an array of order status with value and label fields
function createOrderStatusOpt(){
    $order_statuses = wc_get_order_statuses();
    $order_status_opt = array();
    foreach($order_statuses as $key=>$status){
        $new_key = str_replace('wc-', "", $key);
        $order_statuses[$new_key] = $status;
        unset($order_statuses[$key]);
        $order_status_opt[] = array('value'=>$new_key, 'label'=>$status);
    }

    return $order_status_opt;
}

function get_active_packages(){
    global $woocommerce;

    $bh_packages =  $woocommerce->cart->get_shipping_packages();

    $bh_shipping_methods = array();
    foreach( $bh_packages as $bh_package_key => $bh_package ) {
        $bh_shipping_methods[$bh_package_key] = $woocommerce->shipping->calculate_shipping_for_package($bh_package, $bh_package_key);
    }

    $shippingArr = $bh_shipping_methods[0]['rates'];
    if(!empty($shippingArr)) {
        $response = array();
        foreach ($shippingArr as $value) {
            $response[] = $value->method_id;
        }
    }

    return $response;
}

function get_zone_active_shipping(){
    $shipping_packages =  WC()->cart->get_shipping_packages();
    // Get the WC_Shipping_Zones instance object for the first package
    $shipping_zone = wc_get_shipping_zone( reset( $shipping_packages ) );
    $zone_id   = $shipping_zone->get_id(); // Get the zone ID
    $zones = WC_Shipping_Zones::get_zones();
    $shipping_methods = isset($zones[$zone_id]) ? $zones[$zone_id]['shipping_methods']: array();
    $active_methods = array();

    if(!empty($shipping_methods)){
        foreach($shipping_methods as $shipping_method){
            if($shipping_method->enabled == 'yes'){
                $active_methods[] = $shipping_method->id;
            }
        }
    }

    return $active_methods;
}

function check_if_active(){
    $active_shipping = get_zone_active_shipping();

    if(!empty($active_shipping) && in_array('woobigpost', $active_shipping)){
        return true;
    } else {
        return false;
    }
}

/**
 * @param $items
 * @param $settings
 * @return int
 */
function count_disabled_items($items, $settings){
    $disabled_item = 0;
    foreach($items as $item){
        $parent_product = $item['product_id'];

        $show_plugin        = get_post_meta( $parent_product, '_show_plugin', true );
        $show_plugin_value  = $show_plugin == "" && $settings['product_page'] != 'hide' ? '1' : $show_plugin; //if initial setup,show plugin on product page by default

        if($show_plugin_value == "0"){
            $disabled_item++;
        }
    }

    return $disabled_item;
}

function get_carriers(){
    $carriers = '{
                   "Object":{
                      "2":[
                         {
                            "CarrierId":2,
                            "CarrierName":"Northline",
                            "ServiceCode":"GEN",
                            "ServiceName":"GENERAL (GEN)"
                         }
                      ],
                      "3":[
                         {
                            "CarrierId":3,
                            "CarrierName":"TGE Standard",
                            "ServiceCode":"",
                            "ServiceName":""
                         }
                      ],
                      "4":[
                         {
                            "CarrierId":4,
                            "CarrierName":"TNT",
                            "ServiceCode":"75",
                            "ServiceName":"Overnight Express (75)"
                         },
                         {
                            "CarrierId":4,
                            "CarrierName":"TNT",
                            "ServiceCode":"76",
                            "ServiceName":"Road Express (76)"
                         },
                         {
                            "CarrierId":4,
                            "CarrierName":"TNT",
                            "ServiceCode":"717B",
                            "ServiceName":"Technology Express (717B)"
                         }
                      ],
                      "5":[
                         {
                            "CarrierId":5,
                            "CarrierName":"TGE Palletised",
                            "ServiceCode":"",
                            "ServiceName":""
                         }
                      ],
                      "6":[
                         {
                            "CarrierId":6,
                            "CarrierName":"Couriers Please",
                            "ServiceCode":"EXP",
                            "ServiceName":"General Freight (EXP)"
                         },
                         {
                            "CarrierId":6,
                            "CarrierName":"Couriers Please",
                            "ServiceCode":"EXPFR",
                            "ServiceName":"General Freight (Flat Rate) (EXPFR)"
                         },
                         {
                            "CarrierId":6,
                            "CarrierName":"Couriers Please",
                            "ServiceCode":"P2A",
                            "ServiceName":"P2A (P2A)"
                         },
                         {
                            "CarrierId":6,
                            "CarrierName":"Couriers Please",
                            "ServiceCode":"P10A",
                            "ServiceName":"P10A (P10A)"
                         },
                         {
                            "CarrierId":6,
                            "CarrierName":"Couriers Please",
                            "ServiceCode":"P15A",
                            "ServiceName":"P15A (P15A)"
                         },
                         {
                            "CarrierId":6,
                            "CarrierName":"Couriers Please",
                            "ServiceCode":"P25A",
                            "ServiceName":"P25A (P25A)"
                         }
                      ],
                      "14":[
                         {
                            "CarrierId":14,
                            "CarrierName":"Aramex",
                            "ServiceCode":"Box service",
                            "ServiceName":"Box Service (Box service)"
                         },
                         {
                            "CarrierId":14,
                            "CarrierName":"Aramex",
                            "ServiceCode":"SAT-LOC-A3",
                            "ServiceName":"Local A3 Satchel (SAT-LOC-A3)"
                         },
                         {
                            "CarrierId":14,
                            "CarrierName":"Aramex",
                            "ServiceCode":"SAT-NAT-A2",
                            "ServiceName":"National network A2 satchel (SAT-NAT-A2)"
                         },
                         {
                            "CarrierId":14,
                            "CarrierName":"Aramex",
                            "ServiceCode":"SAT-NAT-A3",
                            "ServiceName":"National network A3 satchel (SAT-NAT-A3)"
                         },
                         {
                            "CarrierId":14,
                            "CarrierName":"Aramex",
                            "ServiceCode":"SAT-NAT-A4",
                            "ServiceName":"National network A4 satchel (SAT-NAT-A4)"
                         },
                         {
                            "CarrierId":14,
                            "CarrierName":"Aramex",
                            "ServiceCode":"SAT-NAT-A5",
                            "ServiceName":"National network A5 satchel (SAT-NAT-A5)"
                         },
                         {
                            "CarrierId":14,
                            "CarrierName":"Aramex",
                            "ServiceCode":"PARCEL",
                            "ServiceName":"Parcel (PARCEL)"
                         }
                      ],
                      "15":[
                         {
                            "CarrierId":15,
                            "CarrierName":"Hi Trans",
                            "ServiceCode":"G",
                            "ServiceName":"General (G)"
                         },
                         {
                            "CarrierId":15,
                            "CarrierName":"Hi Trans",
                            "ServiceCode":"X",
                            "ServiceName":"Express (X)"
                         },
                         {
                            "CarrierId":15,
                            "CarrierName":"Hi Trans",
                            "ServiceCode":"L",
                            "ServiceName":"Local (L)"
                         }
                      ],
                      "16":[
                         {
                            "CarrierId":16,
                            "CarrierName":"Hunter Express",
                            "ServiceCode":"RF",
                            "ServiceName":"Road Freight (RF)"
                         }
                      ],
                      "17":[
                         {
                            "CarrierId":17,
                            "CarrierName":"Allied Express",
                            "ServiceCode":"R",
                            "ServiceName":"Road Express (R)"
                         }
                      ],
                      "18":[
                         {
                            "CarrierId":18,
                            "CarrierName":"Josies Transport",
                            "ServiceCode":"",
                            "ServiceName":""
                         }
                      ],
                      "19":[
                         {
                            "CarrierId":19,
                            "CarrierName":"Sadleirs",
                            "ServiceCode":"",
                            "ServiceName":""
                         },
                         {
                            "CarrierId":19,
                            "CarrierName":"Followmont",
                            "ServiceCode":"",
                            "ServiceName":""
                         }
                      ],
                      "21":[
                         {
                            "CarrierId":21,
                            "CarrierName":"Sampson Express",
                            "ServiceCode":"",
                            "ServiceName":""
                         }
                      ],
                      "23":[
                         {
                            "CarrierId":23,
                            "CarrierName":"CRL Express",
                            "ServiceCode":"",
                            "ServiceName":""
                         }
                      ],
                      "24":[
                         {
                            "CarrierId":24,
                            "CarrierName":"Parca Logistics",
                            "ServiceCode":"",
                            "ServiceName":""
                         }
                      ],
                      "25":[
                         {
                            "CarrierId":25,
                            "CarrierName":"Cochranes Transport",
                            "ServiceCode":"",
                            "ServiceName":""
                         }
                      ]
                   },
                   "Errors":null
                }';

    return json_decode($carriers, true);
}

function create_order_in_cis($order, $order_id){
    $settings = woobigpost_get_plugin_settings();

    $order_data = $order->get_data();

    $CarrierId = 0;
    $ServiceCode = '';
    $ShippingType = '';
    $LeaveAuth = '';
    $QuoteId = 0;

    foreach( $order->get_items('shipping') as $key => $val ){
        $CarrierId = $val->get_meta('CarrierId');
        $ServiceCode = $val->get_meta('ServiceCode');
        $ShippingType = $val->get_meta('ShippingType');
        $LeaveAuth = $val->get_meta('RequiresAuthorityToLeave');
        $QuoteId = $val->get_meta('QuoteId');
    }

    $items = $order->get_items();
    
    $can_consolidated = $settings['is_advanced_mode'];

    $line_items = array();
    foreach( $items as $item ) {
        
        $product = wc_get_product($item->get_product_id());

        if($product){
            $sku = $product->get_sku();
            $qty = $item->get_quantity();

            $line_items[] = array("sku"=>$sku, "quantity"=>$qty);
        }
    }

    $data = array(
        'key' => $settings['bigpost_key'],
        'product_ids' => json_encode($line_items),
        'postcode' => $order_data['shipping']['postcode'],
        'suburb' => $order_data['shipping']['city'],
        'carrier_id' => $CarrierId,
        'depot_id' => '0',
        'warehouse_id' => '29',
        'shipping_type' => $ShippingType,
        'authority_to_leave' => $LeaveAuth,
        'status' => '0',
        'email' => $order_data['billing']['email'],
        'first_name' => $order_data['shipping']['first_name'],
        'last_name' => $order_data['shipping']['last_name'],
        'address' => $order_data['shipping']['address_1'].", ".$order_data['shipping']['address_2'].", ".$order_data['shipping']['city']." ".$order_data['shipping']['state']." ".$order_data['shipping']['postcode'],
        'phone' => $order_data['billing']['phone'],
        'company' => null,
        'order_id' => $order_id,
    );

    update_post_meta($order_id, 'cis_payload', print_r($data, true));

    //post above payload to  cis.bigpost.com.au/create_order
    $ch = curl_init('https://cis.bigpost.com.au/create_order');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $response = curl_exec($ch);
    curl_close($ch);
    
    //echo $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    return $response;

}

