<?php
    class WC_Bigpost_CIS_Shipping_Method extends WC_Shipping_Method{
        public function __construct(){
            $this->id = 'woobigpost';
            $this->method_title = 'Bigpost 2.0';
            $this->method_description = 'This is a new shipping method';
            $this->enabled = 'yes';
            $this->title = 'Bigpost 2.0';
            $this->init();
        }

        public function init(){
            $this->init_form_fields();
            $this->init_settings();
            add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
        }

        public function init_form_fields(){
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable'),
                    'type' => 'checkbox',
                    'label' => __('Enable this shipping method'),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __('Method Title'),
                    'type' => 'text',
                    'description' => __('This controls the title which the user sees during checkout'),
                    'default' => __('Bigpost 2.0')
                ),
                'bigpost_key' => array(
                    'title' => __('Bigpost Platform Key'),
                    'type' => 'text',
                    'description' => __('Key found in the CIS'),
                    'default' => __('')
                ),
                'manage_products_in_cis' => array(
                    'title' => __( 'Manage Products in CIS', 'woobigpost' ),
                    'type' => 'select',
                    'description' => esc_attr( '', 'woobigpost' ),
                    'options' => array(
                        'No' => esc_attr( 'No', 'woobigpost' ),
                        'Yes' => esc_attr( 'Yes', 'woobigpost' ),
                    ),
                    'css' => 'width: auto;',
                    'class' => 'business_shipping'
                ),
            );
        }

        public function calculate_shipping($package = array()){

                $quotes = $this->get_quotes();

                foreach($quotes as $quote){
                    $rate = array(
                        'id' => $this->id . '_' . $quote['id'],
                        'label' => $quote['label'],
                        'cost' => $quote['cost']
                    );

                    $this->add_rate($rate);
                }
            }

        public function get_quotes(){
            
            $boxes = array();
            foreach(WC()->cart->get_cart() as $cart_item){
                
                $product_id = $cart_item['product_id'];
                $product = wc_get_product($product_id);

                $product_weight = $product->get_weight();
                $product_length = $product->get_length();
                $product_width = $product->get_width();
                $product_height = $product->get_height();
                
                $product_quantity = $cart_item['quantity'];

                $boxes[] = array("name"=>"Product Name",
                    "length"=>$product_length, 
                    "height"=>$product_height, 
                    "width"=>$product_width, 
                    "weight"=>$product_weight
                );
            }

            $payload = array();
            $payload['page'] = "checkout";
            $payload['key'] = "65d8005e9139b";
            $payload['boxes'] = json_encode($boxes);
            $payload['quantity'] = 1;
            $payload['has_forklift'] = "";
            $payload['has_access'] = "1";
            $payload['authority_to_leave'] = "1";
            $payload['postcode'] = "2340";
            $payload['suburb'] = "Tamworth";
            $payload['buyer_location'] = '{"PostcodeId":1635,"Suburb":"Tamworth","Postcode":2340,"State":"NSW"}';
            $payload['shipping_type'] = "BUSINESS";

            $url_param = http_build_query($payload);

            
            $ch = curl_init('https://cis.bigpost.com.au/quote?'.$url_param);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dummy_data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/x-www-form-urlencoded',
            ));
            curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_REFERER']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);
            
            $response = json_decode($response);

            $quotes = array();
            if(!$response->error){
                foreach($response->result as $key=>$quote_row){
                    if($key == "BUSINESS" || $key == "HDS"){
                        $quotes[] = array(
                            'id' => 'woobigpost_'.$key,
                            'label' => $quote_row->TYPE,
                            'cost' => $quote_row->DETAILS->Total
                        );
                    }

                    if($key=="DEPOT"){
                        $quotes[] = array(
                            'id' => 'woobigpost_'.$key,
                            'label' => "DEPOT",
                            'cost' => $quote_row
                        );
                    }
                }
            }
        
            
            return $quotes;
        }
    }
?>