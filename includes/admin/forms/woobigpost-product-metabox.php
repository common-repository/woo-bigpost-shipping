<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Product metabox fields
 */
global $post;

$product_data = wc_get_product($post->ID);
$settings = woobigpost_get_plugin_settings();
$p_settings = new Woo_BigPost_Product_Settings($post->ID, $settings);

$show_plugin_value  = $p_settings->product_settings['show_plugin'];
$no_of_cartons		= $p_settings->product_settings['_no_of_cartons'] != "" ? $p_settings->product_settings['_no_of_cartons'] : 1;
$CanConsolidated	= $p_settings->product_settings['_can_consolidated'];
$carton_items		= $p_settings->product_settings['_carton_items'];
$ShippingType		= $p_settings->product_settings['_shipping_type'];
$Authority_option	= $p_settings->product_settings['_authority_option'];
$use_admin_setting	= $p_settings->product_settings['_use_admin_setting'];
$free_shipping      = $p_settings->product_settings['_free_shipping'];
$product_location   =  $p_settings->product_settings['_product_locations'];
$all_items_available_to  =  $p_settings->product_settings['_items_available_to'];
?>

<style>
    .form-table tr td label { margin-right: 20px;}
</style>

<table class="form-table">
    <tr>
        <th><?php _e("Present plugin for this product?", 'bigpost'); ?></th>
        <td>
            <label>
                <input class="show_plugin" type="checkbox" <?php if($show_plugin_value == '1'){ echo 'checked="checked"'; } ?> value="<?php echo $show_plugin_value;?>"/>
                <input class="show_plugin_hidden" type="hidden" name="woobigpost_show_plugin" value="<?php echo $show_plugin_value;?>"/>
            </label>
        </td>
    </tr>
    <?php
    if( isset($settings['is_advanced_mode']) && $settings['is_advanced_mode'] == "Yes" ) { ?>

        <!-- <tr>
			<td><strong><?php _e("Can this item be consolidated?", 'bigpost'); ?></strong></td>
			<td>
				<input type="radio" name="CanConsolidated" id="canconsoldatedyes" value="true" <?php if (!empty($CanConsolidated) && $CanConsolidated == 'true') {	echo 'checked="checked"'; } ?>> 
				<label for="canconsoldatedyes"><?php _e("Yes", 'bigpost'); ?></label>
				<input type="radio" name="CanConsolidated" id="canconsoldatedno" value="false" <?php if (!empty($CanConsolidated) && $CanConsolidated == 'false') {	echo 'checked="checked"'; } elseif(empty($CanConsolidated)) { echo 'checked="checked"'; } ?>> 
				<label for="canconsoldatedno"><?php _e("No", 'bigpost'); ?></label>
			</td>
		</tr> -->
    <?php
    } else {
        //echo '<input type="hidden" name="CanConsolidated" value="false" />';
    }

    $ShippingType = ( $ShippingType == '' ) ? array() : $ShippingType; ?>

    <tr>
        <th><?php _e("Shipping Type", 'bigpost'); ?></th>
        <td>
            <label>
                <input class="shipping_type" type="checkbox" value="HDS" name="woobigpost_shipping_type[]" id="hds"  <?php checked( in_array('HDS', $ShippingType) ); ?> />
                <?php esc_html_e( 'Home Delivery Service (HDS)', 'woobigpost' ); ?>
            </label>
            <label for="woobigshop-business">
                <input  class="shipping_type" type="checkbox" value="BUSINESS" name="woobigpost_shipping_type[]"  data-shiptypeid="woocommerce_bigpost_shipping_business" id="woobigshop-business" <?php checked( in_array('BUSINESS', $ShippingType) ); ?> />
                <?php esc_html_e( 'Business', 'woobigpost' ); ?>
            </label>
            <label>
                <input class="shipping_tpe" type="checkbox" value="DEPOT" name="woobigpost_shipping_type[]" data-shiptypeid="woocommerce_bigpost_shipping_depot" id="woobigshop-depot" <?php checked( in_array('DEPOT', $ShippingType) ) ?> />
                <?php esc_html_e( 'Depot', 'woobigpost' ); ?>
            </label>
            <label>
                <input class="shipping_tpe" type="checkbox" value="Yes" name="woobigpost_use_admin_setting" data-shiptypeid="woocommerce_bigpost_use_admin_setting" id="woobigshop-admin-setting"<?php checked( $use_admin_setting, 'Yes' ); ?> />
                <?php esc_html_e( 'Use Admin Setting', 'woobigpost' ); ?>
            </label>
        </td>
    </tr>
    <tr>
        <th><?php _e("Authority To Leave", 'woobigpost'); ?></th>
        <td>
            <label><input type="radio" id="fauthority_yes" name="woobigpost_authority_option" value="Yes" <?php checked( $Authority_option, 'Yes' ); ?> /> Yes - Ask My Customer</label>

            <label><input type="radio" id="fauthority_always" name="woobigpost_authority_option" value="Always" <?php checked( $Authority_option, 'Always' ); ?> /> Yes - Always give ATL</label>

            <label><input type="radio" id="authority_no" name="woobigpost_authority_option" value="No" <?php checked( $Authority_option, 'No' ); ?>/> No - No It Must Be Signed For </label>

            <label><input type="radio" id="authority_global" name="woobigpost_authority_option" value="global" <?php checked( $Authority_option, 'global' ); ?> /> Use Admin Setting </label>

            <p class="description">Ask your customers if we are allowed to leave the package in  a safe place?</p>
        </td>
    </tr>
    <?php if(isset($settings['bigpost_warehouse_locations']) && !empty($settings['bigpost_warehouse_locations'])){ ?>
        <tr>
            <th><label for="warehouse_locations">
                    <?php _e( 'Available At Warehouse', 'bigpost' ); ?>
                </label>
            </th>
            <td>
                <?php
                foreach($settings['bigpost_warehouse_locations'] as $key=>$location) {
                    $readonly = "";
                ?>
                    <label>
                        <input class="shipping_location" type="checkbox" value="<?php echo $key; ?>" name="woobigpost_product_shipping_locations[]" <?php if(is_array($product_location) && !empty($product_location)){ checked( in_array($key, $product_location) );} ?> <?php echo $readonly; ?>/>
                        <?php esc_html_e( $location['from_name'], 'woobigpost' ); ?>
                    </label>
                <?php } ?>
            </td>
        </tr>
    <?php }

    if($product_data){
        if($product_data->get_type() == 'variable'){
            require_once( WOO_BIGPOST_DIR . '/includes/admin/forms/templates/variable_product.php' );
        } else if($product_data->get_type() == 'simple' || $product_data->get_type() == 'composite'){
            require_once( WOO_BIGPOST_DIR . '/includes/admin/forms/templates/simple_product.php' );
        }
    }
    ?>

</table>
<script>
    jQuery(document).ready(function(){
        if(jQuery('.show_plugin').prop('checked')){
            jQuery('#quote_item_list input').attr('min','0.01');
            jQuery('#woobigpost-no-of-cartons').attr('min','1');
            
        }else{
            jQuery('#quote_item_list input').attr('min','0');
            jQuery('#woobigpost-no-of-cartons').attr('min','0');
        }

        jQuery('.show_plugin').on('change', function(){
            if(jQuery(this).prop('checked')){
                jQuery('#quote_item_list input').attr('min','0.01');
                jQuery('#woobigpost-no-of-cartons').attr('min','1');
            }else{
                jQuery('#quote_item_list input').attr('min','0');
                jQuery('#woobigpost-no-of-cartons').attr('min','0');
            }
        });
    })
</script>
<?php
    require_once( WOO_BIGPOST_DIR . '/includes/admin/forms/templates/leave_rating.php' );
?>