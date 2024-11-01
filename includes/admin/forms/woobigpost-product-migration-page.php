<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

$packaging_types = array(
    'Carton'	=> 0,
    'Skid'		=> 1,
    'Pallet'	=> 2,
    'Pack'		=> 3,
    'Crate'		=> 4,
    'Roll'		=> 5,
    'Satchel'	=> 6,
    'Stillage'	=> 7,
    'Tube'		=> 8,
    'Bag'		=> 9
);

$settings = woobigpost_get_plugin_settings();
?>

<div class="wrap">
    <h2><?php echo __( 'Big Post Product Box Migration', 'woobigpost' ); ?></h2>
    <div class="woobigpost-product-box-wrap">
        <div class="woobigpost-product-box-start">
            <div class="woobigpost-product-box-msg">
                <p>
                <?php
                if(isset($_GET['product_box_migration']) && $_GET['product_box_migration'] == 'true'){
                    echo __("Woocommerce box settings shipping dimensions has been copied to Big Post box settings.", 'woobigpost');
                } else {
                    echo __( 'Use the function below to copy all of your woocommerce box settings across to the Big Post box settings all at once.', 'woobigpost' );
                }
                ?>
                </p>
            </div>
        </div>
    </div>
    <div id="poststuff">
        <div id="post-body-content" class="woobigpost-product-box-start">
            <form id="woobigpost-product-box_form" action="<?php echo admin_url( 'admin.php' ); ?>" method="post">
                <?php wp_nonce_field( basename( __FILE__ ), 'woobigpost-product-box_nonce' ); ?>
                <input type="hidden" name="action" value="woobigpost_product_box_update">
                <div id="namediv" class="stuffbox">
                    <h2><label>Overwrite my existing Big Post Product Carton settings</label></h2>
                    <div class="inside">
                        <input type="checkbox" name="woobigpost-product-box_overwrite_settings" id="woobigpost-product-box_overwrite_settings" value="<?php echo $settings; ?>" <?php if($settings == 'yes'){?>checked="checked" <?php } ?> style="width: 1%"/>
                    </div>
                    <h2><label>Package Type</label></h2>
                    <div class="inside">
                        <select id="woobigpost-product-box_package_type" name="woobigpost-product-box_package_type">
                            <?php
                               foreach($packaging_types as $key=>$type){
                            ?>
                                 <option value="<?php echo $type; ?>"><?php echo $key;?></option>
                            <?php
                               }
                            ?>
                        </select>
                    </div>
                    <h2><label>Consolidated?</label></h2>
                    <div class="inside">
                        <select id="woobigpost-product-box_consolidated" name="woobigpost-product-box_consolidated">
                            <option value="No">No</option>
                            <option value="Yes">Yes</option>
                        </select>
                    </div>
                    <h2><label>Available At Warehouse</label></h2>
                    <?php if($settings['bigpost_warehouse_locations'] && count($settings['bigpost_warehouse_locations']) > 0): ?>
                    <div class="inside">
                        <?php foreach($settings['bigpost_warehouse_locations'] as $key=>$location) {?>
                            <input class="shipping_location" type="checkbox" value="<?php echo $key; ?>" name="woobigpost_product_shipping_locations[]" <?php if(!empty($product_location)){ checked( in_array($key, $product_location) );} ?> style="width: 1%"/>
                            <?php esc_html_e( $location['from_name'], 'woobigpost' ); ?><br />
                        <?php } ?>
                    </div>
                    
                    <div class="inside woobigpost-product-box_btn">
                        <button id="woobigpost-product-box_btn" class="button button-primary woocommerce-save-button" type="button">
                            <span class="dashicons dashicons-update"></span>
                            <?php echo __( 'GO', 'woobigpost' ) ?>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>