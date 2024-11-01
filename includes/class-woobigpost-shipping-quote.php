<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class Woo_BigPost_Shipping_Quote {
    private $settings;
    private $page;
    public $Shipping_Method_Selected;
    public $woobigpost_buyer_is_business;
    public $woobigpost_leave_authority;
    public $woobigpost_has_forklift;
    public $woobigpost_have_confirm;

    public $woobigpost_tosuburb;
    public $woobigpost_tostate;
    public $woobigpost_topostcode;
    public $woobigpost_topostcodeid;
    public $woobigpost_todepotid;
    public $free_disabled_items;
    public $shipping_rates;


    public function __construct($page) {
        $this->settings = woobigpost_get_plugin_settings();
        $this->page = $page;

        $this->Shipping_Method_Selected = WC()->session->get( 'Shipping_Method_Selected');
        $this->woobigpost_buyer_is_business =  WC()->session->get( 'woobigpost-buyer_is_business');
        $this->woobigpost_leave_authority = WC()->session->get( 'woobigpost-leave_authority');
        $this->woobigpost_has_forklift =  WC()->session->get( 'woobigpost-has_forklift');
        $this->woobigpost_have_confirm = WC()->session->get( 'woobigpost-have_confirm');

        $this->woobigpost_tosuburb = WC()->session->get( 'woobigpost-tosuburb');
        $this->woobigpost_tostate = WC()->session->get( 'woobigpost-tostate');
        $this->woobigpost_topostcode = WC()->session->get( 'woobigpost-topostcode');
        $this->woobigpost_topostcodeid =  WC()->session->get( 'woobigpost-topostcodeid');
        $this->woobigpost_todepotid = WC()->session->get( 'woobigpost-todepotid');

        $this->free_disabled_items = WC()->session->get('free_disabled_items');
    }

    /**
     * @param $page
     * @return mixed
     */
    public function get_shipping_rates_non_free(){
        $warehouses = $this->settings['bigpost_warehouse_locations'];
        $can_consolidated = $this->settings['is_advanced_mode'];
         if($this->page == 'product'){

         } else {
             $data_items = array();
             $items = $this->free_disabled_items;

             $matched_warehouse = array();

             foreach( $items as $item ) {
                 $parent_product = $item['product_id'];

                 $show_plugin        = get_post_meta( $parent_product, '_show_plugin', true );
                 $show_plugin_value  = $show_plugin == "" && $this->settings['product_page'] != 'hide' ? '1' : $show_plugin; //if initial setup,show plugin on product page by default

                 if($show_plugin_value == "0"){
                     continue; //exclude products where bigpost quote box is hidden
                 }

                 $product_id = isset($item['product_id'] ) ? $item['product_id']: '';

                 if(isset($item['variation_id']) && $item['variation_id'] > 0){
                     $product_id  = $item['variation_id'];
                 }

                 $cartonsRows   = get_post_meta( $product_id, '_carton_items', true );
                 $cartonsRows   = json_decode( $cartonsRows );

                 $product_location   = json_decode(get_post_meta( $parent_product, '_product_locations', true ));
                 $item_location = empty($product_location)? array_keys($warehouses) : $product_location;
                 $matched_warehouse = empty($matched_warehouse)? $product_location : array_intersect($matched_warehouse,$item_location);

                 if( !empty($cartonsRows) ) {
                     foreach( $cartonsRows as $row ) {

                         if( $can_consolidated == 'Yes' && !empty($row->consolidated) ) {
                             $can_consolidated = $row->consolidated;
                         }

                         $data_items[] = array(
                             'Description'      => substr(get_the_title( $product_id ), 0, 50),
                             'Length'           => floatval( $row->length ),
                             'Width'                => floatval( $row->width ),
                             'Height'           => floatval( $row->height ),
                             'Weight'           => floatval( $row->weight ),
                             'ItemType'         => intval( $row->packaging_type ),
                             'isMHP'            => ( $row->mhp == 'Yes' ) ? true : false,
                             'Quantity'         => $item['quantity'],
                             'Consolidatable'   => ( $can_consolidated == 'Yes' ) ? true : false
                         );
                     }
                 }
             }

             $warehouses =  woobigpost_get_selected_warehouse($matched_warehouse);

             if( empty($data_items) ) {
                 WC()->session->set( 'WooBigPost_shippingPrices_Display','Shipping settings are not saved for some of products in the cart.');
                 echo "Shipping settings are not saved for some of products in the cart";
                 wp_die();
             }

             if(empty($matched_warehouse)){
                 WC()->session->set( 'WooBigPost_shippingPrices_Display',$this->settings['no_available_shipping']);

                 echo $this->settings['no_available_shipping'];
                 wp_die();
             }
         }

        $has_forklift   = $this->woobigpost_has_forklift ? true : false;
        $auth_leave     = $this->woobigpost_leave_authority ? true : false;

        $Address = $this->woobigpost_tosuburb . ' ' . $this->woobigpost_tostate;
        $current_user = wp_get_current_user();
        $delivery_options_arr = array();

        if(!empty($warehouses)){
            foreach($warehouses as $location){
                $API_data = array(
                    'JobType'           => "", // DEPOT = 1, Business = 2, HDS = 3, blank to Depot + home or business
                    'DepotId'           => $this->woobigpost_todepotid,
                    'BuyerIsBusiness'   => $this->woobigpost_buyer_is_business == "1" ? true : false,
                    'BuyerHasForklift'  => ( $has_forklift ) ? true : false,
                    'ReturnAuthorityToLeaveOptions' => ( $auth_leave ) ? true : false,
                    'PickupLocation'    => array(
                        'Name'      => "WooCommerce - Big Post Shipping",
                        'Address'   => $location['from_address'],
                        "LocalityId"    => $location['from_postcode_id'],
                        'Locality'  => array(
                            "Id"        => $location['from_postcode_id'],
                            "Suburb"    => $location['from_suburb_addr'],
                            "Postcode"  => $location['from_post_code'],
                            "State"     => $location['from_state']
                        )
                    ),
                    'BuyerLocation'     => array(
                        'Name'      => isset($current_user->user_nicename) ? $current_user->user_nicename : "Guest User",
                        'Address'   => $Address,
                        "LocalityId"    => $this->woobigpost_topostcodeid,
                        'Locality'  => array(
                            "Id"        => $this->woobigpost_topostcodeid,
                            "Suburb"    => $this->woobigpost_tosuburb,
                            "Postcode"  => $this->woobigpost_topostcode,
                            "State"     => $this->woobigpost_tostate
                        )
                    ),
                    'Items' => $data_items
                );

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

        $result = woobigpost_get_cheapest_delivery_opt($delivery_options_arr,'cart', array());
        return $result;
    }
}
