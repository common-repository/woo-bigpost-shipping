<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class Woo_BigPost_Free_Shipping_Check {
    private $settings;
    public $free_shipping_enabled;
    public $has_minimum_order_value;
    public $has_max_shipping_cost;
    public $free_shipping_freight_cost_condition;
    public $free_shipping_mixed_cart_option;
    private $product_quantity = 1;


    public function __construct() {
        $this->settings = woobigpost_get_plugin_settings();
        $this->has_minimum_order_value = $this->has_minimum_order_value();
        $this->has_max_shipping_cost = $this->has_max_shipping_cost();
        $this->free_shipping_freight_cost_condition = $this->get_freight_condition();
        $this->free_shipping_mixed_cart_option = $this->get_mixed_cart_option();
    }

    /**
     * get the minimum order value if set else false
     * @return bool
     */
    private function has_minimum_order_value(){
        if(!empty($this->settings['free_shipping_min_order_value']) && $this->settings['free_shipping_min_order_value'] >= 0){
            $min = $this->settings['free_shipping_min_order_value'];
        } else {
            $min = false;
        }

        return $min;
    }

    /**
     * check if the price of hte product is free based on its price
     * @param $price
     * @return bool
     */
    public function check_price_if_free($price){
        if($this->has_minimum_order_value === false){
            $free = true;
        } else {
            $free = false;
            if($price >= $this->has_minimum_order_value){
                $free = true;
            }
        }
        return $free;
    }

    /**
     * get max shipping cost if set else false
     * @return bool
     */
    private function has_max_shipping_cost(){
        if(!empty($this->settings['free_shipping_max_freight_cost']) && $this->settings['free_shipping_max_freight_cost'] > 0){
            $max = $this->settings['free_shipping_max_freight_cost'];
        } else {
            $max= false;
        }

        return $max;
    }

    /**
     * @return string
     */
    private function get_freight_condition(){
        $res = "";
        if(!empty($this->settings['free_shipping_freight_cost_condition'])){
            $res = $this->settings['free_shipping_freight_cost_condition'];
        }

        return $res;
    }

    /**
     * get mixed cart option value
     */
    private function get_mixed_cart_option(){
        $res = "";
        if(!empty($this->settings['free_shipping_mixed_cart_option'])){
            $res = $this->settings['free_shipping_mixed_cart_option'];
        }

        return $res;
    }

    /**
     * check if product is free based on the settings of bigpost
     * @param $product_id
     * @return bool
     */
    public function check_product_price_if_free($product_id){
        $product = wc_get_product($product_id );
        $price = round($product->get_price() * $this->product_quantity, 2);

        if($this->settings['free_shipping_config'] != 'disabled'){
            if($this->settings['free_shipping_config'] == 'enable_all'){
                $free = $this->check_price_if_free($price);
            } else {
                $free = false;
                $free_meta	= get_post_meta( $product_id, '_free_shipping', true );
                if($free_meta == '1'){
                    $free = $this->check_price_if_free($price);
                }
            }
        } else {
            $free = false;
        }

        return $free;
    }

    /**
     * check if freight cost is free
     * @param $freight_cost
     * @return int
     */
    public function check_freight_if_free($freight_cost, $price_check){
        if($this->has_max_shipping_cost === false){
            $cost = $price_check == true ? 0 : $freight_cost;
        } else {
            $cost = $freight_cost;
            if($freight_cost <= $this->has_max_shipping_cost && $price_check == true){
                $cost = 0;
            }
        }

        if($this->has_max_shipping_cost !== false){
            if(!empty($this->free_shipping_freight_cost_condition) && $price_check == true){
                if($this->free_shipping_freight_cost_condition == 'charge_full_amount'){
                    $cost = $cost > 0 ? $cost : 0;
                }else if ($this->free_shipping_freight_cost_condition == 'charge_over_limit'){
                    $cost = $cost > 0 ? $cost - $this->has_max_shipping_cost : 0;
                }
            }
        }


        return $cost;
    }

    /**
     * check if freight cost is free
     */
    public function check_product_freight_cost_if_free($product_id,$freight_cost,$by_price){
        $cost = 0;
        if($this->settings['free_shipping_config'] != 'disabled'){
            if($this->settings['free_shipping_config'] == 'enable_all'){
                $cost = $this->check_freight_if_free($freight_cost, $by_price);

            } else {
                $cost = $freight_cost;
                $free_meta	= get_post_meta( $product_id, '_free_shipping', true );
                if($free_meta == '1'){
                    $cost = $this->check_freight_if_free($freight_cost, $by_price);
                }
            }
        } else {
            $cost = $freight_cost;
        }

        return $cost;
    }

    /**
     * get decision based on evaluation of min order value and freight cost
     */
    public function get_decision($by_price, $by_freight){
        if($by_price == true && $by_freight == 0){
            $cost = 0;
        } else{
            $cost = $by_freight;
        }

        return $cost;
    }

    /**
     * check if overall it is free based on minimum order value and freight cost
     */
    public function check_product_if_free($post_data, $freight_cost){
        $this->product_quantity = $post_data['product_quantity'];
        $by_price = $this->check_product_price_if_free($post_data['productId']);
        $by_freight = $this->check_product_freight_cost_if_free($post_data['productId'],$freight_cost, $by_price);

        $cost = $this->get_decision($by_price,$by_freight);

        return $cost;
    }

    /**
     * @param $result
     */
    public function create_new_shipping_rates($result, $mixed=false){
        global $woocommerce;
        $new_rates = array();
        $total_price = $woocommerce->cart->subtotal;

        if(isset($result->Object->DeliveryOptions)){
            foreach($result->Object->DeliveryOptions as $deliveryOpt){

                if(isset($deliveryOpt->CarrierOptions) && !empty($deliveryOpt->CarrierOptions)){
                    $freight_cost = $deliveryOpt->CarrierOptions[0]->Total;

                    $price_check = $this->check_price_if_free($total_price);
                    $freight_check = $this->check_freight_if_free($freight_cost, $price_check);

                    if($mixed === false){

                        $cost = $this->get_decision($price_check, $freight_check);

                    } else {
                        $decision = $this->get_decision($price_check,$freight_check);

                        if($decision == 0){
                            //get the option selected for How should we present and charge shipping to customers if they have both free and non-free items in their cart?
                            if($this->free_shipping_mixed_cart_option == 'free'){
                                $cost = 0;
                            } else {
                                //get the freight cost of non-free items
                                $shipping_quote = new Woo_BigPost_Shipping_Quote('cart');
                                $new_rates['Results'] = $shipping_quote->get_shipping_rates_non_free();
                            }

                        } else {
                            $cost = $decision;
                        }
                    }

                    if(isset($cost)){
                        $new_rates[$deliveryOpt->JobType] = $cost;    
                    }
                }
            }
        }

        return $new_rates;
    }
}


return new Woo_BigPost_Free_Shipping_Check();