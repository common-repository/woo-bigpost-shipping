<div id="<?php echo $idWrapper ?>">
    <div id="<?php echo $uniqueID; ?>" class="<?php echo $class; ?>">
        <div id="woobigpost-ajax-loader" style="background-image: url( <?php echo admin_url(); ?>images/spinner.gif );"></div>

        <div class="woobigpost-sqf-title">
            <h2><?php echo $title; ?></h2>
        </div>

        <div class="woobigpost-sqf-body">
            <?php
            $url = $settings['api_url'];
            $no_hds_business = false; 
            if(strpos($url, 'staging') !== false){
                ?>
                <p>Current Mode: <strong>Testing</strong></p>
            <?php } ?>
            <table class="woobigpost-sqf-fields">
                <input type="hidden" name="is_over_weight" value="<?php echo $over40; ?>"/>
                <tr>
                    <td>
                        <?php if( in_array('HDS', $options['shipping_types']) || in_array('BUSINESS', $options['shipping_types']) ) { ?>
                        <label class="label-control"><?php _e( 'The delivery address is a:', 'woobigpost' ); ?></label>
                        <?php } else { $no_hds_business = true; ?>
                        <label class="label-control"><?php _e( 'Home Delivery and Business shipping not available.', 'woobigpost' ); ?></label>
                        <?php } ?>
                        <?php
                        if( in_array('HDS', $options['shipping_types']) ) { ?>
                        <label class="radioitem">
                            <input name="BuyerIsBusiness" type="radio" value="0" <?php echo $buyer_is_business?'':'checked'; ?> />
                            <label for="BuyerIsBusiness"></label>
                            <span class="lbl_inner">Home</span>
                        </label>
                        <?php }?>
                        <?php
                        if( in_array('BUSINESS', $options['shipping_types']) ) { ?>
                            <label class="radioitem">
                                <input name="BuyerIsBusiness" type="radio" value="1" <?php echo $buyer_is_business?'checked':''; ?> />
                                <label for="BuyerIsBusiness"></label>
                                <span class="lbl_inner">Business</span>
                            </label>
                        <?php }?>
                        <?php if( !in_array('HDS', $options['shipping_types']) && !in_array('BUSINESS', $options['shipping_types']) ) { ?>
                            <input name="BuyerIsBusiness" type="radio" value="0" style="display: none;" checked />
                            <input name="leave_auth" class="have_permission" type="radio" value="1"  style="display: none;" checked />
                        <?php }else{ ?>
                        <label class="subtext"><br />Select from a home delivery or a delivery to your business address.</label>
                        <?php } ?>
                    </td>
                </tr>

                <?php
                    // check weight is grator then 40
                    if( $over40 == true && !$no_hds_business ) { ?>

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
                                        <input name="have_confirm" class="have_confirm" type="radio" value="1" <?php echo $have_confirm?'checked':''; ?>>
                                        <label for="have_confirm"></label>
                                        <span class="lbl_inner">Yes - All of these are true</span>
                                    </label><br/>
                                    <label class="radioitem">
                                        <input name="have_confirm" class="have_confirm" type="radio" value="0" <?php echo $have_confirm?'':'checked'; ?>>
                                        <label for="have_confirm"></label>
                                        <span class="lbl_inner">No - one or more of these are not true</span>
                                    </label>
                                    <input name="leave_auth" class="have_permission" type="radio" value="1" checked style="display: none">
                                    <input name="Forkliftsoption" type="radio" value="0" checked style="display: none">
                                </div>
                            </td>
                        </tr>
                        <tr class="sqf-confirm-no woo_var_replaceable"><td></td></tr>

                        <?php
                        // check authority option
                    } elseif( $over40 == false && $options['authority_option'] == 'Yes' && !$no_hds_business ) { ?>
                        <tr class="sqf-opts sqf-authority-opt woo_var_replaceable">
                            <td>
                                <input name="have_confirm" class="have_confirm woo_var_replaceable" type="hidden" value="3">
                                <label class="label-control"><?php _e( "Do we have permission to leave the package in a safe place?:", 'woobigpost' ); ?></label>

                                <div class="options_inner">
                                    <label class="radioitem">
                                        <input name="leave_auth" class="have_permission" type="radio" value="1" <?php echo $leave_auth?'checked':''; ?>>
                                        <label for="leave_auth"></label>
                                        <span class="lbl_inner">Yes - You may leave at the front door</span>
                                    </label><br/>

                                    <label class="radioitem">
                                        <input name="leave_auth" class="have_permission" id="permission_no" type="radio" value="0" <?php echo $leave_auth?'':'checked'; ?>>
                                        <label for="leave_auth"></label>
                                        <span class="lbl_inner">No - It must be signed for</span>
                                    </label>
                                </div>
                            </td>
                        </tr>
                    <?php
                    }elseif( $over40 == false && $options['authority_option'] == 'No' ) { ?>
                        <input name="have_confirm" class="have_confirm woo_var_replaceable" type="hidden" value="3" checked="checked">
                    <?php
                    }elseif( $options['authority_option'] == 'Always' ) { ?>
                        <input name="leave_auth" class="have_permission" type="radio" value="1" checked style="display: none">
                    <?php
                    }
                    // Check forklift option
                    if( $over40 == true && $options['forklift_option'] == 'Yes' ) { ?>

                        <tr class="sqf-opts sqf-forklift-opt woo_var_replaceable">
                            <td>
                                <label class="label-control"><?php _e( 'Do you have a forklift?:', 'woobigpost' ); ?></label>

                                <div class="options_inner">
                                    <label class="radioitem">
                                        <input name="Forkliftsoption" type="radio" value="1" <?php echo $has_forklift?'checked':''; ?>>
                                        <label for="Forkliftsoption"></label>
                                        <span class="lbl_inner">Yes</span>
                                    </label>
                                    <label class="radioitem">
                                        <input name="Forkliftsoption" type="radio" value="0" <?php echo $has_forklift?'':'checked'; ?>>
                                        <label for="Forkliftsoption"></label>
                                        <span class="lbl_inner">No</span>
                                    </label><br />
                                </div>
                            </td>
                        </tr>

                    <?php } ?>
                    <tr class="no_variation woo_var_replaceable">
                        <td></td>
                    </tr>
            </table>

            <div class="sqf-suburb-box">
                <?php
                $textfieldValue = ( $tosuburb != '' && $topostcode != '' && $tostate != '' ) ? $tosuburb .' '.$tostate. ' - ' . $topostcode:'';

                //if( !is_checkout() ) {
                ?>
                <input type="text" name="to_suburb1" id="sqf-to-suburb1" autocomplete="off" placeholder="<?php _e("Type suburb name or postcode",'woobigpost'); ?>" value="<?php echo $textfieldValue; ?>" />
                <?php //} ?>

                <div id="sqf-suburb-list" style="display: none;"></div>

                <input type="hidden" id="toSuburb" name="ToSuburb" value="<?php echo $tosuburb; ?>" />
                <input type="hidden" id="toState" name="ToState" value="<?php echo $tostate; ?>" />
                <input type="hidden" id="toPostcode" name="ToPostcode" value="<?php echo $topostcode; ?>" />
                <input type="hidden" id="toPostcodeId" name="ToPostcodeId" value="<?php echo $topostcodeid; ?>" />
                <input type="hidden" id="toDepotId" name="ToDepotId" value="<?php echo $todepotid; ?>" />

                <input type="hidden" id="productID" name="ProductID" value="<?php echo $productid; ?>" />

                <?php wp_nonce_field( 'woocommerce-shipping-calculator', 'woocommerce-shipping-calculator-nonce' ); ?>
            </div>

            <div id="sqf-shipping-cost"><?php echo $shippingPrices_Display; ?></div>
            <?php
            if( is_checkout() ) { ?>
                <div class="shipping-type-result-div" id="shipping-type-result-div-checkout"></div>
            <?php } ?>
        </div>
    </div>
</div>