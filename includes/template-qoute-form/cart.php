<tr class="cart-bigpost-shipping">
    <th>Big Post Shipping</th>
    <td data-title="Big Post Shipping">
        <div id="<?php echo $idWrapper ?>">
            <input type="hidden" id="exclude_tax" value="<?php echo $settings['tax_exclusive_cart']; ?>">
            <div id="<?php echo $uniqueID; ?>" class="<?php echo $class; ?> cart">
                <div id="woobigpost-ajax-loader" style="background-image: url( <?php echo admin_url(); ?>images/spinner.gif );"></div>
                <div class="woobigpost-sqf-body">
                    <table class="woobigpost-sqf-fields">
                        <tr>
                            <td>
                                <?php if( in_array('HDS', $options['shipping_types']) || in_array('BUSINESS', $options['shipping_types']) ) { $no_hds_business = false; ?>
                        <label class="label-control"><?php _e( 'The delivery address is a:', 'woobigpost' ); ?></label>
                        <?php } else { $no_hds_business = true; ?>
                        <label class="label-control"><?php _e( 'Home Delivery and Business shipping not available.', 'woobigpost' ); ?></label>
                        <?php } ?>
                                
                        <?php
                        if( in_array('HDS', $options['shipping_types']) ) { ?>
                                <label class="radioitem">
                                    <input name="BuyerIsBusiness" type="radio" value="0" <?php checked($buyer_is_business, '0'); ?> />
                                    <label for="BuyerIsBusiness"></label>
                                    <span class="lbl_inner">Home</span>
                                </label>
                        <?php } ?>
                                <?php
                                if( in_array('BUSINESS', $options['shipping_types']) ) { ?>
                                    <label class="radioitem">
                                        <input name="BuyerIsBusiness" type="radio" value="1" <?php checked($buyer_is_business, '1'); ?> />
                                        <label for="BuyerIsBusiness"></label>
                                        <span class="lbl_inner">Business</span>
                                    </label>
                                <?php }?>
                                
                                <?php if( !in_array('HDS', $options['shipping_types']) && !in_array('BUSINESS', $options['shipping_types']) ) { ?>
                            <input name="BuyerIsBusiness" type="radio" value="0" style="display: none;" checked />
                            <input name="leave_auth" class="have_permission" type="radio" value="1"  style="display: none;" checked />
                        <?php }else{ ?>
                        <p class="small-text">Select from a home delivery or a delivery to your business address.</p>
                        <?php } ?>
                                
                            </td>
                        </tr>

                        <?php
                        // check weight is grator then 40
                        if( $over40 == true && !$no_hds_business ) { ?>

                            <tr  class="sqf-opts sqf-confirm-opt">
                                <td>
                                    <label class="label-control"><?php _e("Due to weight of the items, please confirm the following:", 'woobigpost'); ?></label>

                                    <div class="sqf-opt-desc">
                                        <?php _e("1. My driveway is sealed with an even surface", 'woobigpost'); ?><br/>
                                        <?php _e("2. There is reasonable vehicle access with height clearance.", 'woobigpost'); ?><br/>
                                        <?php _e("3. I acknowledge the delivery is to the front door,  carport, or closest practical delivery point as deemed by the     driver.", 'woobigpost'); ?>
                                    </div>

                                    <div class="options_inner">
                                        <label class="radioitem">
                                            <input name="have_confirm" class="have_confirm" type="radio" value="1" <?php checked( $have_confirm, '1' ); ?>>
                                            <label for="have_confirm"></label>
                                            <span class="lbl_inner">Yes - All of these are true</span>
                                        </label><br/>
                                        <label class="radioitem">
                                            <input name="have_confirm" class="have_confirm" type="radio" value="0" <?php checked( $have_confirm, '0' ); ?>>
                                            <label for="have_confirm"></label>
                                            <span class="lbl_inner">No - one or more of these are not true</span>
                                        </label>
                                        <input name="leave_auth" class="have_permission" type="radio" value="<?php echo $options['authority_option'] == 'No'?0:1; ?>" checked style="display: none">
                                        
                                        <input name="Forkliftsoption" type="radio" value="0" checked style="display: none">
                                    </div>
                                </td>
                            </tr>
                            <tr class="sqf-confirm-no"><td></td></tr>

                            <?php
                            // check authority option
                        } elseif( $over40 == false && $options['authority_option'] == 'Yes' && !$no_hds_business ) { ?>
                            <input name="have_confirm" class="have_confirm" type="hidden" value="3">
                            <tr class="sqf-opts sqf-authority-opt">
                                <td>
                                    <label class="label-control"><?php _e( "Do we have permission to leave the package in a safe place?:", 'woobigpost' ); ?></label>

                                    <div class="options_inner">
                                        <label class="radioitem">
                                            <input name="leave_auth" class="have_permission" type="radio" value="1" <?php checked( $leave_auth, '1' ); ?>>
                                            <label for="leave_auth"></label>
                                            <span class="lbl_inner">Yes - You may leave at the front door</span>
                                        </label><br/>

                                        <label class="radioitem">
                                            <input name="leave_auth" class="have_permission" id="permission_no" type="radio" value="0" <?php checked( $leave_auth, '0' ); ?>>
                                            <label for="leave_auth"></label>
                                            <span class="lbl_inner">No - It must be signed for</span>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        <?php
                        }elseif( $over40 == false && $options['authority_option'] == 'No' ) { ?>
                            <input name="have_confirm" class="have_confirm" type="hidden" value="3" checked="checked">
                        <?php
                        }elseif( $options['authority_option'] == 'Always' ) { ?>
                            <input name="leave_auth" class="have_permission" type="radio" value="1" checked style="display: none">
                        <?php
                        }

                        // Check forklift option
                        if( $over40 == true && $options['forklift_option'] == 'Yes' ) { ?>

                            <tr class="sqf-opts sqf-forklift-opt">
                                <td>
                                    <label class="label-control"><?php _e( 'Do you have a forklift?:', 'woobigpost' ); ?></label>

                                    <div class="options_inner">
                                        <label class="radioitem">
                                            <input name="Forkliftsoption" type="radio" value="1" <?php checked( $has_forklift, '1' ); ?>>
                                            <label for="Forkliftsoption"></label>
                                            <span class="lbl_inner">Yes</span>
                                        </label>
                                        <label class="radioitem">
                                            <input name="Forkliftsoption" type="radio" value="0" <?php checked( $has_forklift, '0' ); ?>>
                                            <label for="Forkliftsoption"></label>
                                            <span class="lbl_inner">No</span>
                                        </label><br />
                                    </div>
                                </td>
                            </tr>

                        <?php } ?>
                    </table>

                    <div class="sqf-suburb-box">
                        <?php
                        $textfieldValue = ( $tosuburb != '' && $topostcode != '' && $tostate != '' ) ? $tosuburb .' '.$tostate. ' - ' . $topostcode:'';

                        if( is_checkout() ) {
                        ?>
                            <!-- <ul id="sqf-suburb-list"></ul> -->
                        <?php } ?>

                        
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
    </td>
</tr>