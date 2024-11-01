<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class Woo_BigPost_Product_Settings {

    public $productID;
    public $product_settings;
    public $global_settings;
    public function __construct($productID, $settings, $parentID = 0) {
        $this->productID = $productID;
		$this->parentId = ($parentID == 0) ? $productID : $parentID;
        $this->global_settings = $settings;
        $this->product_settings = $this->getProductSettings();
    }

    public function getProductSettings(){
        $parent_settings    = get_post_custom($this->parentId);
        $show_plugin        = isset($parent_settings['_show_plugin']) ? $parent_settings['_show_plugin'][0] : "";
		
		if(!isset($this->global_settings['product_page'])){
			$this->global_settings['product_page'] = '';
		}
		
        $show_plugin_value  = $show_plugin == "" && $this->global_settings['product_page'] != 'hide' ? '1' : $show_plugin; //if initial setup,show plugin on product page by default

        $this->product_settings['show_plugin']        = $show_plugin_value;
        $this->product_settings['_use_admin_setting'] = isset($parent_settings['_use_admin_setting']) ? $parent_settings['_use_admin_setting'][0] : "";
        $this->product_settings['_authority_option']  = isset($parent_settings['_authority_option']) ? $parent_settings['_authority_option'][0] : "";
        $product_stypes                               = isset($parent_settings['_shipping_type']) ? $parent_settings['_shipping_type'][0] : "";
        $product_location                             = json_decode(isset($parent_settings['_product_locations']) ? $parent_settings['_product_locations'][0] : ""); //use the original maybe_unserialize for data that are already stored
        $product_settings                             = $this->parentId == $this->productID ? $parent_settings : get_post_custom($this->productID);
        $this->product_settings['_free_shipping']     = isset($product_settings['_free_shipping']) ? $product_settings['_free_shipping'][0] : "";
        $this->product_settings['_no_of_cartons']     = isset($product_settings['_no_of_cartons']) ? $product_settings['_no_of_cartons'][0] : "";
        $this->product_settings['_can_consolidated']  = isset($product_settings['_can_consolidated']) ? $product_settings['_can_consolidated'][0] : "";
        $this->product_settings['_carton_items']      = isset($product_settings['_carton_items']) ? $product_settings['_carton_items'][0] : "";

        $this->product_settings['_shipping_type'] 	  = is_array($product_stypes)? $product_stypes : json_decode($product_stypes);

        $all_items_available_to = (isset($this->global_settings['bigpost_items_available_to']))?$this->global_settings['bigpost_items_available_to']:'';

        if(empty($product_location)){
            //use maybe_serialize, for old data before updating to json
            $product_location   = maybe_unserialize(isset($product_settings['_product_locations']) ? $product_settings['_product_locations'][0] : "");
        }

        //if empty data on product settings for warehouses
        if(!is_array($product_location) && empty($product_location)){
			if(isset($this->global_settings['bigpost_warehouse_locations'])){
            	$product_location = array_keys($this->global_settings['bigpost_warehouse_locations']); //return all available warehouses				
			}
        }

        $this->product_settings['_product_locations'] = $product_location;
        $this->product_settings['_items_available_to'] = $all_items_available_to;

        if($this->product_settings['_use_admin_setting'] == "" && $this->product_settings['_authority_option'] == ""){ //if initial setup and empty, set yes as default
            $this->product_settings['_use_admin_setting'] = "Yes";
        }

        if( $this->product_settings['_authority_option'] == ""){ //if initial setup and empty, set a default value
            $this->product_settings['_authority_option'] = "global";
        }

        return $this->product_settings;
    }
}