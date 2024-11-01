<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Shipping Method Class
 *
 * Manage shipping method,
 * and it's functionalities
 */
class Woo_BigPost_Shipping_Method extends WC_Shipping_Method {
    public $plugin_check;
    public $id;
    public $method_title;
    public $method_description;
    public $availability;
    public $supports;
    public $instance_id;
    public $advanced_margins;
    public $have_forklift;
    public $have_authority;
    public $api_url;
    public $api_username;
    public $api_password;
    public $api_key;

    public function __construct($instance_id=0) {

        $this->id                   = 'woobigpost';
        $this->method_title         = __( 'Big Post Shipping', 'woobigpost' );
        $this->method_description   = __( 'Use this shortcode in Page/Post to search for the shipping status of the Order <br/> <strong>[woobigpost_shipping_status]</strong>', 'woobigpost' );

        //$this->tax_status = 'notax';
        // Availability & Countries
        $this->availability = 'including';

        if(!isset($_GET['section'])){
            $this->instance_id = absint( $instance_id );
        }

        $this->supports = array(
            'shipping-zones',
            'settings'
        );

        $this->advanced_margins = array();
        $this->init();

        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'woobigpost_save_custom_options' ) );

               /* check shipping enabled on checkout page */
        add_action( 'wp_ajax_check_woobigpost_shipping_enabled', array( $this, 'check_woobigpost_shipping_enabled_func') );
        add_action( 'wp_ajax_nopriv_check_woobigpost_shipping_enabled', array( $this, 'check_woobigpost_shipping_enabled_func') );

        $plugin_setup_data = $this->get_option( 'plugin_setup' );
        if(!empty($plugin_setup_data)){
            $this->plugin_check = (object) $plugin_setup_data;
            $version_check = bp_woocommerce_version_check();
            $this->plugin_check->woocommerce_version = $version_check;
        } else {
            $this->plugin_check = new Woo_BigPost_Plugin_Setup_Check();
        }

        $settings_sec = isset( $_GET['section'] ) ? $_GET['section'] : '';

        //plugin setup check
        if(($this->plugin_check->api_creds == false || $this->plugin_check->woocommerce_version == false) && ($settings_sec == 'woobigpost')){
            wp_register_script( 'woobigpost-plugin-setup-js', WOO_BIGPOST_URL . 'js/woobigpost-plugin-setup.js', array( 'jquery' ), WOO_BIGPOST_VERSION, true );
            wp_enqueue_script( 'woobigpost-plugin-setup-js' );
        } else {
            if($settings_sec == 'woobigpost' && !$this->plugin_check->pickup_locations){
                wp_register_script( 'woobigpost-enable_location-js', WOO_BIGPOST_URL . 'js/woobigpost-enable_location.js', array( 'jquery' ), WOO_BIGPOST_VERSION, true );
                wp_enqueue_script( 'woobigpost-enable_location-js' );
            } else {
                if($settings_sec == 'woobigpost'){
                    wp_register_script( 'woobigpost-enable_all-js', WOO_BIGPOST_URL . 'js/woobigpost-enable_all.js', array( 'jquery' ), WOO_BIGPOST_VERSION, true );
                    wp_enqueue_script( 'woobigpost-enable_all-js' );
                }
            }
        }
    }

    /**
     * Init your settings
     *
     * @access public
     * @return void
     */
    public function init() {

        $settings = woobigpost_get_plugin_settings();

        $this->enabled  = $this->get_option( 'enabled', 'yes' );
        $this->title    = $this->get_option( 'title', __( 'Big Post Shipping', 'woobigpost' ) );

        $this->have_forklift    = $this->get_option( 'have_forklift', 'No' );
        $this->have_authority   = $this->get_option( 'have_authority', 'No' );

        $this->api_url      = $this->get_option( 'api_url' );

        if($this->api_url == "https://bigpost.com.au/"){ //old api url
            $this->api_url = "https://api.bigpost.com.au/";
        }

        if($this->api_url == "https://staging.bigpost.com.au/"){
            $this->api_url = "https://stagingapiv2.bigpost.com.au";
        }

        $this->api_username = $this->get_option( 'api_username' );
        $this->api_password = $this->get_option( 'api_password' );
        $this->api_key      = $this->get_option( 'api_key' );

        // Load the settings API
        $this->init_form_fields();
        $this->init_settings();

        $this->tax_status = 'taxable';
        #if($settings['tax_exclusive_widget'] == "yes" && $settings['exclude_tax_from_total'] == "yes"){
        #    $this->tax_status = '';
        #}

        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    /**
     * Define settings field for this shipping
     * @return void
     */
    public function init_form_fields() {
        $settings = woobigpost_get_plugin_settings();
        $warehouses = (isset($settings['bigpost_warehouse_locations']))?$settings['bigpost_warehouse_locations']:array();
        $warehouse_names = array();
        foreach($warehouses as $k => $w){
            $warehouse_names[] = $w['from_name'];
        }
       
        $carriers = get_carriers();
        #echo "<pre>";print_r($carriers); echo "</pre>";
        
        $restrict_carriers = array();
        
        //print_r($restrict_carriers);
        $order_status_opt = createOrderStatusOpt();
        $form_fields = array(
            array( 'type' => 'seperator' ),
            array(
                'title' => __( '', 'woobigpost' ),
                'type'  => 'title',
                'desc'  => '',
                'id'    => 'enable',
            ),
            'enabled' => array(
                'title' => esc_attr( 'Enable', 'woobigpost' ),
                'type' => 'checkbox',
                'label' => __( 'Check this to enable Big Post shipping', 'woobigpost' ),
                'default' => 'yes',
                'id' => 'enable',
                'class'=>'in_cis'
            ),
            'use_cis' => array(
                'title' => esc_attr( 'Use CIS Platform', 'woobigpost' ),
                'type' => 'checkbox',
                'label' => __( '&nbsp;', 'woobigpost' ),
                'default' => 'no',
                'id' => 'use_cis',
                'class'=>'in_cis'
            ),
            'bigpost_key' => array(
                'title' => __('Big Post CIS Secret Key'),
                'type' => 'text',
                'description' => __('Key found in the CIS'),
                'default' => __(''),
                'class'=>'in_cis'
            ),

            array( 'type' => 'seperator' ),
            array(
                'title' => __( 'Big Post API Credentials', 'woobigpost' ),
                'type'  => 'title',
                'desc'  => '',
                'id'    => 'api_details',
            ),
            'api_url' => array(
                'title' => __( 'API Access URL', 'woobigpost' ),
                'type' => 'select',
                'description' => __( 'Input Your Big Post API Access URL', 'woobigpost' ),
                'default' => 'https://api.bigpost.com.au/',
                'desc_tip'  => true,
                'options'  => array(
                    'https://stagingapiv2.bigpost.com.au/' => 'Testing Mode',
                    'https://api.bigpost.com.au/' => 'Live Mode',
                ),
            ),
            'api_key' => array(
                'title' => __( 'Live Plugin Key', 'woobigpost' ),
                'type' => 'text',
                'description' => __( 'Input Your Big Post Production API Key', 'woobigpost' ),
                'desc_tip'  => true,
                'placeholder' => 'Big Post API Token'
            ),
            'testing_api_key' => array(
                'title' => __( 'Testing Key', 'woobigpost' ),
                'type' => 'text',
                'description' => __( 'Input Your Big Post Testing API Key', 'woobigpost' ),
                'desc_tip'  => true,
                'placeholder' => 'Staff Use Only'
            ),
            array( 'type' => 'seperator' ),
            'shipping_types' => array(
                'title'     => __( 'Shipping Types', 'woocommerce' ),
                'type'      => 'multicheckbox',
                'default'   => array( 'HDS', 'BUSINESS', 'DEPOT' ),
                'options'   => array(
                    array(
                        'value' => 'HDS',
                        'label' => 'Home Delivery Service (HDS)',
                    ),
                    array(
                        'value' => 'BUSINESS',
                        'label' => 'Business'
                    ),
                    array(
                        'value' => 'DEPOT',
                        'label' => 'Depot'
                    )
                )
            ),

            array(
                'title' => __( 'Home Delivery Shipping', 'woobigpost' ),
                'type'  => 'title',
                'desc'  => '',
                'id'    => 'home_delivery',
            ),
            'shipping_hds_label' => array(
                'title' => __( 'Label', 'woobigpost' ),
                'type' => 'text',
                'description' => __( 'Your Big Post Shipping Label to Display on HDS Type', 'woobigpost' ),
                'default' => __( 'Delivery to your home', 'woobigpost' ),
                'desc_tip'  => true,
                'custom_attributes' => array( 'readonly' => '' ),
            ),
            'shipping_hds_description' => array(
                'title' => __( 'Description', 'woobigpost' ),
                'type' => 'textarea',
                'description' => __( 'Your Big Post Shipping Description to Display on HDS Type', 'woobigpost' ),
                //'default' => __( 'Shipping to your residential address. Terms and Conditions apply.', 'woobigpost' ),
                'default' => __( 'Receive a scheduled home delivery by tailgate truck. Available Monday - Friday', 'woobigpost' ),
                'desc_tip'  => true,
                'css'      => 'width: 400px; max-width: 80%;'
            ),
            'authority_option' => array(
                'title' => __( 'Authority To Leave', 'woobigpost' ),
                'type' => 'select',
                'description' => esc_attr( 'Ask your customers if we are allowed to leave the package in a safe place?', 'woobigpost' ),
                'options' => array(
                    'No' => esc_attr( 'No - No It Must Be Signed For', 'woobigpost' ),
                    'Yes' => esc_attr( 'Yes - Ask My Customer', 'woobigpost' ),
                    'Always' => esc_attr( 'Yes - Always give ATL', 'woobigpost' ),
                ),
                'css' => 'width: auto;'
            ),

            'shipping_hds_atl_description' => array(
                'title' => __( 'Authority To Leave Description', 'woobigpost' ),
                'type' => 'textarea',
                'css'      => 'width: 400px; max-width: 80%;',
                'default' => __( 'Your goods will be left in a safe location nearest to your front door.', 'woobigpost' ),
            ),

            array(
                'title' => __( 'Business Shipping', 'woobigpost' ),
                'type'  => 'title',
                'desc'  => '',
                'id'    => 'business_delivery',
                'class' => 'business_shipping',
            ),
            'shipping_business_label' => array(
                'title' => __( 'Label', 'woobigpost' ),
                'type' => 'text',
                'description' => __( 'Your Big Post Shipping Label to Display on Business Type', 'woobigpost' ),
                'default' => __( 'Delivery to your business', 'woobigpost' ),
                'desc_tip'  => true,
                'custom_attributes' => array( 'readonly' => '' ),
                'class' => 'business_shipping',
            ),
            'shipping_business_description' => array(
                'title' => __( 'Description', 'woobigpost' ),
                'type' => 'textarea',
                'description' => __( 'Your Big Post Shipping Description to Display on Business Type', 'woobigpost' ),
                'default' => __( 'Delivery to a business address Monday - Friday between 9am - 5pm.', 'woobigpost' ),
                'desc_tip'  => true,
                'css'      => 'width: 400px; max-width: 80%;',
                'class' => 'business_shipping'
            ),
            'forklift_option' => array(
                'title' => __( 'Forklifts Option', 'woobigpost' ),
                'type' => 'select',
                'description' => esc_attr( 'Do some of your customers have Forklifts?', 'woobigpost' ),
                'options' => array(
                    'No' => esc_attr( 'No', 'woobigpost' ),
                    'Yes' => esc_attr( 'Yes', 'woobigpost' ),
                ),
                'css' => 'width: auto;',
                'class' => 'business_shipping'
            ),

            array(
                'title' => __( 'Depot Shipping', 'woobigpost' ),
                'type'  => 'title',
                'desc'  => '',
                'id'    => 'depot_delivery',
                'class' => 'depot_shipping',
            ),
            'shipping_depot_label' => array(
                'title' => __( 'Label', 'woobigpost' ),
                'type' => 'text',
                'description' => __( 'Your Big Post Shipping Label to Display on Depot Type', 'woobigpost' ),
                'default' => __( 'Delivery to a depot closest to you', 'woobigpost' ),
                'desc_tip'  => true,
                'custom_attributes' => array( 'readonly' => '' ),
                'class' => 'depot_shipping',
            ),
            'shipping_depot_description' => array(
                'title' => __( 'Description', 'woobigpost' ),
                'type' => 'textarea',
                'description' => __( 'Your Big Post Shipping Description to Display on Depot Type', 'woobigpost' ),
                'default' => __( 'Delivery to a nearest depot for collection. Free Storage for upto 6 working days.</br>(Available for selection in
your shopping cart)', 'woobigpost' ),
                'desc_tip'  => true,
                'css'      => 'width: 400px; max-width: 80%;',
                'class' => 'depot_shipping'
            ),
            array(
                'title' => __( 'Messages', 'woobigpost' ),
                'type'  => 'title',
                'desc'  => '',
                'id'    => 'woobigpost_messages',
                'class' => 'woobigpost_messages'
            ),
            'no_available_shipping' => array(
                'title' => __( 'No Available Shipping', 'woobigpost' ),
                'type' => 'text',
                'description' => __( 'Your Big Post message if there is no available shipping option', 'woobigpost' ),
                'default' => __( 'There are no shipping methods available. Please contact us for a shipping quote.', 'woobigpost' ),
                'desc_tip'  => true,
                'class' => 'no_available_shipping'
            ),
            'business_no_forklift' => array(
                'title' => __( 'Business delivery (no forklift)', 'woobigpost' ),
                'type' => 'text',
                'description' => __( 'Your Big Post message if business delivery but no forklift', 'woobigpost' ),
                'default' => __( 'Your goods will be delivered to your business with a tailgate service.', 'woobigpost' ),
                'desc_tip'  => true,
                'class' => 'business_no_forklift'
            ),

            array( 'type' => 'seperator' ),
            array(
                'title' => __( 'Shipping Mode', 'woobigpost' ),
                'type'  => 'title',
                'desc'  => '',
                'id'    => 'shipping_mode',
                'class' => 'shipping_mode',
            ),
            'is_advanced_mode' => array(
                'title' => __( 'Basic Or Advanced Mode', 'woobigpost' ),
                'type' => 'select',
                'description' => esc_attr( 'Do you consolidate cartons into pallets?', 'woobigpost' ),
                'options' => array(
                    'No' => esc_attr( 'No', 'woobigpost' ),
                    'Yes' => esc_attr( 'Yes', 'woobigpost' ),
                ),
                'css' => 'width: auto;',
            ),
            array( 'type' => 'seperator' ),
            array(
                'title' => __( 'Your Warehouse Location', 'woobigpost' ),
                'type'  => 'title',
                'desc'  => '',
                'id'    => 'warehouse_location_repeater',
            ),
            'bigpost_warehouse_location_repeater' => array(
                'type' => 'warehouse_location_repeater',
                'fields'=>array(
                    'from_name' => array(
                        'title' => __( 'Name', 'bigpost' ),
                        'type' => 'text',
                        'id' => 'from_name',
                        'description' => __( 'Insert your address that will be used for Big Post Shipping as PickUp Location Address', 'bigpost' ),
                        'desc_tip'  => true,
                    ),
                    'from_address' => array(
                        'title' => __( 'Address', 'bigpost' ),
                        'type' => 'text',
                        'description' => __( 'Insert your address that will be used for Big Post Shipping as PickUp Location Address', 'bigpost' ),
                        'default' => __( '832 high street', 'bigpost' ),
                        'desc_tip'  => true,
                    ),
                    'from_suburb' => array(
                        'title' => __( 'Suburb', 'bigpost' ),
                        'type' => 'text',
                        'id' => 'from_suburb_bigpost',
                        'description' => __( 'Type the Suburb name in the input field and click on Find Suburb button to get list of suburbs and select your Suburb. This will be used for Big Post Shipping as PickUp Location Suburb', 'bigpost' ),
                        'default' => __( 'Kew East - 3102', 'bigpost' ),
                        'desc_tip'  => true
                    ),

                    'find_suburb' => array(
                        'type' => 'bigpost_find_suburb',
                    ),
                    'from_state' => array(
                        'type' => 'hidden',
                        'id' => 'from_state',
                    ),
                    'from_suburb_addr' => array(
                        'type' => 'hidden',
                        'id' => 'from_suburb_addr',
                    ),

                    'from_post_code' => array(
                        'type' => 'hidden',
                        'id' => 'from_post_code',
                    ),

                    'from_postcode_id' => array(
                        'type' => 'hidden',
                        'id' => 'from_postcode_id',
                    ),
                )
            ),


            array( 'type' => 'seperator' ),
            array(
                'title' => __( 'Shipping Price', 'woobigpost' ),
                'type'  => 'title',
                'desc'  => '',
                'id'    => 'shipping_price',
            ),
            'shipping_price_round' => array(
                'title' => __( 'Round Shipping Price', 'woobigpost' ),
                'type' => 'select',
                'description' => esc_attr( 'Round Shipping Price based on selected option.', 'woobigpost' ),
                'default' => 'UpDown',
                'desc_tip'  => true,
                'options'  => array(
                    'NoRound' => esc_attr( 'Do Not Round', 'woobigpost' ),
                    'RoundUp' => esc_attr( 'Up To Nearest Dollar', 'woobigpost' ),
                    'RoundDown' => esc_attr( 'Down Nearest Dollar', 'woobigpost' ),
                    'UpDown' => esc_attr( 'Up Or Down', 'woobigpost' ),
                ),
                'css' => 'width: auto;',
            ),

            array(
                'title' => __( 'Shipping Margin', 'woobigpost' ),
                'type'  => 'title',
                'desc'  => '',
                'id'    => 'shipping_margin',
            ),
            'shipping_margin' => array(
                'title' => __( 'Add Margin To Shipping', 'woobigpost' ),
                'type' => 'select',
                'default' => 'Yes',
                'desc_tip'  => true,
                'options'  => array(
                    'No' => esc_attr( 'No', 'woobigpost' ),
                    'Yes' => esc_attr( 'Yes', 'woobigpost' ),
                ),
                'css' => 'width: auto;',
                'class' => 'is_shipping_margin',
            ),
            'margin_type' => array(
                'title' => __( 'Shipping Mode', 'woobigpost' ),
                'type' => 'select',
                'default' => 'Simple',
                'options'  => array(
                    'Simple' => 'Simple',
                    'Advanced' => 'Advanced'
                ),
                'css' => 'width: auto; min-width: 170px;',
                'class' => 'shipping_margin',
            ),
            'margin_action' => array(
                'title' => __( 'Margin Action:', 'woobigpost' ),
                'type' => 'select',
                'default' => 'Add',
                'options'  => array(
                    'Add' => 'Add',
                    'Subtract' => 'Subtract'
                ),
                'css' => 'width: auto; min-width: 170px;',
                'class' => 'shipping_margin',
            ),
            'margin_fixed_percent' => array(
                'title' => __( 'Margin Type:', 'woobigpost' ),
                'type' => 'select',
                'default' => 'Fixed',
                'options'  => array(
                    '$' => 'Fixed',
                    '%' => 'Percentage'
                ),
                'css' => 'width: auto; min-width: 170px;',
                'class' => 'shipping_margin',
            ),
            'simple_margin_value' => array(
                'title' => esc_attr( 'Margin Value', 'woobigpost' ),
                'type' => 'number',
                'default' => '0',
                'class' => 'small-text',
                'css' => 'width: auto;',
                'class' => 'shipping_margin',
            ),
            'advanced_margin_values' => array(
                'type' => 'margin_value_advanced',
            ),
            array(
                'title' => __( 'Quick Quote Positions', 'woobigpost' ),
                'type'  => 'title',
                'desc'  => '',
                'id'    => 'quick_quote_positions',
            ),
            'product_page' => array(
                'label'     =>'Product Page',
                'type'      => 'radio',
                'default'   => 'popup',
                'options'   => array(
                    'hide' => 'Hide quick quote on the product page',
                    'popup' => 'Display in a pop up',
                    'on_page' => 'Display on the page itself'
                )
            ),
            'popup_button_text' => array(
                'title' => __( 'Popup Button text', 'woobigpost' ),
                'type' => 'text',
                'description' => __( '' ),
                'default' => __( 'Quick Shipping Quote' ),
                'desc_tip'  => true,
                'class' => 'no_available_shipping'
            ),
            'product_position' => array(
                'title' => __( 'Position', 'woobigpost' ),
                'type' => 'select',
                'default' => 'woocommerce_after_single_product_summary',
                'desc_tip'  => true,
                'options'  => array(
                    'woocommerce_after_single_product_summary' => esc_attr( 'woocommerce_after_single_product_summary', 'woobigpost' ),
                    'woocommerce_before_add_to_cart_form' => esc_attr( 'woocommerce_before_add_to_cart_form', 'woobigpost' ),
                    'woocommerce_before_add_to_cart_button' => esc_attr( 'woocommerce_before_add_to_cart_button', 'woobigpost' ),
                    'woocommerce_after_add_to_cart_button' => esc_attr( 'woocommerce_after_add_to_cart_button', 'woobigpost' ),
                    'woocommerce_product_meta_start' => esc_attr( 'woocommerce_product_meta_start', 'woobigpost' ),
                    'woocommerce_product_meta_end' => esc_attr( 'woocommerce_product_meta_end', 'woobigpost' ),
                    'woocommerce_after_single_product' => esc_attr( 'woocommerce_after_single_product', 'woobigpost' ),
                ),
                'css' => 'width: auto;'
            ),
            'cart_position' => array(
                'title' => __( 'Cart Page', 'woobigpost' ),
                'type' => 'select',
                'default' => 'woocommerce_cart_totals_before_shipping',
                'desc_tip'  => true,
                'options'  => array(
                    'woocommerce_cart_totals_before_shipping' => esc_attr( 'woocommerce_cart_totals_before_shipping', 'woobigpost' ),
                    'woocommerce_after_cart_contents' => esc_attr( 'woocommerce_after_cart_contents', 'woobigpost' ),
                    'woocommerce_after_cart_table' => esc_attr( 'woocommerce_after_cart_table', 'woobigpost' ),
                    'woocommerce_cart_collaterals' => esc_attr( 'woocommerce_cart_collaterals', 'woobigpost' ),
                    'woocommerce_before_cart_totals' => esc_attr( 'woocommerce_before_cart_totals', 'woobigpost' ),
                    'woocommerce_cart_totals_after_shipping' => esc_attr( 'woocommerce_cart_totals_after_shipping', 'woobigpost' ),
                    'woocommerce_proceed_to_checkout' => esc_attr( 'woocommerce_proceed_to_checkout', 'woobigpost' ),
                ),
                'css' => 'width: auto;'
            ),
            'checkout_position' => array(
                'title' => __( 'Checkout Page', 'woobigpost' ),
                'type' => 'select',
                'default' => 'woocommerce_checkout_after_customer_details',
                'desc_tip'  => true,
                'options'  => array(
                    'woocommerce_checkout_after_customer_details' => esc_attr( 'woocommerce_checkout_after_customer_details', 'woobigpost' ),
                    'woocommerce_before_checkout_shipping_form' => esc_attr( 'woocommerce_before_checkout_shipping_form', 'woobigpost' ),
                    'woocommerce_after_checkout_shipping_form' => esc_attr( 'woocommerce_after_checkout_shipping_form', 'woobigpost' ),
                    'woocommerce_before_order_notes' => esc_attr( 'woocommerce_before_order_notes', 'woobigpost' ),
                    'woocommerce_after_order_notes' => esc_attr( 'woocommerce_after_order_notes', 'woobigpost' ),
                    'woocommerce_checkout_before_order_review' => esc_attr( 'woocommerce_checkout_before_order_review', 'woobigpost' ),
                    'woocommerce_review_order_before_shipping' => esc_attr( 'woocommerce_review_order_before_shipping', 'woobigpost' ),
                    'woocommerce_review_order_after_shipping' => esc_attr( 'woocommerce_review_order_after_shipping', 'woobigpost' )
                ),
                'css' => 'width: auto;'
            ),
            /*array(
                'title' => __( 'Quick Quote Box Visibility', 'woobigpost' ),
                'type'  => 'title',
                'desc'  => '',
                'id'    => 'quick_quote_visibility',
            ),
            'hide_product_page' => array(
                'title' => esc_attr( 'Hide on Product Page', 'woobigpost' ),
                'type' => 'checkbox',
                'label' => __( 'Check this to hide quick quote box on Product page', 'woobigpost' ),
                'default' => 'no',
                'id' => 'hide_cart_page'

            ),*/

            array( 'type' => 'seperator' ),
            array(
                'title' => __( 'Free Shipping', 'woobigpost' ),
                'type'  => 'title',
                'desc'  => '',
                'id'    => 'quick_quote_visibility',
            ),
            'free_shipping_config' => array(
                'label'     =>'Free Shipping Configuration',
                'type'      => 'radio',
                'default'   => 'disabled',
                'options'   => array(
                    'disabled' => 'Disable Free Shipping',
                    'enable_all' => 'Enable Free Shipping on all items',
                    'enable_specific_items' => 'Enable Free Shipping on the items I select'
                )
            ),
            'free_shipping_min_order_value' => array(
                'title' => esc_attr( 'Minimum Order Value for Free Shipping (optional)', 'woobigpost' ),
                'type' => 'number',
                'default' => '0',
                'min'=>0
            ),
            'free_shipping_max_freight_cost' => array(
                'title' => esc_attr( 'Maximum Shipping Cost To qualify for Free Shipping (optional)', 'woobigpost' ),
                'type' => 'number',
                'default' => '0',
                'min'=>0
            ),
            'free_shipping_freight_cost_condition' => array(
                'label'     =>'If the maximum shipping cost is exceeded, we will',
                'type'      => 'radio',
                'options'   => array(
                    'charge_full_amount' => 'Charge the full amount of freight.',
                    'charge_over_limit' => 'Charge only the amount over the maximum limit.'
                )
            ),
            'free_shipping_mixed_cart_option' => array(
                'label'     =>'How should we present and charge shipping to customers if they have both free and non-free items in their cart?',
                'type'      => 'radio',
                'options'   => array(
                    'charge_non_free' => 'Charge the freight cost of the non-free items only.',
                    'free' => 'It is free.'
                )
            ),
            array( 'type' => 'seperator' ),
            array(
                'title' => __( 'Order Pushing Stage', 'woobigpost' ),
                'type'  => 'title',
                'desc'  => '',
                'id'    => 'order_pushing_stage',
            ),
            'order_trigger_status' => array(
                'title'     => __( 'Sent the order to BigPost once they achieve these status', 'woobigpost' ),
                'type'      => 'multicheckbox',
                'description'  => __( 'Sent the order to Big Post when it reaches the above order stages.', 'woobigpost' ) . "<br>" . __( 'Note, "processing" is the default woocommerce new order stage', 'woobigpost' ),
                'default'   => array( 'processing', 'on-hold'),
                'options'   =>$order_status_opt
            ),
            array( 'type' => 'seperator' ),
            array(
                'title' => __( 'Reorder Checkout Fields', 'woobigpost' ),
                'type'  => 'title',
                'desc'  => '',
                'id'    => 'checkout_order_fields',
            ),
            'postcode_field_order' => array(
                'title' => esc_attr( 'Put Postcode before Suburb & State', 'woobigpost' ),
                'type' => 'checkbox',
                'label' => __( '&nbsp;', 'woobigpost' ),
                'default' => 'no',
                'id' => 'postcode_field_order'
            ),
            'limit_phone' => array(
                'title' => esc_attr( 'Limit Phone to 10 numbers', 'woobigpost' ),
                'type' => 'checkbox',
                'label' => __( '&nbsp;', 'woobigpost' ),
                'default' => 'no',
                'id' => 'limit_phone'
            ),
            'leave_rating'=>array(
                'type'=>'leave_rating'
            ),
            array( 'type' => 'seperator' ),
            array(
                'title' => __( 'Tax Settings', 'woobigpost' ),
                'type'  => 'title',
                'desc'  => '',
                'id'    => 'tax_settings',
            ),
            'tax_exclusive_widget' => array(
                'title' => esc_attr( 'Exclude tax from widget', 'woobigpost' ),
                'type' => 'checkbox',
                'label' => __( '&nbsp;', 'woobigpost' ),
                'default' => 'no',
                'id' => 'tax_exclusive_widget'
            ),
            'tax_exclusive_cart' => array(
                'title' => esc_attr( 'Exclude tax from inserted price in cart/checkout', 'woobigpost' ),
                'type' => 'checkbox',
                'label' => __( '&nbsp;', 'woobigpost' ),
                'default' => 'no',
                'id' => 'tax_exclusive_cart'
            ),
            
            array( 'type' => 'seperator' ),
            
            'order_sync_only' => array(
                'title' => __( 'Order Sync Only', 'woobigpost' ),
                'type' => 'select',
                'description' => esc_attr( 'Disable live rates but syncs order to bigpost.', 'woobigpost' ),
                'default' => 'No',
                'options' => array(
                    'No' => esc_attr( 'No', 'woobigpost' ),
                    'Yes' => esc_attr( 'Yes', 'woobigpost' ),
                ),
                'css' => 'width: auto;'
            ),
            
            'default_carrier_id' => array(
                'title' => __( 'Carrier ID', 'woobigpost' ),
                'type' => 'text',
                'description' => '',
                'class' => 'order_sync_only_fields'
            ),
            
            'default_service_code' => array(
                'title' => __( 'Service Code', 'woobigpost' ),
                'type' => 'text',
                'description' => '',
                'class' => 'order_sync_only_fields'
            ),
            
            'default_shipping_type' => array(
                'title' => __( 'Shipping Type', 'woobigpost' ),
                'type' => 'select',
                'description' => '',
                'default' => 'HDS',
                'options' => array(
                    'HDS' => esc_attr( 'HDS', 'woobigpost' ),
                    'BUSINESS' => esc_attr( 'BUSINESS', 'woobigpost' ),
                    'DEPOT' => esc_attr( 'DEPOT', 'woobigpost' ),
                ),
                'css' => 'width: auto;',
                'class' => 'order_sync_only_fields'
            ),
            
            'default_leave_auth' => array(
                'title' => __( 'Authority To Leave', 'woobigpost' ),
                'type' => 'select',
                'description' => '',
                'default' => 'No',
                'options' => array(
                    '0' => esc_attr( 'No', 'woobigpost' ),
                    '1' => esc_attr( 'Yes', 'woobigpost' ),
                ),
                'css' => 'width: auto;',
                'class' => 'order_sync_only_fields'
            ),
            
            'default_has_forklift' => array(
                'title' => __( 'Has Forklift', 'woobigpost' ),
                'type' => 'select',
                'description' => '',
                'default' => 'No',
                'options' => array(
                    '0' => esc_attr( 'No', 'woobigpost' ),
                    '1' => esc_attr( 'Yes', 'woobigpost' ),
                ),
                'css' => 'width: auto;',
                'class' => 'order_sync_only_fields'
            ),

            'default_warehouse' => array(
                'title' => __( 'Select Warehouse', 'woobigpost' ),
                'type' => 'select',
                'description' => '',
                'default' => (isset($warehouse_names[0]))?$warehouse_names[0]:'',
                'options' => $warehouse_names,
                'css' => 'width: auto;',
                'class' => 'order_sync_only_fields'
            ),
            
            array( 'type' => 'seperator' ),

            /*'show_all_options' => array(
                'title' => __( 'Hide checkout widget and show all options', 'woobigpost' ),
                'type' => 'select',
                //'description' => esc_attr( 'Disable live rates but syncs order to bigpost.', 'woobigpost' ),
                'default' => 'No',
                'options' => array(
                    'No' => esc_attr( 'No', 'woobigpost' ),
                    'Yes' => esc_attr( 'Yes', 'woobigpost' ),
                ),
                'css' => 'width: auto;'
            ),*/

            'display_eta' => array(
                'title' => __( 'Display ETA in checkout', 'woobigpost' ),
                'type' => 'select',
                //'description' => esc_attr( 'Disable live rates but syncs order to bigpost.', 'woobigpost' ),
                'default' => 'No',
                'options' => array(
                    'No' => esc_attr( 'No', 'woobigpost' ),
                    'Yes' => esc_attr( 'Yes', 'woobigpost' ),
                ),
                'css' => 'width: auto;'
            ),
            'eta_margin' => array(
                'title' => esc_attr( 'ETA Additional days', 'woobigpost' ),
                'type' => 'number',
                'default' => '0',
                'min'=>0
            ),
            
            array( 'type' => 'seperator' ),

            'restrict_carriers' => array(
                'title' => __( 'Restrict Carriers', 'woobigpost' ),
                'type' => 'select',
                //'description' => esc_attr( 'Disable live rates but syncs order to bigpost.', 'woobigpost' ),
                'default' => 'No',
                'options' => array(
                    'No' => esc_attr( 'No', 'woobigpost' ),
                    'Yes' => esc_attr( 'Yes', 'woobigpost' ),
                ),
                'css' => 'width: auto;'
            ),
            
            array( 'type' => 'seperator' ),
        );
        
        $itemtypes = array(
            'Carton'    => 0,
            'Skid'      => 1,
            'Pallet'    => 2,
            'Pack'      => 3,
            'Crate'     => 4,
            'Roll'      => 5,
            'Satchel'   => 6,
            'Stillage'  => 7,
            'Tube'      => 8,
            'Bag'       => 9
        );

        $itemtypes_opt = array();
        $itemtypes_default = array();
        foreach($itemtypes as $label=>$val){
            $itemtypes_opt[] = array('value'=>$val,'label'=>$label);
            $itemtypes_default[] = $val;
        }

        $carrier_options = array();
        foreach($carriers['Object'] as $i=>$a){
            $carrier_options[$i] = esc_attr( $a[0]['CarrierName'], 'woobigpost' );
        }

        $form_fields['carrier_list'] = array(
            'title' => __( 'Select Carrier', 'woobigpost' ),
            'type' => 'select',
            //'description' => esc_attr( 'Disable live rates but syncs order to bigpost.', 'woobigpost' ),
            'default' => 'No',
            'options' => $carrier_options,
            'css' => 'width: auto;',
            'class' => 'restrict_carriers_fields'
        );
        
        foreach($carriers['Object'] as $k=>$c){
    
            $form_fields['restrict_carrier_'.$k] = array(
                'title' => 'Allow Carrier to Quote',
                'description' => esc_attr( 'If this option is selected, return quotes for the selected carrier ('.$c[0]['CarrierName'].').', 'woobigpost' ),
                'type' => 'checkbox',
                'label' => __( '&nbsp;', 'woobigpost' ),
                'default' => 'no',
                'id' => 'restrict_carrier_'.$k,
                'class' => 'restrict_items_fields'
            );
            
            $form_fields['allowed_items_'.$k] = array(
                'title'     => __( ' Allowed Item Types <br /><small><a href="#selectall" class="selectall-itemtypes" data-id="'.$k.'" >Select All</a><br /><a href="#deselectall" class="deselectall-itemtypes" data-id="'.$k.'">Deselect All</a></small>', 'woobigpost' ),
                'type'      => 'multicheckbox',
                'options'   => $itemtypes_opt,
                'id' => 'restrict_items_types_'.$k,
                'class' => 'restrict_items_fields',
                'description' => esc_attr( 'Items selected above will be allowed to return for a quote price for the selected carrier ('.$c[0]['CarrierName'].').', 'woobigpost' ),
            );
            
            //$form_fields[] = array( 'type' => 'seperator2');
        }
        //$form_fields[] = $restrict_carriers;
        
        $this->form_fields = $form_fields;
    }

    /**
     * Seperator HTML
     */
    public function generate_seperator2_html() {
        ob_start(); ?>
        <tr class="seprater restrict_carriers_fields"><td colspan="2" style="padding: 0;"><hr></td></tr>
        <?php
        return ob_get_clean();
    }

    public function generate_seperator_html() {
        ob_start(); ?>
        <tr class="seprater"><td colspan="2" style="padding: 0;"><hr></td></tr>
        <?php
        return ob_get_clean();
    }

    /**
     * Hidden field
     */
    public function generate_hidden_html( $key, $data ) {

        $field_key = $this->get_field_key( $key );

        ob_start(); ?>

        <input class="<?php echo esc_attr( $data['class'] ); ?>" type="<?php echo esc_attr( $data['type'] ); ?>" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>"  value="<?php echo esc_attr( $this->get_option( $key ) ); ?>" />

        <?php
        return ob_get_clean();
    }


    /**
     * Multiple checkboxes
     */
    public function generate_multicheckbox_html( $key, $data ) {

        $field_key = $this->get_field_key( $key );
        $data['desc_tip'] = isset( $data['desc_tip'] ) ? $data['desc_tip'] : '';

        ob_start(); ?>

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data ); // WPCS: XSS ok. ?></label>
            </th>
            <td class="forminp">
                <?php
                $values = $this->get_option( $key );
                if( empty($values) && !empty($data['default']) ) {
                    $values = $data['default'];
                }
                if(empty($values)){
                    $values = array();
                }

                foreach( $data['options'] as $key => $option ) {
                    $disabled = isset( $option['disabled'] ) ? $option['disabled'] : false; 

                    if(!isset($data['class'])){
                        $data['class'] = '';
                    }
                ?>

                    <fieldset class="multicheckbox">
                        <label for="<?php echo esc_attr( $field_key ).'-'.$key; ?>">
                            <input class="<?php echo esc_attr( $field_key ); ?> <?php echo esc_attr( $data['class'] ); ?>" type="checkbox" name="<?php echo esc_attr( $field_key ); ?>[]" id="<?php echo esc_attr( $field_key ).'-'.$key; ?>" value="<?php echo esc_attr( $option['value'] ); ?>" <?php checked( in_array($option['value'], $values) ); ?> <?php if( $disabled ) echo "onclick='return false;' readonly"; ?> /> <?php echo wp_kses_post( $option['label'] ); ?></label>
                    </fieldset>
                <?php } ?>
                <?php echo $this->get_description_html( $data ); // WPCS: XSS ok. ?>
            </td>
        </tr>

        <?php
        return ob_get_clean();
    }

    /**
     * Validate checkboxes fields
     */
    public function validate_multicheckbox_field( $key, $value ) {
        return $value;
    }

    /**
     * Suburb HTML
     */
    public function generate_bigpost_find_suburb_html(){
        ob_start(); ?>
        <tr valign="top">
            <th></th>
            <td>
                <style>
                    div#from_suburb_list{width: 350px;margin-top: 15px;}
                    div#from_suburb_list li{ display: block; border: 1px solid #ccc; padding: 7px 10px;}
                    div#from_suburb_list li:hover{ background: #ffffff; cursor: pointer; }
                </style>

                <button id="bigpost_find_suburb" class="button button-primary">Find Suburb</button>

                <img class="loader-quote-product" id="loader-quote-product" src="<?php echo admin_url(); ?>images/spinner.gif" alt="<?php _e("loading...", 'quote_estimator_plugin'); ?>" title="<?php _e("loading...", 'quote_estimator_plugin'); ?>" style="display: none;" />

                <div id="from_suburb_list"></div>
            </td>
        </tr>

        <?php
        return ob_get_clean();
    }

    /**
     * Warehouse Repeater HTML
     */
    public function generate_warehouse_location_repeater_html($key, $data){
        $location_repeater = $this->get_option('bigpost_warehouse_locations');
        ob_start();

        ?>
        <style>
            div.from_suburb_list{width: 200px;display: inline-block}
            div.from_suburb_list li{ display:block; border: 1px solid #ccc; padding: 7px 10px;}
            div.from_suburb_list li:hover{ background: #ffffff; cursor: pointer;vertical-align: top; }
            .bigpost_find_suburb {display: inline-block}
            .loader-quote-product{display: inline-block;vertical-align:bottom; }
            .form-table td.vtop{
                vertical-align: top !important;
            }
        </style>
        <script>
            jQuery(document).ready(function(){
                jQuery('.shipping_mode').hide();
                jQuery('.shipping_mode').next().hide();
            })
        </script>
        <?php
        if(isset($data['fields']) && !empty($data['fields']) && empty($location_repeater)){
            ?>
            <tr valign="top" class="row_loc default_loc">
                <?php
                foreach($data['fields'] as $key2=>$field){
                    $name = $key."[$key2][]";
                    switch($field['type']){
                        case 'text':
                            echo "<td style='width:22%' class='vtop'><input style='width:240px;' type='text' placeholder='".$field['title']."' name='".$name."' class='".$key2."' required/></td>";
                            break;
                        case 'hidden':
                            echo "<input type='hidden' value='' name='".$name."' class='".$key2."'/>";
                            break;
                        case 'bigpost_find_suburb':
                            ?>
                            <td class='vtop'>
                                <input type='hidden' class="key_val" name='<?php echo $key."[key_val][]"  ?>' value='0'/>
                                <button class="button button-primary bigpost_find_suburb">Find Suburb</button>
                                <img class="loader-quote-product" src="<?php echo admin_url(); ?>images/spinner.gif" alt="<?php _e("loading...", 'quote_estimator_plugin'); ?>" title="<?php _e("loading...", 'quote_estimator_plugin'); ?>" style="display: none;" />
                                <div class="from_suburb_list"></div>
                            </td>
                            <?php
                            break;
                    }
                    ?>
                <?php
                } ?>
            </tr>
        <?php } else {
            $i = 0;
            if(!empty($location_repeater)){
                foreach($location_repeater as $id=>$loc){
                    ?>
                    <tr valign="top" class="row_loc">
                        <?php

                        foreach($data['fields'] as $key2=>$field){
                            $name = $key."[$key2][]";
                            switch($field['type']){
                                case 'text':
                                    echo "<td style='width:22%' class='vtop'><input style='width:240px;' type='text' placeholder='".$field['title']."' name='".$name."' value='".$loc[$key2]."' class='".$key2."' required/></td>";
                                    break;
                                case 'hidden':
                                    echo "<input type='hidden' name='".$name."' value='".$loc[$key2]."' class='".$key2."'/>";
                                    break;
                                case 'bigpost_find_suburb':
                                    ?>
                                    <td class='vtop'>
                                        <input type='hidden' class="key_val" name='<?php echo $key."[key_val][]"  ?>' value='<?php echo $id; ?>'/>
                                        <?php if($i > 0){?><button class="button button-primary remove_location">&minus;</button><?php } ?>
                                        <button class="button button-primary bigpost_find_suburb">Find Suburb</button>
                                        <img class="loader-quote-product" src="<?php echo admin_url(); ?>images/spinner.gif" alt="<?php _e("loading...", 'quote_estimator_plugin'); ?>" title="<?php _e("loading...", 'quote_estimator_plugin'); ?>" style="display: none;" />
                                        <div class="from_suburb_list"></div>
                                    </td>
                                    <?php
                                    break;
                            }

                        }
                        ?>
                    </tr>
                    <?php
                    $i++; } //end foreach locations
            } //end if
        }
        ?>
        <tr>
            <td colspan="3"><button class="button button-primary add_location_repeater">+ Add Location</button></td>
        </tr>
        <?php
        return ob_get_clean();
    }

    public function generate_leave_rating_html($key,$value){
        ob_start();
        require_once( WOO_BIGPOST_DIR . '/includes/admin/forms/templates/leave_rating.php' );
        return ob_get_clean();

    }

    public function generate_getting_started_html($key, $value){
        $api = $this->plugin_check->api_creds ? '&#10004;' : '&#10060;';
        $pickup_locations = $this->plugin_check->pickup_locations ? '&#10004;' : '&#10060;';
        $product_carton = $this->plugin_check->product_cartons ? '&#10004;' : '&#10060;';
        $woocommerce_version = $this->plugin_check->woocommerce_version ? '&#10004;' : '&#10060;';

        $strike_api = $this->plugin_check->api_creds ? 'strike' : '';
        $strike_location = $this->plugin_check->pickup_locations ? 'strike' : '';
        $strike_carton = $this->plugin_check->product_cartons ? 'strike' : '';
        $strike_woocommerce_version = $this->plugin_check->woocommerce_version ? 'strike' : '';
        if(!$this->plugin_check->api_creds || !$this->plugin_check->pickup_locations || !$this->plugin_check->product_cartons || !$this->plugin_check->woocommerce_version){
            ob_start();

            ?>
            <tr valign="top" class="bigpost_getting_started">
                <td scope="row" class="titledesc">
                    <?php _e( '<span>'.$woocommerce_version.'</span><label class="'.$strike_woocommerce_version.'"> Woocommerce version.</label>', 'woobigpost' ); ?>
                    <?php if (isset($this->plugin_check->plugin_setup_messages['api_woocommerce_version'])) { ?><div class="bigpost_notif bigpost_error"><p class="bigpost_message"><?php echo $this->plugin_check->plugin_setup_messages['api_woocommerce_version']; ?></p></div><?php } ?>
                </td>
            </tr>
            <tr valign="top" class="bigpost_getting_started">
                <td scope="row" class="titledesc">
                    <?php _e( '<span>'.$api.'</span><label class="'.$strike_api.'"> Input your API details - don\'t know what these are? Contact Big Post for your very own details today.</label>', 'woobigpost' ); ?>
                    <?php if (isset($this->plugin_check->plugin_setup_messages['api_creds'])) { ?><div class="bigpost_notif bigpost_error"><p class="bigpost_message"><?php echo $this->plugin_check->plugin_setup_messages['api_creds']; ?></p></div><?php } ?>
                </td>
            </tr>
            <tr  valign="top" class="bigpost_getting_started">
                <td scope="row" class="titledesc">
                    <?php _e( '<span>'.$pickup_locations.'</span><label class="'.$strike_location.'"> Create your first <a href="#" id="moveto_location">FROM pick up location</a> - you can add as many as you like, we will display the priced based on the cheapest quote for all locations.</label>', 'woobigpost' ); ?>
                    <?php if (isset($this->plugin_check->plugin_setup_messages['pickup_locations'])) { ?><div class="bigpost_notif bigpost_error"><p class="bigpost_message"><?php echo $this->plugin_check->plugin_setup_messages['pickup_locations']; ?></p></div><?php } ?>
                </td>
            </tr>
            <tr  valign="top" class="bigpost_getting_started">
                <td scope="row" class="titledesc">
                    <?php _e(  '<span>'.$product_carton.'</span><label class="'.$strike_carton.'"> Add all your product box sizes - there is a specific Big Post carton settings section that will show on ALL products. You can also use our Box Migration tool if you have already input your box sizes into the default WooCommerce size fields.</label>', 'woobigpost' ); ?>
                    <?php if (isset($this->plugin_check->plugin_setup_messages['api_product_cartons'])) { ?><div class="bigpost_notif bigpost_error"><p class="bigpost_message"><?php echo $this->plugin_check->plugin_setup_messages['api_product_cartons']; ?></p></div><?php } ?>
                </td>
            </tr>
            <?php
            return ob_get_clean();
        }
    }

    /**
     * Advance Margin
     */
    public function generate_margin_value_advanced_html( $key, $data ) {

        $field_key = $this->get_field_key( $key );

        $advanced_margins = $this->get_option( $key );

        ob_start(); ?>

        <tr valign="top" id="advanced_margin_table">
            <th scope="row" class="titledesc"><?php _e( 'Advanced Margin', 'woobigpost' ); ?>:</th>
            <td class="forminp" id="advanced_bigpost_margin">
                <table class="" cellspacing="0">
                    <thead>
                    <tr>
                        <th><?php _e( 'No. of Cartons', 'woobigpost' ); ?></th>
                        <th><?php _e( 'Margin Value', 'woobigpost' ); ?></th>
                    </tr>
                    </thead>
                    <tbody class="margins">
                    <?php
                    $i = 0;
                    if( $advanced_margins ) {
                        foreach( $advanced_margins as $margin ) {

                            $range_from = isset($margin['range_from']) ? $margin['range_from'] : '';
                            $range_end  = isset($margin['range_end']) ? $margin['range_end'] : '5';
                            $value      = isset($margin['value']) ? $margin['value'] : '1';
                            $start_text = ( $margin['range_end'] == '' ) ? 'Above' : $range_from; ?>

                            <tr class="margin">
                                <td>[<?php echo $start_text; ?> - <?php echo ($range_end != '') ? $range_end : $range_from; ?>]</td>
                                <td>
                                    <input type="hidden" class="range-from" name="<?php echo esc_attr( $field_key ); ?>[<?php echo $i; ?>][range_from]" value="<?php echo esc_attr( $range_from ); ?>" />
                                    <input type="hidden" class="range-end" name="<?php echo esc_attr( $field_key ); ?>[<?php echo $i; ?>][range_end]" value="<?php echo $range_end; ?>" />
                                    <input type="number" data-index="<?php echo $i; ?>" data-range="<?php echo $range_from; ?>" min="1" name="<?php echo esc_attr( $field_key ); ?>[<?php echo $i; ?>][value]" value="<?php echo $value; ?>">
                                </td>
                            </tr>

                            <?php
                            $i++;
                        }
                    } else { ?>
                        <tr>
                            <td>[0 - 5]</td>
                            <td>
                                <input type="hidden" class="range-from" name="<?php echo esc_attr( $field_key ); ?>[0][range_from]" value="0" />
                                <input type="hidden" class="range-end" name="<?php echo esc_attr( $field_key ); ?>[0][range_end]" value="5" />
                                <input type="number" data-index="0" data-range="0" min="1" name="<?php echo esc_attr( $field_key ); ?>[0][value]" value="1">
                                <span class="curent-range">X</span>
                            </td>
                        </tr>

                        <tr>
                            <td>[Above - 5]</td>
                            <td>
                                <input type="hidden" class="range-from" name="<?php echo esc_attr( $field_key ); ?>[1][range_from]" value="5" />
                                <input type="hidden" class="range-end" name="<?php echo esc_attr( $field_key ); ?>[1][range_end]" value="" />
                                <input type="number" data-index="1" data-range="5" min="1" name="<?php echo esc_attr( $field_key ); ?>[1][value]" value="1">
                                <span class="curent-range">X</span>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <th colspan="7"><a href="#" class="add button"><?php _e( '+ Add More', 'woobigpost' ); ?></a></th>
                    </tr>
                    </tfoot>
                </table>

            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    public function generate_radio_html($key, $data){
        $field_key = $this->get_field_key( $key );
        $data['desc_tip'] = isset( $data['desc_tip'] ) ? $data['desc_tip'] : '';


        ob_start(); ?>

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['label'] ); ?> <?php echo $this->get_tooltip_html( $data ); // WPCS: XSS ok. ?></label>
            </th>
            <td class="forminp">
                <?php
                $values = $this->get_option( $key );
                if( empty($values) && !empty($data['default']) ) {
                    $values = $data['default'];
                }

                foreach( $data['options'] as $key => $option ) {
                    if(!isset($data['class'])){
                        $data['class'] = "";
                    }
                    ?>
                    <fieldset class="radio">
                        <label for="<?php echo esc_attr( $field_key ).'-'.$key; ?>">
                            <input class="<?php echo esc_attr( $field_key ); ?> <?php echo esc_attr( $data['class'] ); ?>" type="radio" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ).'-'.$key; ?>" value="<?php echo esc_attr($key); ?>" <?php checked( $key, $values); ?> /> <?php echo wp_kses_post( $option ); ?></label>
                    </fieldset>
                <?php } ?>
                <?php echo $this->get_description_html( $data ); // WPCS: XSS ok. ?>
            </td>
        </tr>

        <?php
        return ob_get_clean();
    }

    /**
     * Validate to remove default validations
     */
    public function validate_margin_value_advanced_field( $key, $value ) {
        return $value;
    }

    /* Save Custom Build Options */
    public function woobigpost_save_custom_options() {

        // Check if this settings page is call
        if( !isset($_GET['section']) || $_GET['section'] != 'woobigpost' ) {
            return;
        }

        // Save shippins types
        if( isset($_POST['woocommerce_woobigpost_shipping_types']) ) {
            $shipping_types = array_map( 'sanitize_text_field', $_POST['woocommerce_woobigpost_shipping_types'] );
            $this->update_option( 'shipping_types', $shipping_types);
        }

        if( isset($_POST['bigpost_warehouse_location_repeater']) ) {
            $location_arr = array();
            $from_suburb = array_map( 'sanitize_text_field', $_POST['bigpost_warehouse_location_repeater']['from_suburb']);
            foreach($from_suburb as $key=>$location){
                $location_arr[$_POST['bigpost_warehouse_location_repeater']['key_val'][$key]] = array(
                    'from_name'=> sanitize_text_field($_POST['bigpost_warehouse_location_repeater']['from_name'][$key]),
                    'from_address'=> sanitize_text_field($_POST['bigpost_warehouse_location_repeater']['from_address'][$key]),
                    'from_suburb'=> $location,
                    'from_state'=> sanitize_text_field($_POST['bigpost_warehouse_location_repeater']['from_state'][$key]),
                    'from_suburb_addr'=> sanitize_text_field($_POST['bigpost_warehouse_location_repeater']['from_suburb_addr'][$key]),
                    'from_post_code'=> sanitize_text_field($_POST['bigpost_warehouse_location_repeater']['from_post_code'][$key]),
                    'from_postcode_id'=> sanitize_text_field($_POST['bigpost_warehouse_location_repeater']['from_postcode_id'][$key]),
                );
            }

            $this->update_option( 'bigpost_warehouse_locations', $location_arr );

        }

        $plugin_check = new Woo_BigPost_Plugin_Setup_Check();

        $plugin_setup = array(
            'api_creds'=> $plugin_check->api_creds ? 'true': 'false',
            'pickup_locations'=>$plugin_check->pickup_locations? 'true': 'false',
            'product_cartons'=>$plugin_check->product_cartons? 'true': 'false'
        );

        $this->update_option( 'plugin_setup', $plugin_setup);
    }

    /**
     * This function is used to calculate the shipping cost. Within this function we can check for weights, dimensions and other parameters.
     *
     * @access public
     * @param mixed $package
     * @return void
     */
    public function calculate_shipping( $package = array() ) {
        global $woocommerce;
        $settings = woobigpost_get_plugin_settings();

        if($settings['use_cis'] == "yes"){

            $destination = array(
                "postcode" => $package['destination']['postcode'],
                "state" => $package['destination']['state'],
                "city" => $package['destination']['city']
            );

            $cart_items = [];
            
            foreach($package['contents'] as $content){
                $cart_items[] = ["id"=>$content['product_id'], "qty"=>$content['quantity']];
            }
            
            $prev_address_hash = WC()->session->get('cis_address_hash');
            $prev_cart_hash = WC()->session->get('cis_cart_hash');
            $address_hash = hash('md5', json_encode($destination));
            $cart_hash = hash('md5', json_encode($cart_items));
            
            if($address_hash == $prev_address_hash && $cart_hash == $prev_cart_hash){
                $quotes = WC()->session->get('cis_rates');
            }else{
                $quotes = $this->get_cis_quotes($package);

                WC()->session->set('cis_address_hash', $address_hash);
                WC()->session->set('cis_cart_hash', $cart_hash);
                WC()->session->set('cis_rates', $quotes);
            }
            
            WC()->customer->set_calculated_shipping( true );
            
            foreach($quotes as $rate){
                $this->add_rate($rate);
            }   
            
        }else{
            $plugin_setup_check = woobigpost_get_plugin_settings();
            $items = $woocommerce->cart->get_cart();
            $disabled_items = count_disabled_items($items,$plugin_setup_check);

            if($disabled_items < count($items)){
                $rates = $this->send_bigpost_api_request();

                if( !empty($rates) ){
                    foreach ( $rates as $rate ) {
                        $this->add_rate( $rate );
                    }
                }
            }
        }

    }

    /* Get cart items so that we can send create a array to send in API request */
    public function get_cart_items_to_get_pricing($items){
        $settings = woobigpost_get_plugin_settings();
        $shipping_types = $settings['shipping_types'];
        $shipping_types_default = isset($this->form_fields['shipping_types']['default']) ? $this->form_fields['shipping_types']['default']: array();
        if( empty($shipping_types) && !empty( $shipping_types_default) ) {
            $shipping_types = $shipping_types_default;
        }

        $can_consolidated = $settings['is_advanced_mode'];

        $data_items = array();

        foreach( $items as $item ) {
            $parent_product = $item['product_id'];

            $show_plugin        = get_post_meta( $parent_product, '_show_plugin', true );
            $show_plugin_value  = $show_plugin == "" && $settings['product_page'] != 'hide' ? '1' : $show_plugin; //if initial setup,show plugin on product page by default

            if($show_plugin_value == "0"){
                continue; //exclude products where bigpost quote box is hidden
            }

            $product_id = isset($item['product_id'] ) ? $item['product_id']: '';

            if(isset($item['variation_id']) && $item['variation_id'] > 0){
                $product_id  = $item['variation_id'];
            }

            $use_admin_setting  = get_post_meta( $parent_product, '_use_admin_setting', true );
            if( $use_admin_setting != 'Yes'  && $use_admin_setting != '' ) {

                $product_stypes = get_post_meta($parent_product, '_shipping_type', true);
                $product_stypes = json_decode( $product_stypes );
                foreach( $shipping_types as $key => $stype ) {
                    if( ! in_array($stype, $product_stypes) ) {
                        unset( $shipping_types[$key] );
                    }
                }
            }

            $rows = get_post_meta($product_id, '_carton_items', true);
            $productname = get_the_title($product_id);

            if(!empty($rows)){
                foreach (json_decode($rows) as $row) {

                    if( $can_consolidated == 'Yes' && !empty($row->consolidated) ) {
                        $can_consolidated = $row->consolidated;
                    }

                    $ItemType = (isset($row->packaging_type))?intval($row->packaging_type):0;
                    $data_items[] = array(
                        'Description'       => substr($productname, 0, 50),
                        'Length'            => floatval($row->length),
                        'Width'             => floatval($row->width),
                        'Height'            => floatval($row->height),
                        'Weight'            => floatval($row->weight),
                        'ItemType'          => $ItemType,
                        'isMHP'      => ( $row->mhp == 'Yes' ) ? true : false,
                        'Quantity'          => $item['quantity'],
                        'Consolidatable'    => ( $can_consolidated == 'Yes' ) ? true : false
                    );
                }
            }
        }

        return array('items' => $data_items, 'shippingtypes' => $shipping_types);
    }


    /* Let's get all data stored in the session for user to calculate shipping prices for BigPost */
    public function get_data_from_session_for_request(){
        $jobType = '';

        $buyer_is_business  = WC()->session->get('woobigpost-buyer_is_business');
        $has_forklift       = WC()->session->get('woobigpost-has_forklift');

        $has_forklift = ( $has_forklift == 1 ) ? true : false;

        if( $buyer_is_business == 'false' ) {
            $jobType = "";
            $isbusiness = 'false';
        } else {
            $jobType = 2;
            $isbusiness = 'true';
        }

        $AuthLeave = WC()->session->get( 'woobigpost-leave_authority' );

        $output = array('BuyerIsBusiness' => $isbusiness, 'JobType' => $jobType, 'BuyerHasForklift' => $has_forklift);

        if( $AuthLeave != '' ) {
            $output['ReturnAuthorityToLeaveOptions'] = ($AuthLeave == '1')?true:false;
        }

        return $output;
    }

    /* Let's get Custom / User Data from Session when the user is on Checkout page */
    public function get_customer_data_buyer($item = ''){
        $tosuburb   = WC()->session->get('woobigpost-tosuburb');
        $topostcode = WC()->session->get('woobigpost-topostcode');
        $tostate    = WC()->session->get('woobigpost-tostate');

        $topostcodeid = WC()->session->get('woobigpost-topostcodeid');

        $Address = $tosuburb . ' ' . $tostate;

        $output = array('address' => $Address, 'suburb' => $tosuburb, 'postcode' => $topostcode, 'postcodeid' => $topostcodeid, 'state' => $tostate);

        return $output[$item];
    }

    public function getPossibilities($has_over_weight)
    {
        if ($has_over_weight) {
            //we return all possibilies selection for shopify
            $possibilities = array(
                array(
                    "BuyerIsBusiness" => false,
                    "BuyerHasForklift" => false,
                    "ReturnAuthorityToLeaveOptions" => true,
                ),
                array(
                    "BuyerIsBusiness" => false,
                    "BuyerHasForklift" => false,
                    "ReturnAuthorityToLeaveOptions" => false,
                ),
                // this has been commented out,
                // this causes some quotes to show multiple business quotes
                // array(
                //   "BuyerIsBusiness" => true,
                //   "BuyerHasForklift" => false,
                //   "ReturnAuthorityToLeaveOptions" => false,
                // ),
                array(
                    "BuyerIsBusiness" => true,
                    "BuyerHasForklift" => true,
                    "ReturnAuthorityToLeaveOptions" => false,
                ),
                array(
                    "BuyerIsBusiness" => true,
                    "BuyerHasForklift" => true,
                    "ReturnAuthorityToLeaveOptions" => true,
                ), 
            );
        } else {
            $possibilities = array(
                array(
                    "BuyerIsBusiness" => false,
                    "BuyerHasForklift" => false,
                    "ReturnAuthorityToLeaveOptions" => false,
                ),
                array(
                    "BuyerIsBusiness" => false,
                    "BuyerHasForklift" => false,
                    "ReturnAuthorityToLeaveOptions" => true,
                ),
                array(  
                    "BuyerIsBusiness" => true,
                    "BuyerHasForklift" => false,
                    "ReturnAuthorityToLeaveOptions" => false,
                ),
                array(
                    "BuyerIsBusiness" => true,
                    "BuyerHasForklift" => false,
                    "ReturnAuthorityToLeaveOptions" => true,
                ),
            );
        }

        return $possibilities;
    }


    public function send_bigpost_api_request(){
        global $woocommerce;
        $data_to_send = array();
        $this->found_rates = array();
        $items = $woocommerce->cart->get_cart();
        $show_all_options = false; //(isset($this->settings['show_all_options']) && $this->settings['show_all_options'] == "Yes")?true:false;

        $data_to_send['PickupLocation'] = array();
        $data_to_send['BuyerLocation'] = array();

        $url = $this->api_url . '/api/getquote';
        $datas = $this->get_cart_items_to_get_pricing($items);

        $post_data = array();

        if( isset($_REQUEST['post_data']) ) {
            parse_str( $_REQUEST['post_data'], $post_data );
        }

        $data = $datas['items'];

        $post_data = empty($post_data) ? WC()->session->get('woobigpost-post_data') : $post_data;
        WC()->session->set( 'WooBigPost_shippingPrices_Display', '');

        if( !empty($data) && !empty($post_data)) {
            $delivery_options_arr = array();
            $warehouse_loc = $this->get_option('bigpost_warehouse_locations');

            $matched_warehouse = array();
            $mixed_cart = [];
            $free_shipping_disabled = [];

            $buyer_is_business = (isset($post_data['buyer_is_business']))?$post_data['buyer_is_business']:WC()->session->get('woobigpost-buyer_is_business');
            $has_forklift   = ( isset($post_data['has_forklift']) && $post_data['has_forklift'] == '1' ) || (isset($post_data['Forkliftsoption']) && $post_data['Forkliftsoption'] == '1') ? true : false;

            
            $session_auth_leave = (WC()->session->get('woobigpost-leave_authority') == 1)?true:false;

            $AuthLeave      = ( isset($post_data['leave_auth']) && $post_data['leave_auth'] == '1' ) ? true : $session_auth_leave;

            $data_to_send['BuyerIsBusiness'] = ($buyer_is_business == 1)?true:false;
            $data_to_send['BuyerHasForklift'] = ($has_forklift == 1)?true:false;
            $data_to_send['ReturnAuthorityToLeaveOptions'] = $AuthLeave;
            $data_to_send['PickupLocation']['Name'] = 'WooCommerce - Big Post Shipping';

            $customerName = WC()->customer->get_first_name();

            $data_to_send['BuyerLocation']['Name'] = (isset($customerName) && $customerName != '')?$customerName:'Guest User';
            $data_to_send['BuyerLocation']['Address'] = (isset($post_data['to_suburb1']))?$post_data['to_suburb1']:$this->get_customer_data_buyer('address');
            $data_to_send['BuyerLocation']['LocalityId'] = (isset($post_data['ToPostcodeId']))?$post_data['ToPostcodeId']:$this->get_customer_data_buyer('postcodeid');

            $BuyerLocality = array();
            $BuyerLocality['Id'] = (isset($post_data['ToPostcodeId']))?$post_data['ToPostcodeId']:$this->get_customer_data_buyer('postcodeid');
            $BuyerLocality['Suburb'] = (isset($post_data['ToSuburb']))?$post_data['ToSuburb']:$this->get_customer_data_buyer('suburb');
            $BuyerLocality['Postcode'] = (isset($post_data['ToPostcode']))?$post_data['ToPostcode']:$this->get_customer_data_buyer('postcode');
            $BuyerLocality['State'] = (isset($post_data['ToState']))?$post_data['ToState']:$this->get_customer_data_buyer('state');

            $data_to_send['BuyerLocation']['Locality'] = $BuyerLocality;
            $i = 0;
            foreach( $items as $item ) {
                if(isset($item['variation_id']) && $item['variation_id'] > 0){ //it is a variation
                    $parent_product = $item['product_id'];
                    $product_id  = $item['variation_id'];
                }else{
                    $parent_product = 0;
                    $product_id = $item['product_id'];
                }
                  
                $p_settings = new Woo_BigPost_Product_Settings($product_id, get_option( 'woocommerce_woobigpost_settings' ), $parent_product);

                $show_plugin_value  = $p_settings->product_settings['show_plugin']; //if initial setup,show plugin on product page by default

                if($show_plugin_value == "0"){
                    continue; //exclude products where bigpost quote box is hidden
                }

                $item_location = is_array($p_settings->product_settings['_product_locations']) ? $p_settings->product_settings['_product_locations'] : array();
                $matched_warehouse = empty($matched_warehouse) && $i == 0 ? $item_location : array_intersect($matched_warehouse,$item_location);

                $free_enabled = $p_settings->product_settings['_free_shipping'];

                if($this->get_option('free_shipping_config') == 'enable_all'){
                    $free_enabled = '1';
                }

                $mixed_cart[] = $free_enabled;

                if($free_enabled != '1'){
                    $free_shipping_disabled[] = $item;
                }

                $i++;
            }

            $mixed = false;
            if(in_array("0",$mixed_cart) || in_array("",$mixed_cart)){
                $mixed = true;
            }

            WC()->session->set( 'free_disabled_items', $free_shipping_disabled );

            $warehouse_loc =  woobigpost_get_selected_warehouse($matched_warehouse);

            $resources = [];
            $api_data  = [];

            $possibilities = $this->getPossibilities(false);

            if(!empty($warehouse_loc) && !is_null($buyer_is_business)){ //check warehouse is not empty and they have selected delivery type (ie Home or Business)

                foreach($possibilities as $possibility){

                	if($show_all_options){
                		$data_to_send['BuyerIsBusiness'] = $possibility['BuyerIsBusiness'];
		                $data_to_send['BuyerHasForklift'] = $possibility['BuyerHasForklift'];
		                $data_to_send['ReturnAuthorityToLeaveOptions'] = $possibility['ReturnAuthorityToLeaveOptions'];
                	}

                    foreach($warehouse_loc as $key=>$location){
                        $data_to_send['PickupLocation']['Address'] = $location['from_address'];
                        $data_to_send['PickupLocation']['LocalityId'] = $location['from_postcode_id'];

                        $PickupLocality = array();
                        $PickupLocality['Id'] = $location['from_postcode_id'];
                        $PickupLocality['Suburb'] = $location['from_suburb_addr'];
                        $PickupLocality['Postcode'] = $location['from_post_code'];
                        $PickupLocality['State'] = $location['from_state'];

                        $data_to_send['PickupLocation']['Locality'] = $PickupLocality;

                        $data_to_send['DepotId'] = '';

                        $data_to_send['Items'] = $data;

                        $toDepotId = (isset($post_data['ToDepotId']))?$post_data['ToDepotId']:WC()->session->get( 'woobigpost-todepotid' );

                        if($data_to_send['BuyerIsBusiness'] == false){
                            $data_to_send['DepotId'] = $toDepotId;
                            $data_to_send['JobType'] = "";
                        }else{
                            $data_to_send['JobType'] = 2;
                        }

                        $data_to_send['JobType'] = "";

                        $shippings = $datas['shippingtypes'];

                        //$typs = (!empty($typs))?array_filter($typs):$typs;
                        //$shippings = (count($typs) > 1)?array_values(call_user_func_array("array_intersect", $typs)):$typs;
                        //$shippings = (isset($shippings[0]) && is_array($shippings[0]))?$shippings[0]:$shippings;

                        /*$server_output = woobigpost_send_api_request('/api/getquote', $data_to_send, 'POST');
                        $decoded_result = json_decode( $server_output );
                        $delivery_options_arr[$key] = $decoded_result;*/

                        $endpoint = '/api/getquote';
                        $resources[] = $endpoint;
                        $api_data[] = $data_to_send;

                    }

                    if(!$show_all_options){
	                    break;
	                }
                }
            } else {
                WC()->session->set( 'WooBigPost_shippingPrices_Display',$this->get_option('no_available_shipping'));
                WC()->customer->set_calculated_shipping( true );
            }

            
            try {
                $guzzle = GuzzleMultiTransfer::getInstance();

                
                update_option('api_data', print_r($api_data, true));
                
                $delivery_options_arr = $guzzle->post_request($resources, $api_data);

                update_option('delivery_options_arr', print_r($delivery_options_arr, true));
            } catch (Exception $e) {
                $delivery_options_arr = array();
                echo 'Caught exception: ',  $e->getMessage(), "\n";
            }

            $result = woobigpost_get_cheapest_delivery_opt($delivery_options_arr,'cart',$post_data);

            $process_free = woobigpost_process_free_shipping($free_shipping_disabled, $items);

            if($process_free === true && ( !empty($result) )){

                $free_shipping_check = new Woo_BigPost_Free_Shipping_Check();
                $new_rates = $free_shipping_check->create_new_shipping_rates($result,$mixed);

                $post_data['new_rates'] = $new_rates;
                if(isset($new_rates['Results']) && !empty($new_rates['Results'])){
                    $result = $new_rates['Results'];
                }
            }

            $index = WC()->session->get( 'woobigpost-cheapest_warehouse_index' ); //we already set the index of the location with the cheapest delivery option
            $depot_index =  WC()->session->get( 'woobigpost-cheapest_depot_warehouse_index' );

            if( !empty($result) ) {
                if( empty($result->Errors) ) {

                    $DeliveryOptions = $result->Object->DeliveryOptions;

                    $save_to_session = array();
                    foreach( array_reverse($DeliveryOptions) as $option ) {

                        $session_data = array();
                        if( !empty( $shippings ) && in_array( $option->JobType, $shippings ) ) {

                            $havec = WC()->session->get('woobigpost-have_confirm');
                            if($option->JobType === 'HDS' &&  $havec == 0 && $show_all_options == false) continue;

                            if( empty($option->CarrierOptions) ) {
                                continue;
                            }

                            if(!empty($index)){
                                //echo "<pre>";print_r($warehouse_loc);echo "</pre>";
                                if(empty($warehouse_loc)){
                                    $warehouse_loc = $this->get_option('bigpost_warehouse_locations')[$index[0]];
                                }else{
                                    $first_key = $index[0];//key($warehouse_loc);
                                    
                                    if(isset($warehouse_loc[$first_key])){
                                        if(is_array($warehouse_loc[$first_key])){
                                            $warehouse_loc = $warehouse_loc[$first_key];
                                        }    
                                    }else{
                                        if(is_array(reset($warehouse_loc))){
                                            $warehouse_loc = reset($warehouse_loc);
                                        }
                                    }
                                }
                                
                                $PickupLocality = array(
                                    'Name'      => $warehouse_loc['from_name'] != "" ?  $warehouse_loc['from_name']: "Bigpost",
                                    'Address'   => $warehouse_loc['from_address'],
                                    "LocalityId"    => absint($warehouse_loc['from_postcode_id']),
                                    'Locality'  => array(
                                        "Id"        => absint($warehouse_loc['from_postcode_id']),
                                        "Suburb"    => $warehouse_loc['from_suburb_addr'],
                                        "Postcode"  => $warehouse_loc['from_post_code'],
                                        "State"     => $warehouse_loc['from_state']
                                    )
                                );
                            }

                            //$price_data = $this->show_pricing_for_shipping($option);
                            $price_data = woobigpost_show_pricing_for_shipping($option, 'cart');

                            //print_r($price_data);
                            //exit();
                            if( count($price_data) > 0 ) {
                                $BuyerLocality['Address'] = (isset($post_data['to_suburb1']))?$post_data['to_suburb1']:$this->get_customer_data_buyer('address');

                                $mthds = array(
                                    'CarrierName'   => $price_data['CarrierName'],
                                    'CarrierId'     => $price_data['CarrierId'],
                                    'ServiceCode'   => $price_data['ServiceCode'],
                                    'Charge'        => $price_data['Charge'],
                                    'Tax'           => $price_data['Tax'],
                                    'Total'         => $price_data['Total'],
                                    'ShippingType'  => $option->JobType,
                                    'BuyerLocality' => $BuyerLocality,
                                    'PickupLocality'=> $PickupLocality,
                                    'BuyerIsBusiness' => WC()->session->get('woobigpost-buyer_is_business'),
                                    'HasForklift' => WC()->session->get('woobigpost-has_forklift')
                                );

                                if(isset($price_data['RequiresAuthorityToLeave'])){
                                    $mthds['LeaveAuth'] = $price_data['RequiresAuthorityToLeave'];
                                }

                                //Overwrites the HOME title for HDS if it is a business customer, but a HDS delivery
                                $title = woobigpost_getLabel($option->JobType, WC()->session->get('woobigpost-buyer_is_business'), WC()->session->get('woobigpost-has_forklift'));
                                $title = $title['label'];


                                $shippingDesc = '';
                                if( isset($option->JobType) && $option->JobType == 'DEPOT' ) {
                                    $depot = $this->export_depot_address($option->DepotLocality->Postcode, $option->DepotLocality->Suburb, $option->DepotLocality->Id, true);

                                    $address = $depot->DepotName . ', ' . $depot->Address . ', ' . $depot->Suburb . ', ' . $depot->State . ' - ' . $depot->Postcode;

                                    $shippingDesc = $this->settings['shipping_'.strtolower($option->JobType) .'_description'];
                                    $shippingAddress = $address;

                                    //for pickup locality
                                    if(!empty($depot_index) && empty($PickupLocality)){
                                        $warehouse_loc = $this->get_option('bigpost_warehouse_locations')[$depot_index[0]];
                                        $PickupLocality = array(
                                            'Name'      => $warehouse_loc['from_name'] != "" ?  $warehouse_loc['from_name']: "Macship",
                                            'Address'   => $warehouse_loc['from_address'],
                                            "LocalityId"    => absint($warehouse_loc['from_postcode_id']),
                                            'Locality'  => array(
                                                "Id"        => absint($warehouse_loc['from_postcode_id']),
                                                "Suburb"    => $warehouse_loc['from_suburb_addr'],
                                                "Postcode"  => $warehouse_loc['from_post_code'],
                                                "State"     => $warehouse_loc['from_state']
                                            )
                                        );
                                    }

                                    $mthds['PickupLocality'] = $PickupLocality;

                                    $depot_details = array();

                                    $BuyerLocality = array();
                                    $BuyerLocality['Address'] = $shippingAddress;
                                    $BuyerLocality['Id'] = (isset($post_data['ToPostcodeId']))?$post_data['ToPostcodeId']:$this->get_customer_data_buyer('postcodeid');
                                    $BuyerLocality['Suburb'] = (isset($post_data['ToSuburb']))?$post_data['ToSuburb']:$this->get_customer_data_buyer('suburb');
                                    $BuyerLocality['Postcode'] = (isset($post_data['ToPostcode']))?$post_data['ToPostcode']:$this->get_customer_data_buyer('postcode');
                                    $BuyerLocality['State'] = (isset($post_data['ToState']))?$post_data['ToState']:$this->get_customer_data_buyer('state');

                                    $address = array(
                                        'company'    => $depot->DepotName,
                                        'address_1'  => $depot->Address,
                                        'city'       => $depot->Suburb,
                                        'state'      => $depot->State,
                                        'postcode'   => $depot->Postcode,
                                        'country'    => 'AU'
                                    );

                                    $BuyerLocality['DepotAddress'] = $address;
                                    $BuyerLocality['ToDepotId'] = $depot->DepotId;

                                    $mthds['BuyerLocality'] = $BuyerLocality;

                                    $shippingAddress = ($shippingAddress) ? 'For Pick Up At: ' . $shippingAddress : '';
                                    $title = $title.' ('.$depot->Suburb.')';

                                } else {
                                    if( isset($option->JobType) ){
                                        $shippingDesc = $this->settings['shipping_' . strtolower($option->JobType) . '_description'];
                                        $shippingAddress = '';
                                    }
                                }

                                $session_data['title'] = $title . ' $' . number_format($price_data['Total'], 2);
                                $session_data['description'] = $shippingDesc;
                                $session_data['address'] = $shippingAddress;

                                $rate_id = $this->id . ':' . $option->JobType;
                                $save_to_session[$rate_id] = $session_data;

                                $shipping_rate = $price_data['Total'];
                                $label = 'Big Post - ' . $title;

                                if(isset($new_rates[$option->JobType]) && !isset($new_rates['Results'])){
                                    $shipping_rate = $new_rates[$option->JobType];

                                    if($shipping_rate == 0){
                                        //$label .= ': <strong>'.wc_price($shipping_rate)."</strong>";
                                        //$label .= ': <strong>FREE</strong>';
                                    }
                                }

                                if($show_all_options && $price_data['RequiresAuthorityToLeave']){
                                	$label .= " (Leave Safe)";
                                }

                                $arr = array(
                                    'id'        => $rate_id,
                                    'label'     => $label,
                                    'cost'      => $shipping_rate,
                                    'meta_data' => $mthds,
                                    'calc_tax' => 'per_order'
                                );

                                $this->found_rates[ $rate_id ] = $arr;

                            }
                        }
                    }

                    /* Let's store all shipping method values in the session, so that we can use them later on. */
                    WC()->session->set('shipping_bigpost_methods', $save_to_session);
                    WC()->session->set('shipping_calculated_cost_bigpost', $this->found_rates);
                    WC()->customer->set_calculated_shipping( true );

                    $shippingPrices = woobigpost_get_shipping_prices( $result, 'cart', $post_data);

                    //setcookie('shippingPrices',$shippingPrices,'/');
                    WC()->session->set( 'WooBigPost_shippingPrices_Display',$shippingPrices);

                }else{

                    /* There was an error getting quote, so let's remove the session stored values. */
                    WC()->session->__unset('shipping_bigpost_methods');
                    $msg = 'From - ' . site_url();
                    $msg .= 'Data Sent - ' . json_encode($data_to_send);
                    $msg .= 'API Server Response - ' . $server_output;
                }
            }

        } else {
            WC()->session->set( 'WooBigPost_shippingPrices_Display','Shipping settings are not saved for some of products in the cart.');
            WC()->customer->set_calculated_shipping( true );
        }

        return $this->found_rates;
    }

    public function calculate_margin_amount($amount = '', $margin_value = '', $margin_action = '', $margin_type = '') {
        $margin_value = floatval($margin_value);
        $margin_action = $margin_action;
        $margin_type = $margin_type;
        if ($margin_action == 'Add') {
            if ($margin_type == '$') {
                $new_amount = floatval($margin_value + $amount);
            } else {
                $new_amount = floatval($amount + (($margin_value*$amount)/100));
            }
        } else {
            if ($margin_type == '$') {
                $new_amount = floatval($amount -$margin_value);
            } else {
                $new_amount = floatval($amount - (($margin_value*$amount)/100));
            }
        }

        return $new_amount;
    }

    public function round_off_amount($new_amount) {
        $round_shipping_value = get_option('shipping_price_round','RoundUp');
        if ($round_shipping_value && $round_shipping_value == "RoundUp") {
            $new_amount = round($new_amount, 0, PHP_ROUND_HALF_UP);
        } elseif ($round_shipping_value && $round_shipping_value == "RoundDown"){
            $new_amount = round($new_amount, 0, PHP_ROUND_HALF_DOWN);
        } elseif ($round_shipping_value && $round_shipping_value == "UpDown") {
            $new_amount = round($new_amount, 0);
        } else {
            $new_amount = number_format($new_amount, 2);
        }
        return $new_amount;
    }

    public function bigpost_api_request($url = '', $data_to_send = array(), $request_method = 'GET'){
        $settings = woobigpost_get_plugin_settings();

        $api_key = $this->api_key;

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

    public function export_depot_address($post_code ='', $suburb = '', $depot_id = '', $return = false) {

        $url = $this->api_url . "/api/depots?p=" . $post_code . '&s=' . urlencode($suburb);

        $depots     = $this->bigpost_api_request($url, '', 'GET');
        $depots     = json_decode($depots);

        $address = '';
        if(isset($depots) && count($depots) > 0){
            foreach($depots as $depot){
                $depotId = $depot->DepotId;
                if($depot_id == $depotId){
                    $address = $depot->DepotName . ', ' . $depot->Address . ', ' . $depot->Suburb . ', ' . $depot->State . ' - ' . $depot->Postcode;

                    if($return === true){
                        $address = $depot;
                    }
                }else{
                    continue;
                }
            }
        }
        return $address;
    }

    public function get_cis_quotes($package){
            
        $boxes = array();
        foreach(WC()->cart->get_cart() as $cart_item){
            
            if(isset($cart_item["variation_id"]) && $cart_item["variation_id"] > 0){
                $product_id = $cart_item['variation_id'];    
            }else{
                $product_id = $cart_item['product_id'];
            }
            
            $product = wc_get_product($product_id);

            $product_weight = $product->get_weight();
            $product_length = $product->get_length();
            $product_width = $product->get_width();
            $product_height = $product->get_height();
            
            $product_quantity = $cart_item['quantity'];

            $boxes[] = array("name"=>"Product Name",
                "sku"=>$product->get_sku(),
                "length"=>$product_length, 
                "height"=>$product_height, 
                "width"=>$product_width, 
                "weight"=>$product_weight,
                "quantity"=>$product_quantity
            );
        }

        $data = array(
            "page" => "checkout",
            "key" => $this->settings['bigpost_key'],
            "use_box" => 0,
            "rate" => array(
                "destination" => array(
                    "country" => $package['destination']['country'],
                    "postal_code" => $package['destination']['postcode'],
                    "province" => $package['destination']['state'],
                    "city" => $package['destination']['city']
                ),
                "items" => $boxes
            ),

        );
        

        //send this array to https://cis.bigpost.com.au/quote
        $url = 'https://cis.bigpost.com.au/wordpress/get_quote';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);

        //echo $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $response = json_decode($result);

        $quotes = array();
        if(!$response->error){
            foreach($response->rates as $key=>$quote_row){

                $meta = explode(":",$quote_row->service_code);

                $mthds = array(
                    'CarrierId'     => $meta[1],
                    'ServiceCode'   => $meta[3],
                    'ShippingType'  => $meta[0],
                    'RequiresAuthorityToLeave' => $meta[4],
                    'QuoteId' => $meta[5]
                    #'CarrierName'   => $meta['CarrierName'],
                    #'Charge'        => $meta['Charge'],
                    #'Tax'           => $meta['Tax'],
                    #'Total'         => $meta['Total'],
                    #'BuyerLocality' => $BuyerLocality,
                    #'PickupLocality'=> $PickupLocality,
                    #'BuyerIsBusiness' => WC()->session->get('woobigpost-buyer_is_business'),
                    #'HasForklift' => WC()->session->get('woobigpost-has_forklift')
                );

                $quotes[] = array(
                    'id' => 'woobigpost_'.$key,
                    'label' => $quote_row->service_name,
                    'cost' => $quote_row->total_price / 100,
                    'meta_data' => $mthds
                );
            }    
        }
        
        WC()->customer->set_calculated_shipping( true );
        
        return $quotes;
    }
}