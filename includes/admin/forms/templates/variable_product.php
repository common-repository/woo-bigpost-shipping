<?php
$variations = $product_data->get_available_variations();
if(!empty($variations)){
    foreach($variations as $variation){
        $variationID = $variation['variation_id'];
        $title = '#'.$variationID;
        $no_cartons = get_post_meta( $variationID, '_no_of_cartons', true ) != "" ? get_post_meta( $variationID, '_no_of_cartons', true ) : 1;
        $carton_items = get_post_meta( $variationID, '_carton_items', true );
        $free_shipping = get_post_meta( $variationID, '_free_shipping', true );
        ?>
        <tr class="var_<?php echo $variationID;?>">
            <th colspan="2" style="border-bottom: 1px solid #ccc"><?php echo $title;?> </th>
        </tr>
        <?php
        if($settings['free_shipping_config'] == 'enable_specific_items'){
            ?>
            <tr class="var_<?php echo $variationID;?>">
                <th><?php _e("Free Shipping", 'bigpost'); ?></th>
                <td>
                    <label>
                        <input class="free_shipping" type="checkbox" value="<?php echo $free_shipping;?>" <?php if($free_shipping == '1'){ echo 'checked="checked"'; } ?>/>
                        <input class="free_shipping_hidden" type="hidden" name="woobigpost_variable_free_shipping[<?php echo $variationID;?>]" value="<?php echo $free_shipping;?>"/>
                    </label>
                </td>
            </tr>
        <?php } ?>
        <tr class="var_<?php echo $variationID;?>">
            <th><label for="woobigpost-no-of-cartons"><?php _e( 'No. of Items', 'bigpost' ); ?></label></th>
            <td>
                <input type="number" class="woobigpost-variation-no-of-cartons" style="" name="woobigpost-variation-no-of-cartons[<?php echo $variationID;?>]" id="woobigpost-variation-no-of-cartons_<?php echo $variationID;?>" value="<?php echo $no_cartons; ?>" placeholder="" step="1" min="1">
            </td>
        </tr>
        <tr class="var_carton_row_settings var_<?php echo $variationID;?>">
            <td colspan="2">
                <table class="wp-list-table widefat fixed variation_quote_item_list_table" id="_variation_quote_item_list" style="width:auto;padding:10px;">
                    <?php
                    $class = "";
                    if( $settings['is_advanced_mode'] == "No" ) {
                        $class = "woobigpost-admin-no";
                    } ?>

                    <thead>
                    <tr>
                        <th>Item #</th>
                        <th>Length(cm)</th>
                        <th>Width(cm)</th>
                        <th>Height(cm)</th>
                        <th>Weight(kg)</th>
                        <th><strong>Package Type</strong></th>
                        <th class="<?php echo $class; ?>" style="display: none;">Consolidated?</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
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

                    if(	!empty($carton_items) ) {

                        $increment = 1;
                        foreach( json_decode($carton_items) as $item ) { ?>
                            <tr>
                                <td>Item <?php echo $increment; ?></td>
                                <td><input type="number" name="carton_length[<?php echo $variationID; ?>][]" min="0.01" step="0.01"  value="<?php echo $item->length; ?>" class="large-text" /></td>
                                <td><input type="number" name="carton_width[<?php echo $variationID; ?>][]" min="0.01" step="0.01"  value="<?php echo $item->width; ?>" class="large-text" /></td>
                                <td><input type="number" name="carton_height[<?php echo $variationID; ?>][]" min="0.01" step="0.01"  value="<?php echo $item->height; ?>" class="large-text" /></td>
                                <td><input type="number" name="carton_weight[<?php echo $variationID; ?>][]" min="0.01" step="0.01"  value="<?php echo $item->weight; ?>" class="large-text" /></td>

                                <td>
                                    <select name="packaging_type[<?php echo $variationID ;?>][]">
                                        <?php
                                        foreach( $packaging_types as $key_package => $value_package ) {
                                            $selected = '';
                                            if( $item->packaging_type == $value_package ) $selected = 'selected="selected"';

                                            echo '<option value="'.$value_package.'" '.$selected.'>'.$key_package.'</option>';
                                        } ?>
                                    </select>
                                </td>

                                <td class="<?php echo $class; ?>" style="display: none;">

                                    <?php
                                    /*if( $settings['is_advanced_mode'] == "No" ) {
                                        $item->consolidated = 'No';
                                    }*/ ?>

                                    <select name="consolidated[<?php echo $variationID ;?>][]">
                                        <option value="No" <?php selected( 'No', $item->consolidated ); ?>>No</option>
                                        <option value="Yes" <?php selected( 'Yes', $item->consolidated ); ?>>Yes</option>
                                    </select>
                                </td>
                            </tr>

                            <?php
                            $increment++;
                        }

                        // If empty cartons array add defaults
                    } else { ?>

                        <tr class="var_<?php echo $variationID;?>">
                            <td>Carton 1</td>
                            <td><input type="number" name="carton_length[<?php echo $variationID ;?>][]" min="0.01" step="0.01"  class="large-text" /></td>
                            <td><input type="number" name="carton_width[<?php echo $variationID ;?>][]" min="0.01" step="0.01"  class="large-text" /></td>
                            <td><input type="number" name="carton_height[<?php echo $variationID ;?>][]" min="0.01" step="0.01"  class="large-text" /></td>
                            <td><input type="number" name="carton_weight[<?php echo $variationID ;?>][]" min="0.01" step="0.01"  step="0.01" class="large-text" /></td>

                            <td>
                                <select name="packaging_type[<?php echo $variationID ;?>][]">
                                    <?php
                                    foreach( $packaging_types as $key_package => $value_package ) {
                                        echo '<option value="'.$value_package.'">'.$key_package.'</option>';
                                    } ?>
                                </select>
                            </td>
                            <td class="<?php echo $class; ?>"  style="display: none;">
                                <select name="consolidated[<?php echo $variationID ;?>][]">
                                    <option value="No">No</option>
                                    <option value="Yes">Yes</option>
                                </select>
                            </td>
                        </tr>
                    <?php
                    } ?>
                    </tbody>
                </table>
            </td>
        </tr>
    <?php
    }
}
?>