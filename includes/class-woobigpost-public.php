<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Public Class
 *
 * Manage public side functionality
 */
class Woo_BigPost_Public {
    private $settings;
    public function __construct() {
        $this->settings = woobigpost_get_plugin_settings();
        //add_filter( 'woocommerce_checkout_fields', array($this, 'woobigpost_update_checkout_city_fields'),  20 );

        add_action( 'wp_ajax_woobigpost_get_suburb_list', array($this, 'woobigpost_get_suburb_list_ajax') );
        add_action( 'wp_ajax_nopriv_woobigpost_get_suburb_list', array($this, 'woobigpost_get_suburb_list_ajax') );

        add_action( 'wp_ajax_woobigpost_set_shipping_suburb', array($this, 'woobigpost_set_shipping_suburb_ajax') );
        add_action( 'wp_ajax_nopriv_woobigpost_set_shipping_suburb', array($this, 'woobigpost_set_shipping_suburb_ajax') );

        add_action( 'wp_ajax_woobigpost_find_shipping_costs', array($this, 'woobigpost_find_shipping_costs_ajax') );
        add_action( 'wp_ajax_nopriv_woobigpost_find_shipping_costs', array($this, 'woobigpost_find_shipping_costs_ajax') );

        add_action( 'wp_ajax_woobigpost_update_cart_shipping', array($this, 'woobigpost_update_cart_shipping_ajax') );
        add_action( 'wp_ajax_nopriv_woobigpost_update_cart_shipping', array($this, 'woobigpost_update_cart_shipping_ajax') );

        add_action( 'wp_ajax_woobigpost_get_checkout_suburbs', array($this, 'woobigpost_get_checkout_suburbs_ajax') );
        add_action( 'wp_ajax_nopriv_woobigpost_get_checkout_suburbs', array($this, 'woobigpost_get_checkout_suburbs_ajax') );

        add_action( 'wp_ajax_woobigpost_get_checkout_depot', array($this, 'woobigpost_get_checkout_depot_ajax') );
        add_action( 'wp_ajax_nopriv_woobigpost_get_checkout_depot', array($this, 'woobigpost_get_checkout_depot_ajax') );

        add_action( 'wp_ajax_woobigpost_get_shipping_status', array($this, 'woobigpost_get_shipping_status_ajax') );
        add_action( 'wp_ajax_nopriv_woobigpost_get_shipping_status', array($this, 'woobigpost_get_shipping_status_ajax') );

        add_action( 'wp_ajax_woobigpost_check_session', array($this, 'woobigpost_check_session_ajax') );
        add_action( 'wp_ajax_nopriv_woobigpost_check_session', array($this, 'woobigpost_check_session_ajax') );

        add_action('woocommerce_checkout_process', array($this, 'my_custom_checkout_field_process') );

        // Display Quick Quote on product page
        if($this->settings['product_page'] != 'hide'){
            $product_page_position = isset($this->settings['product_position'])? $this->settings['product_position'] : 'woocommerce_after_single_product_summary';
            add_action($product_page_position, array($this,'woobigpost_shipping_charges_for_single_product') );
        }


        // Display Quick Quote On cart page
        if(get_option( 'woocommerce_enable_shipping_calc' ) == 'yes'){
            $cart_page_position = isset($this->settings['cart_position'])? $this->settings['cart_position'] : 'woocommerce_cart_totals_before_shipping';
            add_action('woocommerce_cart_totals_before_shipping', array($this,'woobigpost_shipping_charges_for_cart' ));
        } else {
            add_filter( 'woocommerce_cart_ready_to_calc_shipping', array($this,'disable_shipping_calc_on_cart'), 99 );
        }

        // Display Quick Quote On checkout page
        $checkout_page_position = isset($this->settings['checkout_position'])? $this->settings['checkout_position'] : 'woocommerce_checkout_after_customer_details';
        add_action( 'woocommerce_review_order_before_shipping' , array($this,'woobigpost_shipping_charges_for_checkout'));


        // add the action
        add_action( 'woocommerce_checkout_create_order', array($this,'bigpost_alter_shipping'), 10, 2 );

        //add_action( 'woocommerce_cart_updated', array($this,'updateQuickQuote'), 10, 0 );
        add_action( 'wp_ajax_woobigpost_set_fields_on_session', array($this, 'woobigpost_set_fields_on_session') );
        add_action( 'wp_ajax_nopriv_woobigpost_set_fields_on_session', array($this, 'woobigpost_set_fields_on_session') );


        add_filter( 'woocommerce_shipping_calculator_enable_state', array($this,'filter_shipping_state') );
        add_filter( 'woocommerce_shipping_calculator_enable_city', array($this,'filter_shipping_city') );
        add_filter( 'woocommerce_shipping_calculator_enable_postcode', array($this,'filter_shipping_postcode') );

        add_action( 'woocommerce_before_add_to_cart_quantity', array($this,'woo_bigpost_create_quick_quote_html') );

        add_filter( 'woocommerce_no_shipping_available_html', array($this,'woobigpost_no_shipping_message'), 10,1 );
        add_filter( 'woocommerce_cart_no_shipping_available_html', array($this,'woobigpost_no_shipping_message'), 10,1 );
        add_filter( 'woocommerce_cart_shipping_method_full_label', array($this,'free_shipping_label_filter'), 10, 2 );

        add_action( 'woocommerce_after_order_notes', array($this,'add_bigpost_checkout_hidden_field') );
        //reorder postcode field on checkout
        if($this->settings['postcode_field_order'] == 'yes'){
            add_filter( 'woocommerce_default_address_fields', array($this,'postcode_up'),99 );
        }
        
        add_action( 'woocommerce_checkout_update_order_meta', array($this,'save_locality_id'));
    }
    
    public function save_locality_id($order_id){
        $LocalityId = $_POST['woo_bp_suburb'];
        
        if ( ! empty( $LocalityId ) ){
            update_post_meta( $order_id, '_locality_id', $LocalityId );
        }
    }
    /**
     *
     */
    public function woobigpost_update_checkout_city_fields( $fields ) {

        $_POST['billing_postcode']  = WC()->session->get('woobigpost-topostcode');
        $_POST['shipping_postcode'] = WC()->session->get('woobigpost-topostcode');

        $fields['billing']['billing_postcode']['placeholder']   = 'Postcode';
        $fields['billing']['billing_postcode']['default']     = WC()->session->get( 'woobigpost-topostcode' );
        $fields['billing']['billing_city']['placeholder']     = 'Suburb';
        $fields['billing']['billing_city']['default']     = WC()->session->get('woobigpost-tosuburb');
        $fields['billing']['billing_state']['default']      = WC()->session->get('woobigpost-tostate');

        /* Autofill Shipping */
        $fields['shipping']['shipping_postcode']['placeholder'] = 'Postcode';
        $fields['shipping']['shipping_postcode']['default']   = WC()->session->get('woobigpost-topostcode');
        $fields['shipping']['shipping_city']['placeholder']   = 'Suburb';
        $fields['shipping']['shipping_city']['default']     = WC()->session->get('woobigpost-tosuburb');
        $fields['shipping']['shipping_state']['default']    = WC()->session->get('woobigpost-tostate');

        return $fields;
    }

    /**
     * Get suburb list by ajax
     */
    public function woobigpost_get_suburb_list_ajax() {

        $type = isset( $_POST['type'] ) ? sanitize_text_field($_POST['type']) : 'from';
        $suburb = isset( $_POST['value'] ) ? sanitize_text_field($_POST['value']) : '';

        $html = woobigpost_get_suburb_list( $suburb, $type );

        echo $html;
        exit;
    }

    /**
     * Set shipping suburb
     */
    public function woobigpost_set_shipping_suburb_ajax() {

        WC()->session->set_customer_session_cookie( true );

        if( isset($_POST['tosuburb']) )   WC()->session->set( 'woobigpost-tosuburb', sanitize_text_field($_POST['tosuburb']) );
        if( isset($_POST['tostate']) )    WC()->session->set( 'woobigpost-tostate', sanitize_text_field($_POST['tostate']) );
        if( isset($_POST['topostcode']) )   WC()->session->set( 'woobigpost-topostcode', sanitize_text_field($_POST['topostcode']) );
        if( isset($_POST['topostcodeid']) ) WC()->session->set( 'woobigpost-topostcodeid', sanitize_text_field($_POST['topostcodeid']) );

        $depotId = woobigpost_get_depot();
        if( $depotId ) WC()->session->set( 'woobigpost-todepotid', $depotId );

        echo $depotId;
        exit;
    }

    /**
     * Find shipping rates
     */
    public function woobigpost_find_shipping_costs_ajax() {
        WC()->session->set_customer_session_cookie( true );

        global $woocommerce;

        $packages =  $woocommerce->cart->get_shipping_packages();
        foreach( $packages as $package_key => $package ) {
            $session_key  = 'shipping_for_package_'.$package_key;
            $stored_rates = WC()->session->__unset( $session_key );
        }

        WC()->session->set( 'Shipping_Method_Selected',  array() );
        WC()->session->set( 'woobigpost-buyer_is_business', sanitize_text_field($_POST['buyer_is_business']) );

        if(isset($_POST['leave_auth'])){
            WC()->session->set( 'woobigpost-leave_authority', sanitize_text_field($_POST['leave_auth']) );
        }

        if(isset($_POST['has_forklift'])){
            WC()->session->set( 'woobigpost-has_forklift', sanitize_text_field($_POST['has_forklift']) );
        }

        if(isset($_POST['have_confirm'])){
            WC()->session->set( 'woobigpost-have_confirm', sanitize_text_field($_POST['have_confirm']) );
        }

        $tosuburb = !empty($_POST['tosuburb']) ? sanitize_text_field($_POST['tosuburb']) : WC()->session->get('woobigpost-tosuburb');
        $tostate  = !empty($_POST['tostate']) ? sanitize_text_field($_POST['tostate']) : WC()->session->get('woobigpost-tostate');
        $todepotid  = !empty($_POST['todepotid']) ? sanitize_text_field($_POST['todepotid']) : WC()->session->get('woobigpost-todepotid');
        $topostcode = !empty($_POST['topostcode']) ? sanitize_text_field($_POST['topostcode']) : WC()->session->get('woobigpost-topostcode');
        $topostcodeid = !empty($_POST['topostcodeid']) ? sanitize_text_field($_POST['topostcodeid']) : WC()->session->get('woobigpost-topostcodeid');

        WC()->session->set( 'woobigpost-tosuburb', $tosuburb );
        WC()->session->set( 'woobigpost-tostate', $tostate );
        WC()->session->set( 'woobigpost-topostcode', $topostcode );
        WC()->session->set( 'woobigpost-topostcodeid', $topostcodeid );
        WC()->session->set( 'woobigpost-todepotid', $todepotid );

        WC()->customer->set_shipping_state( WC()->session->get( 'woobigpost-tostate'));
        WC()->customer->set_shipping_city( WC()->session->get( 'woobigpost-tosuburb'));
        WC()->customer->set_shipping_postcode( WC()->session->get( 'woobigpost-topostcode'));
        WC()->customer->set_calculated_shipping( true );

        // Plugin all settings
        $settings = woobigpost_get_plugin_settings();
        $warehouses = $settings['bigpost_warehouse_locations'];
        $product_warehouses = array();

        $can_consolidated = $settings['is_advanced_mode'];

        $jobtype = "";

        if( $_POST['page_is'] == 'product' ) {
          $_product =  wc_get_product(sanitize_text_field($_POST['productId']) );
          $product_data = $_product->get_data();
          $product_type = $_product->get_type();

          if($product_data['parent_id'] > 0){ //it is a variation
            $parent_product =  $product_data['parent_id'];
       $parent_id =  $product_data['parent_id'];
          } else {
            $parent_product = 0;
      $parent_id =  $_POST['productId'];
          }

          $use_admin_shipping = get_post_meta( $parent_id, '_use_admin_setting', true );

          $shipping_types = $settings['shipping_types'];
          if( $use_admin_shipping != 'Yes' && $use_admin_shipping != '' ) {

            $product_stypes = get_post_meta($parent_id, '_shipping_type', true);
            $product_stypes = json_decode( $product_stypes );

            $shipping_types = array_merge($shipping_types, $product_stypes);
            foreach( $shipping_types as $key => $stype ) {
              if( ! in_array($stype, $product_stypes) ) {
                unset( $shipping_types[$key] );
              }
            }
          }
          //print_r($product_stypes);
          
          if(!in_array("DEPOT", $shipping_types) ){
            $jobtype = 2;
          }

          $p_settings = new Woo_BigPost_Product_Settings(sanitize_text_field($_POST['productId']), $settings,$parent_product);

          $_POST['parent_product'] = $parent_product;

          $productid    = isset( $_POST['productId'] ) ? sanitize_text_field($_POST['productId']) : '';
          $cartonsRows  = $p_settings->product_settings['_carton_items'];
          $cartonsRows  = json_decode( $cartonsRows );
          $product_quantity = isset($_POST['product_quantity']) ? sanitize_text_field($_POST['product_quantity']): 1;

          if( !empty($cartonsRows) ) {
            $product_location   = $p_settings->product_settings['_product_locations'];
            //$warehouses = woobigpost_get_selected_warehouse($product_location);
            $product_warehouses[$_POST['productId']] = $product_location;

            foreach( $cartonsRows as $row ) {

              if( $can_consolidated == 'Yes' ) {
                $can_consolidated = $row->consolidated;
              }

              $data_items[] = array(
                'Description'   => woobigpost_trim_text(get_the_title( $productid ), 50),
                'Length'      => floatval( $row->length ),
                'Width'       => floatval( $row->width ),
                'Height'      => floatval( $row->height ),
                'Weight'      => floatval( $row->weight ),
                'ItemType'      => intval( $row->packaging_type ),
                'isMHP'      => ( $row->mhp == 'Yes' ) ? true : false,
                'Quantity'      => $product_quantity,
                'Consolidatable'  => ( $can_consolidated == 'Yes' ) ? true : false
              );
            }
          } else {
            echo "Shipping settings are not saved for this product";
            wp_die();
          }

          // if composite
          if ($product_type == 'composite' && isset($_POST['components']) && !empty($_POST['components'])) {
            // add component to data_items
            $selected_components = $_POST['components']['components'];

            foreach($selected_components as $comp) {
              if($comp != "") {
                $p_settings = new Woo_BigPost_Product_Settings(sanitize_text_field($comp), $settings);

                $show_plugin_value  = $p_settings->product_settings['show_plugin']; //if initial setup,show plugin on product page by default

                if($show_plugin_value == "0"){
                  continue; //exclude products where bigpost quote box is hidden
                }

                $cartonsRows  = $p_settings->product_settings['_carton_items'];
                $cartonsRows  = json_decode( $cartonsRows );
                $product_quantity = $product_quantity;

                if( !empty($cartonsRows) ) {
                  $product_location   = $p_settings->product_settings['_product_locations'];
                  $product_warehouses[sanitize_text_field($comp)] = $product_location;

                  foreach( $cartonsRows as $row ) {

                    if( $can_consolidated == 'Yes' ) {
                      $can_consolidated = $row->consolidated;
                    }

                    $data_items[] = array(
                      'Description'   => woobigpost_trim_text(get_the_title( $comp ), 50),
                      'Length'      => floatval( $row->length ),
                      'Width'       => floatval( $row->width ),
                      'Height'      => floatval( $row->height ),
                      'Weight'      => floatval( $row->weight ),
                      'ItemType'      => intval( $row->packaging_type ),
                      'isMHP'      => ( $row->mhp == 'Yes' ) ? true : false,
                      'Quantity'      => $product_quantity,
                      'Consolidatable'  => ( $can_consolidated == 'Yes' ) ? true : false
                    );
                  }
                }
              }
            }
          }

          $matched_warehouse = array();

          if(!empty($product_warehouses)) {
            $i = 0;
            foreach($product_warehouses as $w){
              $item_location = is_array($w) ? $w : array();

              $matched_warehouse = empty($matched_warehouse) && $i == 0 ? $item_location : array_intersect($matched_warehouse,$item_location);
              $i++;
            }
          }

          $warehouses =  woobigpost_get_selected_warehouse($matched_warehouse);

        // Cart or checkout page
        } else {

            global $woocommerce;
            $data_items = array();

            $items = $woocommerce->cart->get_cart();

            $matched_warehouse = array();
            $mixed_cart = [];
            $free_shipping_disabled = [];

            foreach( $items as $item ) {
                $parent_product = $item['product_id'];
                $product_id = isset($item['product_id'] ) ? $item['product_id']: '';

                if(isset($item['variation_id']) && $item['variation_id'] > 0){
                    $product_id  = $item['variation_id'];
                }
                $p_settings = new Woo_BigPost_Product_Settings($product_id, $settings,$parent_product);

                $show_plugin_value  = $p_settings->product_settings['show_plugin']; //if initial setup,show plugin on product page by default

                if($show_plugin_value == "0"){
                    continue; //exclude products where bigpost quote box is hidden
                }

                $cartonsRows  = $p_settings->product_settings['_carton_items'];
                $cartonsRows  = json_decode( $cartonsRows );

                $item_location = $p_settings->product_settings['_product_locations'];
                $matched_warehouse = empty($matched_warehouse)? $item_location : array_intersect($matched_warehouse,$item_location);

                $free_enabled = $p_settings->product_settings['_free_shipping'];

                $mixed_cart[] = $free_enabled;

                if($free_enabled != '1'){
                    $free_shipping_disabled[] = $item;
                }

                if( !empty($cartonsRows) ) {
                    foreach( $cartonsRows as $row ) {

                        if( $can_consolidated == 'Yes' && !empty($row->consolidated) ) {
                            $can_consolidated = $row->consolidated;
                        }

                        $data_items[] = array(
                            'Description'   => substr(get_the_title( $product_id ), 0, 50),
                            'Length'      => floatval( $row->length ),
                            'Width'       => floatval( $row->width ),
                            'Height'      => floatval( $row->height ),
                            'Weight'      => floatval( $row->weight ),
                            'ItemType'      => intval( $row->packaging_type ),
                            'isMHP'      => ( $row->mhp == 'Yes' ) ? true : false,
                            'Quantity'      => $item['quantity'],
                            'Consolidatable'  => ( $can_consolidated == 'Yes' ) ? true : false
                        );
                    }
                }
            }

            $warehouses =  woobigpost_get_selected_warehouse($matched_warehouse);
            $mixed = false;
            if(in_array("0",$mixed_cart) || in_array("",$mixed_cart)){
                $mixed = true;
            }

            $_POST['mixed'] = $mixed;

            WC()->session->set( 'free_disabled_items', $free_shipping_disabled );

            if( empty($data_items) ) {
                WC()->session->set( 'WooBigPost_shippingPrices_Display','Shipping settings are not saved for some of products in the cart.');
                echo "Shipping settings are not saved for some of products in the cart";
                wp_die();
            }

            if(empty($matched_warehouse)){
                WC()->session->set( 'WooBigPost_shippingPrices_Display',$settings['no_available_shipping']);

                echo $settings['no_available_shipping'];
                wp_die();
            }
        }

        // Ghether all values
        $has_forklift = ( isset($_POST['has_forklift']) && $_POST['has_forklift'] == '1' ) ? true : false;
        
        if(!isset($_POST['leave_auth'])){
          $auth_leave = ($settings['authority_option'] == "Yes")?true:false;
        }else{
          $auth_leave   = ( $_POST['leave_auth'] == '1' ) ? true : false;
        }
        
        //$jobType    = ( isset($_POST['buyer_is_business']) && $_POST['buyer_is_business'] == '0' ) ? "" : 2;

        $Address = $tosuburb . ' ' . $tostate;

        $current_user = wp_get_current_user();

        $delivery_options_arr = array();
        $resources = array();
        $api_data = array();

        if(!empty($warehouses)){
            foreach($warehouses as $location){

                $API_data = array(
                    'JobType'       => $jobtype, // DEPOT = 1, Business = 2, HDS = 3, blank to Depot + home or business
                    'DepotId'       => $todepotid,
                    'BuyerIsBusiness'   => ( $_POST['buyer_is_business'] ) ? true : false,
                    'BuyerHasForklift'  => ( $has_forklift ) ? true : false,
                    'ReturnAuthorityToLeaveOptions' => ( $auth_leave ) ? true : false,
                    'PickupLocation'  => array(
                        'Name'    => "WooCommerce - Big Post Shipping",
                        'Address'   => $location['from_address'],
                        "LocalityId"  => $location['from_postcode_id'],
                        'Locality'  => array(
                            "Id"    => $location['from_postcode_id'],
                            "Suburb"  => $location['from_suburb_addr'],
                            "Postcode"  => $location['from_post_code'],
                            "State"   => $location['from_state']
                        )
                    ),
                    'BuyerLocation'   => array(
                        'Name'    => isset($current_user->user_nicename) ? $current_user->user_nicename : "Guest User",
                        'Address'   => $Address,
                        "LocalityId"  => $topostcodeid,
                        'Locality'  => array(
                            "Id"    => $topostcodeid,
                            "Suburb"  => $tosuburb,
                            "Postcode"  => $topostcode,
                            "State"   => $tostate
                        )
                    ),
                    'Items' => $data_items
                );

                /*if( ! $buyer_is_business ) {
                    $API_data['DepotId'] = $todepotid;
                }*/
                $endpoint = '/api/getquote';
                /*$result = woobigpost_send_api_request( $endpoint, $API_data, 'POST' );
                $decoded_result = json_decode( $result );
                $delivery_options_arr[] = $decoded_result;*/

                $resources[] = $endpoint;
                $api_data[] = $API_data;

            }
        }

        $guzzle = GuzzleMultiTransfer::getInstance();
        $delivery_options_arr = $guzzle->post_request($resources, $api_data);
        $result = woobigpost_get_cheapest_delivery_opt($delivery_options_arr,sanitize_text_field($_POST['page_is']), $_POST);

        //for free shipping
        if($_POST['page_is'] != 'product'){
            $process_free = woobigpost_process_free_shipping($free_shipping_disabled, $items);
            if($process_free == true) {
                $free_shipping_check = new Woo_BigPost_Free_Shipping_Check();
                $new_rates = $free_shipping_check->create_new_shipping_rates($result,sanitize_text_field($_POST['mixed']));

                $_POST['new_rates'] = $new_rates;
                if(isset($new_rates['Results']) && !empty($new_rates['Results'])){
                    $result = $new_rates['Results'];
                }
            }

        }

        $shippingPrices = $settings['no_available_shipping'];
        if( !empty($result) ) {
            $shippingPrices = woobigpost_get_shipping_prices( $result, sanitize_text_field($_POST['page_is']), $_POST);
        }

        $shippingPrices = empty($shippingPrices) ? $settings['no_available_shipping'] : $shippingPrices;

        //setcookie('shippingPrices',$shippingPrices,'/');
        WC()->session->set( 'WooBigPost_shippingPrices_Display',$shippingPrices);
        echo $shippingPrices;
        exit;
    }

    /**
     *
     */
    public function woobigpost_update_cart_shipping_ajax() {

        global $woocommerce;

        WC()->session->set_customer_session_cookie( true );

        $shipping_data = '';
        $output = array('status' => 'error');

        if( !empty($_POST['Total']) ) {

            $shippingType = isset( $_POST['ShippingType'] ) ? sanitize_text_field($_POST['ShippingType']) : '';

            //Overwrites the HOME title for HDS if it is a business customer, but a HDS delivery
            $title = woobigpost_getLabel($shippingType, WC()->session->get('woobigpost-buyer_is_business'), WC()->session->get('woobigpost-has_forklift'));
            $label = $title['label'];

            if( $label != '' ) {
                $shipping_data .= '<tr class="shipping"> <th>' . $label . '</th> <td data-title="Shipping"> $' . number_format(sanitize_text_field($_POST['Total']), 2) . ' </td> </tr>';
                $shipping_data .= '<tr class="order-total"> <th>Total</th> <td data-title="Total"><strong><span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">$</span>' . number_format(($woocommerce->cart->subtotal + $_POST['Total'] ), 2) . '</span></strong> </td> </tr>';
                $output = array( 'status' => 'success', 'options' => $shipping_data );
            } else {
                $output = array( 'status' => 'error', 'options' => $shipping_data );
            }
        }

        //WC()->session->set_customer_session_cookie( true );

        WC()->session->__unset( "WooBigPost_Shipping_Method_Selected" );

        WC()->session->set( 'WooBigPost_Shipping_Method_Selected', array(
            'CarrierName'   => stripcslashes( $_POST['carriername'] ),
            'CarrierId'   => sanitize_text_field($_POST['carrierid']),
            'Charge'    => sanitize_text_field($_POST['charge']),
            'Tax'       => sanitize_text_field($_POST['tax']),
            'Total'     => sanitize_text_field($_POST['Total']),
            'ShippingType'  => $shippingType,
            'LeaveAuth'   => sanitize_text_field($_POST['authority']),
        ) );

        WC()->cart->calculate_shipping();

        echo json_encode( $output );
        exit;
    }

    /**
     *
     */
    public function woobigpost_get_checkout_suburbs_ajax() {

        $suburbname = sanitize_text_field( $_GET['term'] );
        $params   = urlencode( $suburbname );

        $endpoint = "/api/suburbs?q=".htmlentities( $params );

        $suburbsresult  = woobigpost_send_api_request( $endpoint, '', 'GET' );

        echo $suburbsresult;
        exit;
    }

    /**
     *
     */
    public function woobigpost_get_checkout_depot_ajax() {

        WC()->session->set_customer_session_cookie( true );

        WC()->session->set( 'woobigpost-tosuburb', sanitize_text_field($_POST['ToSuburb'] ));
        WC()->session->set( 'woobigpost-tostate', sanitize_text_field($_POST['ToSuburb']) );
        WC()->session->set( 'woobigpost-topostcode', sanitize_text_field($_POST['ToPostcode']) );
        WC()->session->set( 'woobigpost-topostcodeid', sanitize_text_field($_POST['ToPostcodeId']) );

        /* get nearest depot id by suburb postcode */
        $params     = "?p=".urlencode(WC()->session->get('topostcode'))."&s=".urlencode(WC()->session->get( 'tosuburb'));
        $endpoint     = "/api/suburbs?q=".htmlentities( $params );
        $suburbsresult  = woobigpost_send_api_request( $endpoint, '', 'GET' );

        // unset session values for shipping option and selected option
        WC()->session->__unset( 'Shipping_Method_Selected' );
        WC()->session->__unset( 'Shipping_Method_Data' );

        $result = json_decode( $suburbsresult );
        if( count($result) ) {
            WC()->session->set( 'woobigpost-todepotid', $result[0]->DepotId );
            echo WC()->session->get( 'woobigpost-todepotid' );
        }
        exit;
    }

    /**
     * Display Quick Quote On Single Product page
     */
    public function woobigpost_shipping_charges_for_single_product() {
        global $product;

        $has_empty_dimension = false;

        if($product->get_type() == 'simple'){
            $carton_items = get_post_meta( $product->get_id(), '_carton_items', true );
            $cartons__ = json_decode($carton_items);

            if( !empty($carton_items) && !empty($cartons__)) {
                foreach($cartons__  as $item ) {
                    $length = floatval($item->length);
                    $width =  floatval($item->width);
                    $height =  floatval($item->height);
                    $weight =  floatval($item->weight);

                    if($length == 0 || $width == 0 || $height == 0 || $weight == 0){
                        $has_empty_dimension = true;
                        break;
                    }
                }
            }
        }

        if($product->get_type() == 'variable'){
            $variations = $product->get_available_variations();
            if(!empty($variations)){
                $carton_items = array();
                foreach($variations as $variation){
                    $variationID = $variation['variation_id'];
                    $carton_items = get_post_meta( $variationID, '_carton_items', true );
                    $cartons__ = json_decode($carton_items);

                    if( !empty($carton_items) && !empty($cartons__)) {
                        foreach($cartons__  as $item ) {
                            $length = floatval($item->length);
                            $width =  floatval($item->width);
                            $height =  floatval($item->height);
                            $weight =  floatval($item->weight);

                            if($length == 0 || $width ==  0 || $height == 0 || $weight == 0){
                                $has_empty_dimension = true;
                                break;
                            }
                        }
                    }
                }
            }
        }

        $plugin_setup_check = woobigpost_get_plugin_settings();
        $show_plugin        = get_post_meta($product->get_id(), '_show_plugin', true );
        $show_plugin_value  = $show_plugin == "" && $this->settings['product_page'] != 'hide' ? '1' : $show_plugin; //if initial setup,show plugin on product page by default

        if(isset($plugin_setup_check['plugin_setup']['api_creds']) && $plugin_setup_check['plugin_setup']['api_creds'] == 'true' && isset($plugin_setup_check['plugin_setup']['pickup_locations']) && $plugin_setup_check['plugin_setup']['pickup_locations'] == 'true') {
            if($has_empty_dimension === false && $show_plugin_value == '1'){ //check if we need to show based on product setting
                echo woobigpost_shipping_quote_form_html();
            }
        }

        if($has_empty_dimension === true && $show_plugin_value == '1'){
            echo "<p>Big Post Error: No product quote widget shown as there is insufficient box data stored on this item.</p>";
        }
    }

    public function woobigpost_shipping_charges_for_checkout() {
        global $woocommerce;
        $plugin_setup_check = woobigpost_get_plugin_settings();
        $items = $woocommerce->cart->get_cart();
        $disabled_items = count_disabled_items($items,$plugin_setup_check);

        if(isset($plugin_setup_check['plugin_setup']['api_creds']) && $plugin_setup_check['plugin_setup']['api_creds'] == 'true' && isset($plugin_setup_check['plugin_setup']['pickup_locations']) && $plugin_setup_check['plugin_setup']['pickup_locations'] == 'true') {
            if(check_if_active() == true && $disabled_items < count($items)){
                echo woobigpost_shipping_quote_form_html('cart');
            }
        }
    }

    public function woobigpost_shipping_charges_for_cart() {
        global $woocommerce;
        $plugin_setup_check = woobigpost_get_plugin_settings();
        $items = $woocommerce->cart->get_cart();
        $disabled_items = count_disabled_items($items,$plugin_setup_check);

        if(isset($plugin_setup_check['plugin_setup']['api_creds']) && $plugin_setup_check['plugin_setup']['api_creds'] == 'true' && isset($plugin_setup_check['plugin_setup']['pickup_locations']) && $plugin_setup_check['plugin_setup']['pickup_locations'] == 'true') {
            if(check_if_active() == true && $disabled_items < count($items)){
                echo woobigpost_shipping_quote_form_html('cart');
            }

        }
    }


    /**
     *
     */
    function woobigpost_get_shipping_status_ajax() {

        $consignment_number = sanitize_text_field( $_POST['consignment_number']);

        if( isset($consignment_number) && !empty($consignment_number) ) {
            global $wpdb;

            $table_name   = $wpdb->prefix . 'postmeta';
            $result = $wpdb->get_row( "SELECT post_id FROM $table_name WHERE meta_value ='$consignment_number'" );

            if( !empty($result) ){

                $data_to_send   =  array(get_post_meta($result->post_id, 'order_job_id', true ));

                $endpoint = "/api/getstatus";
                $server_output  = woobigpost_send_api_request( $endpoint, $data_to_send, 'POST' );

                if(is_object(json_decode($server_output))) {
                    $result = json_decode($server_output);
                    if(empty($result->Errors)) {
                        $orderStatus = $result->Object[0]->Status;
                        echo $orderStatus;
                    } else {
                        echo 'Enter valid consignment number, try again';
                    }
                } else {
                    echo 'Some internal error occurred, Please try again';
                }
            } else {
                echo "Invalid consignment number";
            }
        } else {
            echo "Consignment number is required";
        }
        wp_die();
    }

    /**
     *
     */
    function woobigpost_check_session_ajax() {
        
        if($this->settings['order_sync_only']){
            echo 1;
            wp_die();
        }
        
        $topostcodeid = WC()->session->get('woobigpost-topostcodeid');
        
        if($_POST['postcodeid'] != $topostcodeid){
            echo 0;
        }else{
            echo 1;
        }
        
        wp_die();
        
    }
    
    /**
     * @param $order
     * @param $data
     * @return mixed
     */
    public function bigpost_alter_shipping($order,$data){

        foreach( $order->get_items('shipping') as $key => $val ){
            $ShippingType = $val->get_meta('ShippingType');
            $BuyerLocality = $val->get_meta('BuyerLocality');
        }

        if($ShippingType == 'DEPOT'){
            $address = array(
                'address_1'  => $BuyerLocality['DepotAddress']['company'],
                'address_2'  => $BuyerLocality['DepotAddress']['address_1'],
                'city'       => $BuyerLocality['DepotAddress']['city'],
                'state'      => $BuyerLocality['DepotAddress']['state'],
                'postcode'   => $BuyerLocality['DepotAddress']['postcode'],
                'country'    => $data['shipping_country']
            );

            $order->set_address( $address, 'shipping' );
        }

        return $order;
    }

    public function disable_shipping_calc_on_cart($show_shipping){
        if( is_cart() ) {
            return false;
        }
        return $show_shipping;
    }

    /**
     * @param $true
     * @return mixed
     */
    public function filter_shipping_state( $true ){
        WC()->customer->set_shipping_state( WC()->session->get( 'woobigpost-tostate'));
        return $true;
    }

    /**
     * @param $true
     * @return mixed
     */
    public function filter_shipping_city( $true ){
        WC()->customer->set_shipping_city( WC()->session->get( 'woobigpost-tosuburb'));
        return $true;
    }

    /**
     * @param $true
     * @return mixed
     */
    public function filter_shipping_postcode( $true ){
        WC()->customer->set_shipping_postcode( WC()->session->get( 'woobigpost-topostcode'));
        return $true;
    }

    /**
     *
     */
    public function woo_bigpost_create_quick_quote_html(){
        global $product;
        if ( $product->is_type('variable') ) {
            ob_start();
            require_once( WOO_BIGPOST_DIR . '/includes/template-qoute-form/variable_product_data_html.php' );
            $html = ob_get_clean();
            echo $html;
        }
    }

    /**
     * @param $message
     * @return mixed
     */
    public function woobigpost_no_shipping_message( $message ) {
        $post_data = WC()->session->get('woobigpost-post_data');
        if (!empty($post_data)) {
          return $this->settings['no_available_shipping'];
        }
    }

    public function woobigpost_set_fields_on_session(){
        global $woocommerce;
        $packages =  $woocommerce->cart->get_shipping_packages();
        foreach( $packages as $package_key => $package ) {
            $session_key  = 'shipping_for_package_'.$package_key;
            $stored_rates = WC()->session->__unset( $session_key );
        }

        WC()->session->set( 'Shipping_Method_Selected',  array() );
        WC()->session->set( 'woobigpost-buyer_is_business', sanitize_text_field($_POST['buyer_is_business']) );

        $post_data = array();

        $post_data['buyer_is_business'] = sanitize_text_field($_POST['buyer_is_business']);
        if(isset($_POST['have_confirm'])){
            WC()->session->set( 'woobigpost-have_confirm', sanitize_text_field($_POST['have_confirm']) );
            $post_data['have_confirm'] = sanitize_text_field($_POST['have_confirm']);
        }

        if(isset($_POST['leave_auth'])){
            WC()->session->set( 'woobigpost-leave_authority', sanitize_text_field($_POST['leave_auth']) );
            $post_data['leave_auth'] = sanitize_text_field($_POST['leave_auth']);
        }

        if(isset($_POST['has_forklift'])){
            WC()->session->set( 'woobigpost-has_forklift', sanitize_text_field($_POST['has_forklift']) );
            $post_data['has_forklift'] = sanitize_text_field($_POST['has_forklift']);
        }

        $tosuburb = !empty($_POST['tosuburb']) ? sanitize_text_field($_POST['tosuburb']) : WC()->session->get('woobigpost-tosuburb');
        $tostate  = !empty($_POST['tostate']) ? sanitize_text_field($_POST['tostate']) : WC()->session->get('woobigpost-tostate');
        $todepotid  = !empty($_POST['todepotid']) ? sanitize_text_field($_POST['todepotid']) : WC()->session->get('woobigpost-todepotid');
        $topostcode = !empty($_POST['topostcode']) ? sanitize_text_field($_POST['topostcode']) : WC()->session->get('woobigpost-topostcode');
        $topostcodeid = !empty($_POST['topostcodeid']) ? sanitize_text_field($_POST['topostcodeid']) : WC()->session->get('woobigpost-topostcodeid');

        WC()->session->set( 'woobigpost-post_data', $post_data );
        WC()->session->set( 'woobigpost-tosuburb', $tosuburb );
        WC()->session->set( 'woobigpost-tostate', $tostate );
        WC()->session->set( 'woobigpost-topostcode', $topostcode );
        WC()->session->set( 'woobigpost-topostcodeid', $topostcodeid );
        WC()->session->set( 'woobigpost-todepotid', $todepotid );

        WC()->customer->set_shipping_state( WC()->session->get( 'woobigpost-tostate'));
        WC()->customer->set_shipping_city( WC()->session->get( 'woobigpost-tosuburb'));
        WC()->customer->set_shipping_postcode( WC()->session->get( 'woobigpost-topostcode'));
        WC()->customer->set_calculated_shipping( true );

    }

    public function free_shipping_label_filter($label,$method){
        if($method->cost == '0.00'){
            $label = $method->get_label().': <strong>FREE</strong>';
        }

        return $label;
    }

    public function postcode_up( $fields ) {
        $fields['postcode']['priority'] = 60;
        $fields['city']['priority'] = 70;
        $fields['state']['priority'] = 80;
        return $fields;
    }

    public function add_bigpost_checkout_hidden_field( $checkout ) {
        global $woocommerce;

        $items = $woocommerce->cart->get_cart();
        $has_bigpost = false;
        foreach( $items as $item ) {
          $product_id = $item['product_id'];

          $show_plugin_value  = get_post_meta($product_id, '_show_plugin', true);

          if($show_plugin_value == "0"){

          }else{
            $has_bigpost = true;
          }
        }

        if($has_bigpost){
          echo '<div id="user_link_hidden_checkout_field">
                  <input type="hidden" class="input-hidden" name="billing_state" id="hidden-billing_state" value="">
          </div>';

          echo '<div id="user_link_hidden_checkout_field">
                  <input type="hidden" class="input-hidden" name="shipping_state" id="hidden-shipping_state" value="">
          </div>';
        }
    }

    public function my_custom_checkout_field_process() {
      global $woocommerce;
    
      if($this->settings['limit_phone'] == 'yes'){
        if ( ! (preg_match('/^[0-9]{10}$/D', $_POST['billing_phone'] ))){
          wc_add_notice( "The Phone has a maximum length of 10."  ,'error' );
        }
      }
    }

}

return new Woo_BigPost_Public();