<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;
$settings = woobigpost_get_plugin_settings();

// Check if shipping is not enabled
if( empty($settings['enabled']) || $settings['enabled'] != 'yes' ) return;

$uniqueID = 'woobigpost-shipping-quote-form-' . uniqid();
$class = 'woobigpost-shipping-quote-form';
$title = 'Quick Quote';
$idWrapper = 'bigpost_single_product_form';

// Global Settings
$options = array();
$options['shipping_types']		= isset( $settings['shipping_types'] ) ? $settings['shipping_types'] : array();
$options['forklift_option']		= isset( $settings['forklift_option'] ) ? $settings['forklift_option'] : array();
$options['authority_option']	= isset( $settings['authority_option'] ) ? $settings['authority_option'] : array();

$weight = 0;
$productid = '';
$over40 = false;

$buyer_is_business = WC()->session->get( 'woobigpost-buyer_is_business' );
$leave_auth = WC()->session->get( 'woobigpost-leave_authority' );
$has_forklift = WC()->session->get( 'woobigpost-has_forklift' );
$have_confirm = WC()->session->get( 'woobigpost-have_confirm' );

$tosuburb = WC()->session->get( 'woobigpost-tosuburb' );
$tostate = WC()->session->get( 'woobigpost-tostate' );
$topostcode = WC()->session->get( 'woobigpost-topostcode' );
$topostcodeid = WC()->session->get( 'woobigpost-topostcodeid' );
$todepotid = WC()->session->get( 'woobigpost-todepotid' );
$shippingPrices_Display = WC()->session->get( 'WooBigPost_shippingPrices_Display' );

$variations = $product->get_available_variations();
$html_data = array();
if(!empty($variations)){
    foreach($variations as $variation){
        $over40 = false;
        $options = woobigpost_merge_product_settings( $product->get_id(), $options );
        $rows = get_post_meta( $variation['variation_id'], '_carton_items', true );
                if( !empty($rows) ) {
                    foreach (json_decode($rows) as $row) {
                        //$weight += ( intval($row->weight) ); old code
                        if(( intval($row->weight) ) > 40){
                            $over40 = true;
                            break;
                        }

                    }
                }
                ob_start();
                // check weight is grator then 40
                if( $over40 == true ) { ?>
                    <tr  class="sqf-opts sqf-confirm-opt woo_var_replaceable">
                        <td>
                            <label class="label-control"><?php _e("Due to weight of the items, please confirm the following:", 'woobigpost'); ?></label>
                            <div class="sqf-opt-desc">
                                <?php _e("1. My driveway is sealed with an even surface", 'woobigpost'); ?><br/>
                                <?php _e("2. There is reasonable vehicle access with height clearance.", 'woobigpost'); ?><br/>
                                <?php _e("3. I acknowledge the delivery is to the front door,  carport, or closest practical delivery point as deemed by the     driver.", 'woobigpost'); ?>
                            </div>
                            <div class="options_inner">
                                <label class="radioitem">
                                    <input name="have_confirm" class="have_confirm" type="radio" value="1" <?php if($have_confirm == '1'){?> checked="checked" <?php } ?>>
                                    <label for="have_confirm"></label>
                                    <span class="lbl_inner">Yes - All of these are true</span>
                                </label><br/>
                                <label class="radioitem">
                                    <input name="have_confirm" class="have_confirm" type="radio" value="0" <?php if($have_confirm == '0'){?>checked="checked"<?php } ?>>
                                    <label for="have_confirm"></label>
                                    <span class="lbl_inner">No - one or more of these are not true</span>
                                </label>
                            </div>
                        </td>
                    </tr>
                    <tr class="sqf-confirm-no woo_var_replaceable"><td></td></tr>
                    <?php
                    // check authority option
                } elseif( $over40 == false && $options['authority_option'] == 'Yes' ) { ?>
                    <tr class="sqf-opts sqf-authority-opt woo_var_replaceable">
                        <td>
                            <input name="have_confirm" class="have_confirm woo_var_replaceable" type="hidden" value="3">
                            <label class="label-control"><?php _e( "Do we have permission to leave the package in a safe place?:", 'woobigpost' ); ?></label>
                            <div class="options_inner">
                                <label class="radioitem">
                                    <input name="leave_auth" class="have_permission" type="radio" value="1" <?php if($leave_auth == '1'){?>checked="checked"<?php } ?>>
                                    <label for="leave_auth"></label>
                                    <span class="lbl_inner">Yes - You may leave at the front door</span>
                                </label><br/>
                                <label class="radioitem">
                                    <input name="leave_auth" class="have_permission" id="permission_no" type="radio" value="0" <?php if($leave_auth == '0'){?>checked="checked"<?php } ?>>
                                    <label for="leave_auth"></label>
                                    <span class="lbl_inner">No - It must be signed for</span>
                                </label>
                            </div>
                        </td>
                    </tr>
                <?php
                } elseif( $over40 == false && $options['authority_option'] == 'No' ) { ?>
                    <input name="have_confirm" class="have_confirm woo_var_replaceable" type="hidden" value="3" checked="checked">
                <?php
                } elseif( $options['authority_option'] == 'Always' ) { ?>
                    <input name="have_confirm" class="have_confirm woo_var_replaceable" type="hidden" value="1" checked="checked">
                <?php
                }
                // Check forklift option
                if( $over40 == true && $options['forklift_option'] == 'Yes' ) { ?>
                    <tr class="sqf-opts sqf-forklift-opt woo_var_replaceable">
                        <td>
                            <label class="label-control"><?php _e( 'Do you have a forklift?:', 'woobigpost' ); ?></label>
                            <div class="options_inner">
                                <label class="radioitem">
                                    <input name="Forkliftsoption" type="radio" value="1" <?php if($has_forklift == '1'){?>checked="checked"<?php } ?>>
                                    <label for="Forkliftsoption"></label>
                                    <span class="lbl_inner">Yes</span>
                                </label>
                                <label class="radioitem">
                                    <input name="Forkliftsoption" type="radio" value="0" <?php if($has_forklift == '0'){?>checked="checked"<?php } ?>>
                                    <label for="Forkliftsoption"></label>
                                    <span class="lbl_inner">No</span>
                                </label></br>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
                <tr class="no_variation woo_var_replaceable">
                    <td></td>
                </tr>
                <?php
                $html = ob_get_clean();
                $html_data[$variation['variation_id']] = $html;
    }
    $html_data = json_encode(array('data'=>$html_data),JSON_UNESCAPED_SLASHES);
}
echo "<p class='woobigpost_var_html_data' style='display:none' data-desc='".$html_data."'>&nbsp;</p>";
?>

