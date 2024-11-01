<?php
if( isset($settings['free_shipping_config']) && $settings['free_shipping_config'] == 'enable_specific_items'){
?>
<tr>
    <th><?php _e("Free Shipping", 'bigpost'); ?></th>
    <td>
        <label>
            <input class="free_shipping" type="checkbox" <?php if($free_shipping == '1'){ echo 'checked="checked"'; } ?> value="<?php echo $free_shipping;?>"/>
            <input class="free_shipping_hidden" type="hidden" name="woobigpost_free_shipping" value="<?php echo $free_shipping;?>"/>
        </label>
    </td>
</tr>
<?php } ?>
<tr>
    <th><label for="woobigpost-no-of-cartons">
            <?php _e( 'No. of Items', 'bigpost' ); ?>
        </label></th>
    <td>
        <input type="number" name="no_of_cartons" id="woobigpost-no-of-cartons" min="1" value="<?php  echo $no_of_cartons; ?>" />
    </td>

</tr>
<tr>
    <td colspan="2">
        <table class="wp-list-table widefat fixed quote_item_list_table" id="quote_item_list" style="width:auto;padding:10px;">

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
                <th class="<?php echo $class; ?>">Add MHP</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $packaging_types = array(
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

            $cartons__ = json_decode($carton_items);

            if( !empty($carton_items) && !empty($cartons__)) {

                $increment = 1;
                foreach($cartons__  as $item ) { ?>
                    <tr>
                        <td>Carton <?php echo $increment; ?></td>
                        <td><input type="number" name="carton_length[]" min="0.01" step="0.01" value="<?php echo $item->length; ?>" class="large-text" /></td>
                        <td><input type="number" name="carton_width[]" min="0.01" step="0.01" value="<?php echo $item->width; ?>" class="large-text" /></td>
                        <td><input type="number" name="carton_height[]" min="0.01" step="0.01" value="<?php echo $item->height; ?>" class="large-text" /></td>
                        <td><input type="number" name="carton_weight[]" min="0.01" step="0.01" value="<?php echo $item->weight; ?>" class="large-text" /></td>

                        <td>
                            <select name="packaging_type[]">
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

                            <select name="consolidated[]">
                                <option value="No" <?php selected( 'No', $item->consolidated ); ?>>No</option>
                                <option value="Yes" <?php selected( 'Yes', $item->consolidated ); ?>>Yes</option>
                            </select>
                        </td>

                        <td class="<?php echo $class; ?>">

                            <?php
                            /*if( $settings['is_advanced_mode'] == "No" ) {
                                $item->consolidated = 'No';
                            }*/ ?>

                            <select name="mhp[]">
                                <option value="No" <?php selected( 'No', $item->mhp ); ?>>No</option>
                                <option value="Yes" <?php selected( 'Yes', $item->mhp ); ?>>Yes</option>
                            </select>
                        
                    </tr>

                    <?php
                    $increment++;
                }

                // If empty cartons array add defaults
            } else { ?>

                <tr>
                    <td>Item 1</td>
                    <td><input type="number" name="carton_height[]" min="0.01" step="0.01" class="large-text" /></td>
                    <td><input type="number" name="carton_width[]" min="0.01" step="0.01" class="large-text" /></td>
                    <td><input type="number" name="carton_length[]" min="0.01" step="0.01" class="large-text" /></td>
                    <td><input type="number" name="carton_weight[]" min="0.01" step="0.01"  class="large-text" /></td>

                    <td>
                        <select name="packaging_type[]">
                            <?php
                            foreach( $packaging_types as $key_package => $value_package ) {
                                echo '<option value="'.$value_package.'">'.$key_package.'</option>';
                            } ?>
                        </select>
                    </td>
                    <td class="<?php echo $class; ?>">
                        <select name="consolidated[]">
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