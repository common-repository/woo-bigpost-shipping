<div class="options_group form-row form-row-full">
    <?php
    woocommerce_wp_text_input(
        array(
            'name'              => 'woobigpost-variation-no-of-cartons['.$variation->ID.']',
             'id'               => 'woobigpost-variation-no-of-cartons_'.$variation->ID,
             'class'            =>'woobigpost-variation-no-of-cartons',
            'label'             => __( 'No. of Items', 'woocommerce' ),
            'placeholder'       => '',
            'type'              => 'number',
            'custom_attributes' => array(
                'step' 	=> '1',
                'min'	=> '1'
            ),
            'value'=>1
        )
    );
    ?>
    <table class="wp-list-table widefat fixed variation_quote_item_list_table" id="_variation_quote_item_list">

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
            <th class="<?php echo $class; ?>">Consolidated?</th>
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
                <tr class="form-row dimensions_field">
                    <td>Item <?php echo $increment; ?></td>
                    <td>
                        <?php
                        woocommerce_wp_text_input(
                            array(
                                'name'                => 'carton_length['.$variation->ID.'][]',
                                'id'                  =>'var_'.$variation->ID.'_carton_length_'.$increment,
                                'placeholder'       => '',
                                'type'              => 'number',
                                'label'             =>'',
                                'custom_attributes' => array(
                                    'step' 	=> '1',
                                    'min'	=> '1'
                                ),
                                'value' =>$item->length
                            )
                        );
                        ?>
                    </td>
                    <td>
                        <?php
                        woocommerce_wp_text_input(
                            array(
                                'name'                => 'carton_width['.$variation->ID.'][]',
                                'id'                  =>'var_'.$variation->ID.'_carton_width_'.$increment,
                                'placeholder'       => '',
                                'type'              => 'number',
                                'label'             =>'',
                                'custom_attributes' => array(
                                    'step' 	=> '1',
                                    'min'	=> '1'
                                ),
                                'value' =>$item->width
                            )
                        );
                        ?>
                    </td>
                    <td>
                        <?php
                        woocommerce_wp_text_input(
                            array(
                                'name'                => 'carton_height['.$variation->ID.'][]',
                                'id'                  =>'var_'.$variation->ID.'_carton_height_'.$increment,
                                'placeholder'       => '',
                                'type'              => 'number',
                                'label'             =>'',
                                'custom_attributes' => array(
                                    'step' 	=> '1',
                                    'min'	=> '1'
                                ),
                                'value' =>$item->height
                            )
                        );
                        ?>
                    </td>
                    <td>
                        <?php
                        woocommerce_wp_text_input(
                            array(
                                'name'                => 'carton_weight['.$variation->ID.'][]',
                                'id'                  =>'var_'.$variation->ID.'_carton_weight_'.$increment,
                                'placeholder'       => '',
                                'type'              => 'number',
                                'label'             =>'',
                                'custom_attributes' => array(
                                    'step' 	=> '1',
                                    'min'	=> '1'
                                ),
                                'value' =>$item->weight
                            )
                        );
                        ?>
                    </td>
                    <td>
                        <select name="packaging_type[<?php echo $variation->ID ;?>][]">
                            <?php
                            foreach( $packaging_types as $key_package => $value_package ) {
                                $selected = '';
                                if( $item->packaging_type == $value_package ) $selected = 'selected="selected"';

                                echo '<option value="'.$value_package.'" '.$selected.'>'.$key_package.'</option>';
                            } ?>
                        </select>
                    </td>
                    <td class="<?php echo $class; ?>">
                        <?php
                        /*if( $settings['is_advanced_mode'] == "No" ) {
                            $item->consolidated = 'No';
                        }*/ ?>

                        <select name="consolidated[<?php echo $variation->ID ;?>][]">
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
            <tr class="form-row dimensions_field">
                <td>Carton 1</td>
                <td>
                    <?php
                    woocommerce_wp_text_input(
                        array(
                            'name'                => 'carton_length['.$variation->ID.'][]',
                            'id'                => 'var_'.$variation->ID.'_carton_length_1',
                            'placeholder'       => '',
                            'type'              => 'number',
                            'label'             => '',
                            'custom_attributes' => array(
                                'step' 	=> '1',
                                'min'	=> '1'
                            ),
                            'value'=>''
                        )
                    );
                    ?>
                </td>
                <td>
                   <?php
                    woocommerce_wp_text_input(
                        array(
                            'name'                => 'carton_width['.$variation->ID.'][]',
                            'id'                => 'var_'.$variation->ID.'_carton_width_1',
                            'placeholder'       => '',
                            'type'              => 'number',
                            'label'             => '',
                            'custom_attributes' => array(
                                'step' 	=> '1',
                                'min'	=> '1'
                            ),
                            'value'=>''
                        )
                    );
                    ?>
                </td>
                <td>
                    <?php
                    woocommerce_wp_text_input(
                        array(
                            'name'                => 'carton_height['.$variation->ID.'][]',
                            'id'                => 'var_'.$variation->ID.'_carton_height_1',
                            'placeholder'       => '',
                            'type'              => 'number',
                            'label'             => '',
                            'custom_attributes' => array(
                                'step' 	=> '1',
                                'min'	=> '1'
                            ),
                            'value'=>''
                        )
                    );
                    ?>
                </td>
                <td>
                    <?php
                    woocommerce_wp_text_input(
                        array(
                            'name'                => 'carton_weight['.$variation->ID.'][]',
                            'id'                => 'var_'.$variation->ID.'_carton_weight_1',
                            'placeholder'       => '',
                            'type'              => 'number',
                            'label'             => '',
                            'custom_attributes' => array(
                                'step' 	=> '1',
                                'min'	=> '1'
                            ),
                            'value'=>''
                        )
                    );
                    ?>
                </td>

                <td>
                    <select name="packaging_type[<?php echo $variation->ID ;?>][]">
                        <?php
                        foreach( $packaging_types as $key_package => $value_package ) {
                            echo '<option value="'.$value_package.'">'.$key_package.'</option>';
                        } ?>
                    </select>
                </td>
                <td class="<?php echo $class; ?>">
                    <select name="consolidated[<?php echo $variation->ID ;?>][]">
                        <option value="No">No</option>
                        <option value="Yes">Yes</option>
                    </select>
                </td>
            </tr>
        <?php
        } ?>
        </tbody>
    </table>
</div>