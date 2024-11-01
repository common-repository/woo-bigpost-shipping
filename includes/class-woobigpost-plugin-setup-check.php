<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class Woo_BigPost_Plugin_Setup_Check {
    public $api_creds;
    public $pickup_locations;
    public $product_cartons;
    public $plugin_setup_messages;
    public $woocommerce_version;
    public $settings;

    public function __construct() {
        $this->settings = woobigpost_get_plugin_settings();
        $this->api_creds = $this->check_api();
        $this->pickup_locations = $this->check_pickup_locations();
        $this->product_cartons = $this->get_count_notempty_product_carton();
        $this->woocommerce_version = $this->bp_woocommerce_version_check();
    }

    /**
     * @return bool
     */
    public function check_api(){
        
        if($this->is_set('api_url') && ($this->is_set('api_key') || $this->is_set('testing_api_key'))){

            //try an api call
            $params = urlencode('Bulleen'); //sample post code
            $endpoint = "api/suburbs?q=".htmlentities( $params );
            $result		= woobigpost_send_api_request( $endpoint, '', 'GET' );
            $suburbs	= json_decode( $result );
            if(empty($suburbs)){
                $this->plugin_setup_messages['api_creds'] = 'Invalid API Details. Please double check your API details.';
                return false;
            } else {
                return true;
            }

        } else {
            $this->plugin_setup_messages['api_creds'] = 'Incomplete API Details';
            return false;
        }
    }

    /**
     * @return bool
     */
    public function check_pickup_locations(){
        $settings = $this->settings;
        if(isset($settings['bigpost_warehouse_locations']) && !empty($settings['bigpost_warehouse_locations'])){
            $postcodes = array_filter(array_column($settings['bigpost_warehouse_locations'], 'from_post_code'));
            if ( !empty($postcodes)) {
                return true;
            } else {
                $this->plugin_setup_messages['pickup_locations'] = 'There is an invalid warehouse location.';
                return false;
            }
        } else {
            $this->plugin_setup_messages['pickup_locations'] = 'Empty warehouse locations';
            return false;
        }
    }

    /**
     * @return bool
     */
    public function get_count_notempty_product_carton(){

        $args = array(
            'post_type'      => array('product', 'product_variation'),
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'           => '_carton_items',
                    'value'         => '[]',
                    'compare'       => '!=',

                )
            )
        );

        $loop = get_posts($args );

        if(count($loop) > 0){
            return true;
        } else {
            $this->plugin_setup_messages['api_product_cartons'] = 'Please add the carton settings of at least one product.';
            return false;
        }
    }

    /**
     * @param $key
     * @return bool
     */
    private function is_set($key){
        $settings = $this->settings;

        if(isset($settings[$key]) && !empty($settings[$key])){
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function bp_woocommerce_version_check(){
        $version_check = bp_woocommerce_version_check();
        if($version_check == false){
            $this->plugin_setup_messages['api_woocommerce_version'] = 'Woocommerce version needs at least 3.4!';
            return false;
        } else {
            return true;
        }
    }
}