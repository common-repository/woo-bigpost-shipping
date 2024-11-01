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
class Woo_BigPost_Admin {
    public function __construct() {

        add_action( 'wp_ajax_woobigpost_get_suburb_list', array($this, 'woobigpost_get_suburb_list_ajax') );
        add_action( 'wp_ajax_nopriv_woobigpost_get_suburb_list', array($this, 'woobigpost_get_suburb_list_ajax') );

        add_action( 'add_meta_boxes', array($this, 'woobigpost_product_add_meta_boxes') );
        add_action( 'save_post', array($this, 'woobigpost_product_save_meta_data'), 10, 2 );

        add_action( 'admin_menu', array($this, 'woobigpost_manage_admin_menu'), 70 );

        add_action( 'admin_action_woobigpost_product_box_update', array($this, 'woobigpost_product_box_update') );
        //add custom metabox for resync order button on edit order page
        add_action( 'add_meta_boxes', array($this,'woobigpost_order_metabox') );

        add_action( 'wp_ajax_woobigpost_order_resync', array($this,'woobigpost_resync_order') );
        add_action( 'wp_ajax_nopriv_woobigpost_order_resync', array($this,'woobigpost_resync_order') );


        add_action( 'wp_ajax_woobigpost_metabox_update', array($this,'woobigpost_metabox_update') );
        //add carton settings on variable products
        //add_action( 'woocommerce_product_after_variable_attributes', array($this,'woobigpost_add_custom_variation_fields'), 10, 3 );

        // Save Variation Settings
        //add_action( 'woocommerce_save_product_variation', array($this,'woobigpost_save_variation_custom_fields'), 10, 2 );

        add_action( 'wp_ajax_woobigpost_rated', array($this, 'woobigpost_rated') );
    }

    public function woobigpost_manage_admin_menu(){
        add_submenu_page(
            'woocommerce',
            __( 'Big Post Product Box Migration', 'woobigpost' ),
            __( 'Big Post Product Box Migration', 'woobigpost' ),
            'manage_options',
            'woobigpost-product-box-migration',
            array( $this, 'woobigpost_product_box_migration' )
        );
    }

    public function woobigpost_product_box_migration(){
        $settings = get_option( 'woobigpost-product-box_overwrite_settings','no');
        require_once( WOO_BIGPOST_ADMIN . '/forms/woobigpost-product-migration-page.php' );
    }

    public function woobigpost_product_box_update(){
        status_header(200);

        $overwrite = isset($_POST['woobigpost-product-box_overwrite_settings']) ? sanitize_text_field($_POST['woobigpost-product-box_overwrite_settings']) : "no";
        $package_type = sanitize_text_field($_POST['woobigpost-product-box_package_type']);
        $consolidated = sanitize_text_field($_POST['woobigpost-product-box_consolidated']);

        if ( get_option('woobigpost-product-box_overwrite_settings') !== false ) {
            update_option( 'woobigpost-product-box_overwrite_settings', $overwrite );
        } else {
            $deprecated = null;
            $autoload = 'no';
            add_option( 'woobigpost-product-box_overwrite_settings', $overwrite, $deprecated, $autoload );
        }

        $page = 1;
        
        do {
            $args = array(
                'status' => 'publish',
                'posts_per_page'=> 1000,
                'page' => $page,
                'orderby' => 'ID',
                'order' => 'ASC'
            );
            
            $products = wc_get_products( $args );
            foreach($products as $product){
                $p_settings = new Woo_BigPost_Product_Settings($product->get_id(), woobigpost_get_plugin_settings());
                //get warehouses
                $existing_warehouses = $p_settings->product_settings['_product_locations'];

                if(!empty($existing_warehouses)){
                    if($overwrite == 'yes'){
                        $warehouse = array_map( 'sanitize_text_field', $_POST['woobigpost_product_shipping_locations']);
                        update_post_meta( $product->get_id(), '_product_locations', json_encode($warehouse));
                    }else{
                        //do not migrate
                    }
                }else{
                    $warehouse = array_map( 'sanitize_text_field', $_POST['woobigpost_product_shipping_locations']);
                    update_post_meta( $product->get_id(), '_product_locations', json_encode($warehouse));
                }

                if($product->get_type() == 'simple'){

                    //get carton items for each product
                    $carton_items = json_decode($p_settings->product_settings['_carton_items']);
                    $dimensions = $product->get_dimensions(false);
                    $weight = $product->get_weight();

                    $items_array   = array();

                    if(!empty($carton_items)){
                        if($overwrite == 'yes'){
                            $items_array[] = array(
                                'height' => round($dimensions['height']),
                                'width'  => round($dimensions['width']),
                                'length' => round($dimensions['length']),
                                'weight' => $weight,
                                'packaging_type'=> $package_type,
                                'consolidated'=>$consolidated
                            );

                            update_post_meta( $product->get_id(), '_no_of_cartons', sanitize_text_field(1) );
                            update_post_meta(  $product->get_id(), '_carton_items', json_encode($items_array) );
                        }else{
                            //do not migrate
                        }

                    }else{
                        $items_array[] = array(
                            'height' => round(floatval($dimensions['height'])),
                            'width'  => round(floatval($dimensions['width'])),
                            'length' => round(floatval($dimensions['length'])),
                            'weight' => $weight,
                            'packaging_type'=> $package_type,
                            'consolidated'=>$consolidated
                        );

                        update_post_meta( $product->get_id(), '_no_of_cartons', sanitize_text_field(1) );
                        update_post_meta(  $product->get_id(), '_carton_items', json_encode($items_array) );
                    }

                } else if($product->get_type() == 'variable'){
                    $variations = $product->get_available_variations();

                    foreach($variations as $variation){

                        $dimensions = $variation['dimensions'];
                        $weight = $variation['weight'];
                        $carton_items = json_decode(get_post_meta( $variation['variation_id'], '_carton_items', true  ));

                        $items_array   = array();

                        $dimensions['height'] = (float) $dimensions['height'];
                        $dimensions['width'] = (float) $dimensions['width'];
                        $dimensions['length'] = (float) $dimensions['length'];

                        if(!empty($carton_items)){
                            if($overwrite == 'yes'){
                                $items_array[] = array(
                                    'height' => round($dimensions['height']),
                                    'width'  => round($dimensions['width']),
                                    'length' => round($dimensions['length']),
                                    'weight' => $weight,
                                    'packaging_type'=> $package_type,
                                    'consolidated'=>$consolidated
                                );

                                update_post_meta( $variation['variation_id'], '_no_of_cartons', sanitize_text_field( 1 ) );
                                update_post_meta( $variation['variation_id'] , '_carton_items', json_encode($items_array) );
                            }else{
                                //do not migrate
                            }

                        }else{
                            $items_array[] = array(
                                'height' => round($dimensions['height']),
                                'width'  => round($dimensions['width']),
                                'length' => round($dimensions['length']),
                                'weight' => $weight,
                                'packaging_type'=> $package_type,
                                'consolidated'=>$consolidated
                            );

                            update_post_meta( $variation['variation_id'], '_no_of_cartons', sanitize_text_field( 1 ) );
                            update_post_meta( $variation['variation_id'] , '_carton_items', json_encode($items_array) );
                        }
                    }

                }

            }
            $page++;
        } while ($products && count($products) > 0);
            wp_redirect(admin_url()."admin.php?page=woobigpost-product-box-migration&product_box_migration=true");

        exit;
    }

    public function woobigpost_order_metabox(){
        add_meta_box( 'resync-order-box', __('Resync Order','woobigpost'), array($this,'render_resync_order_box'), 'shop_order', 'side', 'core' );
    }

    public function render_resync_order_box(){
        global $post;
        $consignment_number = get_post_meta($post->ID, 'order_consignment_number', true);
        if(empty($consignment_number)){
            ob_start(); ?>
            <button data-id="<?php echo $post->ID; ?>" id="order_resync" name="order_resync" class="button-primary " type="button" value="Resync Order">Resync Order</button><span style="float:none;" class="spinner"></span>
            <script type="text/javascript">
                jQuery(document).ready(function($){
                    $(document).on('click', '#order_resync', function(e){
                        e.preventDefault();
                        var elem = $(this);
                        var order_id = elem.data('id');
                        var data = {
                            action: 'woobigpost_order_resync',
                            order_id: order_id
                        };

                        $.ajax({
                            url: ajaxurl,
                            data: data,
                            method: 'POST',
                            beforeSend: function(){
                                elem.next('.spinner').addClass('is-active');
                                elem.prop('disabled',true);
                            },
                            success: function(data){
                                elem.next('.spinner').removeClass('is-active');
                                elem.prop('disabled',false);
                                $('<p class="resync_message">'+data+'</p>').insertAfter(elem.next('.spinner'));
                            }
                        });
                    });
                });
            </script>
            <style>
                .resync_message{
                    background-color: #f3f3f3;
                    padding: 12px 13px;
                }
            </style>
            <?php
            echo  ob_get_clean();
        }

    }

    public function woobigpost_resync_order(){
        $order_id = sanitize_text_field($_POST['order_id']);
        woocommerce_checkout_order_processed_func($order_id);
        $order_response = get_post_meta($order_id, 'bigpost_order_response', true);
        $consignment = get_post_meta($order_id, 'order_consignment_number', true);
        $result = json_decode($order_response,true);

        if (json_last_error() === JSON_ERROR_NONE) {
            $result = $result;
        } else {
            $result = $order_response;
        }


        if(!empty($result) && !isset($result['code']) && !empty($consignment)){
            echo "Success";
        } else {
            if(isset($result['code'])){
                echo "Bigpost Response: ".$result['code']." ".$result['message'];
            }

            if(isset($result['Errors'])){
                echo "BigPost Error: ".$result['Errors'][0]['ErrorMessage'];
            }
        }

        exit;
    }

    /**
     * Get suburb list by ajax
     */
    public function woobigpost_get_suburb_list_ajax() {

        $type   = isset( $_POST['type'] ) ? sanitize_text_field($_POST['type']) : 'from';
        $suburb = isset( $_POST['value'] ) ? sanitize_text_field($_POST['value']) : '';

        $html = woobigpost_get_suburb_list( $suburb, $type );

        echo $html;
        exit;
    }

    /**
     * Add product post meta boxes
     */
    public function woobigpost_product_add_meta_boxes() {
        add_meta_box( 'woobigpost_meta', __( 'Big Post Shipping - Product Customisation', 'woobigpost' ), array($this, 'woobogpost_product_meta_fields'), 'product', 'normal', 'default' );
    }

    /**
     * Manage product metabox fields
     */
    public function woobogpost_product_meta_fields() {
        require_once( WOO_BIGPOST_ADMIN . '/forms/woobigpost-product-metabox.php' );
    }

    /**
     * Save meta data of products
     */
    public function woobigpost_product_save_meta_data( $post_id, $post ) {

        // Check the current post details
        if ( empty( $post_id ) || empty( $post ) || is_int( wp_is_post_revision( $post ) )
            || is_int( wp_is_post_autosave( $post ) ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
            || ( empty( $_POST['post_ID'] ) || $post_id != $_POST['post_ID'] )
            || ( ! current_user_can( 'edit_post', $post_id ) ) )
        {
            return $post_id;
        };

        if( $post->post_type != 'product' ) return;

        // Save consolidated, display only in advance shipping margin
        if( isset($_POST['CanConsolidated']) ) {
            update_post_meta( $post_id, '_can_consolidated', sanitize_text_field( $_POST['CanConsolidated'] ) );
        }

        // Save shipping type
        if( isset( $_POST['woobigpost_shipping_type']) ) {
            $shipping_type = array_map( 'sanitize_text_field', $_POST['woobigpost_shipping_type'] );

            update_post_meta( $post_id, '_shipping_type', json_encode($shipping_type) );
            update_post_meta( $post_id, '_use_admin_setting', 'No' );
        }

        // Check if need to inherit admin settings
        if( isset($_POST['woobigpost_use_admin_setting']) ) {
            $shipping_type_product = array();

            update_post_meta( $post_id, '_use_admin_setting', sanitize_text_field($_POST['woobigpost_use_admin_setting']) );
            update_post_meta( $post_id, '_shipping_type',$shipping_type_product);
        }

        // Save authority options
        if( isset($_POST['woobigpost_authority_option']) ) {
            update_post_meta( $post_id, '_authority_option', sanitize_text_field( $_POST['woobigpost_authority_option'] ) );
        }

        if(isset($_POST['woobigpost_free_shipping'])){
            $free_shipping = $_POST['woobigpost_free_shipping'] == '1' ? 1 : 0;
            update_post_meta($post_id, '_free_shipping', sanitize_text_field($free_shipping) );
        }

        if(isset($_POST['woobigpost_variable_free_shipping'])){
            foreach($_POST['woobigpost_variable_free_shipping'] as $key=>$free){
                $free_shipping = $free == '1' ? 1 : 0;
                update_post_meta($key, '_free_shipping', sanitize_text_field($free_shipping) );
            }
        }

        if(isset($_POST['woobigpost_show_plugin'])){
            $show_plugin = $_POST['woobigpost_show_plugin'] == '1' ? 1 : 0;
            update_post_meta($post_id, '_show_plugin', sanitize_text_field($show_plugin) );
        }

        // Update cartons
        if( isset($_POST['no_of_cartons']) ) {

            $items_array   = array();
            for( $i=0; $i < sanitize_text_field( $_POST['no_of_cartons'] ); $i++ ) {



                $items_array[] = array(
                    'height' => sanitize_text_field(floatval($_POST['carton_height'][$i])),
                    'width'  => sanitize_text_field(floatval($_POST['carton_width'][$i])),
                    'length' => sanitize_text_field(floatval($_POST['carton_length'][$i])),
                    'weight' => sanitize_text_field(floatval($_POST['carton_weight'][$i])),
                    'packaging_type' => sanitize_text_field($_POST['packaging_type'][$i]),
                    'consolidated' => sanitize_text_field($_POST['consolidated'][$i]),
                    'mhp' => sanitize_text_field($_POST['mhp'][$i]),
                );

            }


            update_post_meta( $post_id, '_no_of_cartons', sanitize_text_field( $_POST['no_of_cartons'] ) );
            update_post_meta( $post_id, '_carton_items', json_encode($items_array) );

        } else if(isset($_POST['woobigpost-variation-no-of-cartons']) && !empty($_POST['woobigpost-variation-no-of-cartons'])){
            $variation_no_cartons = array_map( 'sanitize_text_field', $_POST['woobigpost-variation-no-of-cartons'] );
            foreach($variation_no_cartons as $key=>$var_cartons){
                $items_array   = array();

                for( $i=0; $i < sanitize_text_field( $var_cartons ); $i++ ) {

                    $items_array[] = array(
                        'height' => sanitize_text_field(floatval($_POST['carton_height'][$key][$i])),
                        'width'  => sanitize_text_field(floatval($_POST['carton_width'][$key][$i])),
                        'length' => sanitize_text_field(floatval($_POST['carton_length'][$key][$i])),
                        'weight' => sanitize_text_field(floatval($_POST['carton_weight'][$key][$i])),
                        'packaging_type' => sanitize_text_field($_POST['packaging_type'][$key][$i]),
                        'consolidated' => sanitize_text_field($_POST['consolidated'][$key][$i]),
                        'mhp' => sanitize_text_field($_POST['mhp'][$key][$i]),
                    );

                }

                update_post_meta( $key, '_no_of_cartons', sanitize_text_field((int)$var_cartons));
                update_post_meta( $key, '_carton_items', json_encode($items_array) );
            }
        }

        if($_POST['action'] != "inline-save"){
            if( isset($_POST['woobigpost_product_shipping_locations']) ) {
                $product_locations = array_map( 'sanitize_text_field', $_POST['woobigpost_product_shipping_locations'] );
                update_post_meta( $post_id, '_product_locations', json_encode($product_locations));
            } else {
                update_post_meta( $post_id, '_product_locations', array());
            }
        }
        

    }

    /**
     * Display of custom fields on product variation
     * @param $loop
     * @param $variation_data
     * @param $variation
     */
    public function woobigpost_add_custom_variation_fields($loop, $variation_data, $variation ){
        $settings = woobigpost_get_plugin_settings();
        $carton_items = get_post_meta( $variation->ID, '_carton_items', true );
        ob_start();
        require(WOO_BIGPOST_ADMIN . '/forms/woobigpost-variable-product-carton-settings.php');
        $content = ob_get_clean();
        echo $content;
    }

    /**
     * Save variation custom field
     * @param $variation_id
     */
    public function woobigpost_save_variation_custom_fields( $variation_id ){
        
        //THIS FUNCTION IS NOT BEING USED

        // Update cartons
        if( isset($_POST['woobigpost-variation-no-of-cartons'][$variation_id]) ) {
            $items_array   = array();
            for( $i=0; $i < sanitize_text_field( $_POST['woobigpost-variation-no-of-cartons'][$variation_id] ); $i++ ) {

                $items_array[] = array(
                    'height' => sanitize_text_field($_POST['carton_height'][$variation_id][$i]),
                    'width'  => sanitize_text_field($_POST['carton_width'][$variation_id][$i]),
                    'length' => sanitize_text_field($_POST['carton_length'][$variation_id][$i]),
                    'weight' => sanitize_text_field($_POST['carton_weight'][$variation_id][$i]),
                    'packaging_type' => sanitize_text_field($_POST['packaging_type'][$variation_id][$i]),
                    'consolidated' => sanitize_text_field($_POST['consolidated'][$variation_id][$i]),
                    'mhp' => sanitize_text_field($_POST['mhp'][$variation_id][$i]),
                );
            }

            update_post_meta( $variation_id, '_no_of_cartons', sanitize_text_field( $_POST['woobigpost-variation-no-of-cartons'] ) );
            update_post_meta( $variation_id, '_carton_items', json_encode($items_array) );
        }
    }

    public function woobigpost_metabox_update(){
        $post_id = sanitize_text_field($_POST['post_id']);
        $product_data = wc_get_product($post_id);
        $settings = woobigpost_get_plugin_settings();
        $p_settings = new Woo_BigPost_Product_Settings($post_id, $settings);

        $no_of_cartons      = $p_settings->product_settings['_no_of_cartons'];
        $CanConsolidated    = $p_settings->product_settings['_can_consolidated'];
        $carton_items       = $p_settings->product_settings['_carton_items'];
        $ShippingType       = $p_settings->product_settings['_shipping_type'];
        $Authority_option   = $p_settings->product_settings['_authority_option'];
        $use_admin_setting  = $p_settings->product_settings['_use_admin_setting'];

        ob_start();
        require_once( WOO_BIGPOST_DIR . '/includes/admin/forms/templates/variable_product.php' );
        $html = ob_get_clean();
        echo $html;
    }

    public function woobigpost_rated(){
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( -1 );
        }
        update_option( 'woobigpost_admin_footer_text_rated', 1 );
        wp_die();
    }
}

return new Woo_BigPost_Admin();