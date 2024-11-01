jQuery( document ).ready( function($) {

	// Hide show admin settings
	woobigpost_hide_show_admin_settings();
	var settingdElements = '.woocommerce_woobigpost_shipping_types, #woocommerce_woobigpost_margin_type, .is_shipping_margin, .woocommerce_woobigpost_product_page';
	$( document ).on( 'change', settingdElements, function() {
		woobigpost_hide_show_admin_settings( $(this) );
	} );

    if($('#woocommerce_woobigpost_use_cis').prop('checked')){
        $('.form-table').hide();
        $('.wc-settings-sub-title').hide();
        $('#woocommerce_woobigpost_use_cis').parents('.form-table').show();
        $('.form-table').first().show();
        $('#woocommerce_woobigpost_1').show();
    }else{
        $('.form-table').show();
        $('.wc-settings-sub-title').show();
    }

    $(document).on('change', '#woocommerce_woobigpost_use_cis', function(){
        var thisObj = $( this );
        if(thisObj.prop('checked')){
            $('.form-table').hide();
            $('.wc-settings-sub-title').hide();
            thisObj.parents('.form-table').show();
            $('.form-table').first().show();
            $('#woocommerce_woobigpost_1').show();
        }else{
            $('.form-table').show();
            $('.wc-settings-sub-title').show();
        }
    });

    // Advamce shipping margin add row
    $( document ).on( 'click', '#advanced_bigpost_margin a.add', function() {

        var thisObj = $( this );
        var tbodyObj = thisObj.parents( '#advanced_margin_table' ).find( 'table tbody' );

        var index 		= tbodyObj.find( 'tr:last-child input[type="number"]' ).attr( 'data-index' );
        index 			= parseInt( index ) + 1;

        var first_range = tbodyObj.find( 'tr:last-child input[type="number"]' ).attr( 'data-range' );
        var last_range 	= parseInt( first_range ) + 5;

        // Changes in last row
        tbodyObj.find( 'tr:last-child td:first-child' ).html( '[' + first_range + ' - ' + last_range + ']' );
        tbodyObj.find( 'tr:last-child .range-end' ).val( last_range );

        // Clone row and made changes
        var newRow = tbodyObj.find( 'tr:last-child' ).clone( true );
        newRow.find( 'td:first-child' ).html( '[ Above - ' + last_range + ']' );
        newRow.find( 'input[type="number"]' ).val('1').attr( 'data-range', last_range ).attr( 'data-index', index ).attr( 'name', 'woocommerce_woobigpost_advanced_margin_values['+index+'][value]' );
        newRow.find( 'input.range-from' ).val( last_range ).attr( 'name', 'woocommerce_woobigpost_advanced_margin_values['+index+'][range_from]' );
        newRow.find( 'input.range-end' ).val( '' ).attr( 'name', 'woocommerce_woobigpost_advanced_margin_values['+index+'][range_end]' );

        tbodyObj.append( newRow );

        return false;
    } );

    // Remove last range
    $( document ).on( 'click', '#advanced_bigpost_margin .curent-range', function() {

        var thisObj = $( this );
        var tbodyObj = thisObj.parents( '#advanced_margin_table' ).find( 'table tbody' );

        tbodyObj.find( 'tr:last-child' ).remove();

        var first_range = tbodyObj.find( 'tr:last-child input[type="number"]' ).attr( 'data-range' );

        tbodyObj.find( 'tr:last-child td:first-child' ).html( '[Above - ' + first_range + ']' )
        tbodyObj.find( 'tr:last-child input.range-end' ).val( '' );
    } );

    // Find suburb
    $( document ).on( 'click', '#bigpost_find_suburb', function(e) {
        e.preventDefault();
        var val = $('#woocommerce_woobigpost_from_suburb').val().length;

        if( val > 3 ) {
            var data = {
                'action': 'woobigpost_get_suburb_list',
                'type': 'from',
                'value': $( '#woocommerce_woobigpost_from_suburb' ).val()
            };

            $( "#loader-quote-product" ).fadeIn( "slow" );

            $.post( ajaxurl, data, function(response) {
                $( "#loader-quote-product" ).fadeOut( "slow" );
                $( "#from_suburb_list" ).html( response ).fadeIn( "slow" );
            } );
        }
    });

    // Find suburb
    $( document ).on( 'click', '.bigpost_find_suburb', function(e) {
        e.preventDefault();
        var elem = $(this);
        var index = elem.siblings('input.key_val').val();
        var suburb = elem.closest('td').siblings('td').find('.from_suburb');

        if( suburb.val().length > 3 ) {
            var data = {
                'action': 'woobigpost_get_suburb_list',
                'type': 'from',
                'value': suburb.val(),
                'index': index

            };

            elem.next('.loader-quote-product').fadeIn( "slow" );
            elem.siblings( ".from_suburb_list" ).html('');

            $.post( ajaxurl, data, function(response) {
                elem.next('.loader-quote-product').fadeOut( "slow" );
                elem.siblings( ".from_suburb_list" ).html( response ).fadeIn( "slow" );
            } );
        }
    });

    /* set selected suburb from list */
    $( document ).on( "click", ".from_suburb_list .woo-bp-suburb-item", function() {
        var suburb		= $(this).attr( 'data-suburb' );
        var postcodeid	= $(this).val();
        var state		= $(this).attr( 'data-state' );
        var postcode	= $(this).attr( 'data-postcode' );

        var parent = $(this).closest('td').siblings('td');

        parent.find('input.from_suburb').val( suburb + ' - ' + postcode );
        $(this).closest('tr').find('input.from_suburb_addr').val( suburb );
        $(this).closest('tr').find('input.from_state').val( state );
        $(this).closest('tr').find('input.from_postcode_id').val( postcodeid );
        $(this).closest('tr').find('input.from_post_code').val( postcode );
        $(this).closest('.from_suburb_list').hide();

    });

    /* Add / Remove rows for Cartons on product meta */
    $( document ).on( 'change', '#woobigpost-no-of-cartons', function() {

        var no_of_cartons 	= $( this ).val();
        var rowCount 		= $( '#quote_item_list tbody tr' ).length;
        var consolClass		= '';
        if( $('#quote_item_list tbody tr td:last-child').hasClass('woobigpost-admin-no') ) {
            consolClass = 'woobigpost-admin-no';
        }

        if( no_of_cartons > 0 ) {
            if( no_of_cartons > rowCount ) {

                var row_diff = ( no_of_cartons - rowCount );

                for( var i = 1; i <= row_diff; i++ ) {

                    var fieldHTMl = '<tr>';
                    fieldHTMl += '<td>Carton '+(rowCount+i)+'</td>';
                    fieldHTMl += '<td><input type="number" name="carton_length[]" min="1" class="large-text" /></td>';
                    fieldHTMl += '<td><input type="number" name="carton_width[]" min="1" class="large-text" /></td>';
                    fieldHTMl += '<td><input type="number" name="carton_height[]" min="1" class="large-text" /></td>';
                    fieldHTMl += '<td><input type="number" name="carton_weight[]" min="1" class="large-text" /></td>';
                    fieldHTMl += '<td><select name="packaging_type[]">';
                    fieldHTMl += '<option value="0">Carton</option>';
                    fieldHTMl += '<option value="1">Skid</option>';
                    fieldHTMl += '<option value="2">Pallet</option>';
                    fieldHTMl += '<option value="3">Pack</option>';
                    fieldHTMl += '<option value="4">Crate</option>';
                    fieldHTMl += '<option value="5">Roll</option>';
                    fieldHTMl += '<option value="6">Satchel</option>';
                    fieldHTMl += '<option value="7">Stillage</option>';
                    fieldHTMl += '<option value="8">Tube</option>';
                    fieldHTMl += '<option value="9">Bag</option>';
                    fieldHTMl += '</select></td>';
                    fieldHTMl += '<td class="'+consolClass+'"  style="display: none;"><select name="consolidated[]">';
                    fieldHTMl += '<option value="No">No</option>';
                    fieldHTMl += '<option value="Yes">Yes</option>';
                    fieldHTMl += '</select></td>';
                    fieldHTMl += '</tr>';

                    $( '#quote_item_list tbody' ).append( fieldHTMl );
                }
            } else if( no_of_cartons < rowCount ) {

                var row_diff = ( rowCount - no_of_cartons );
                for( var i=0 ; i < row_diff ; i++ ) {
                    $( '#quote_item_list tbody tr:last' ).remove();
                }
            }
        }
    });

    //for bigpost product box migration
    $(document).on('click','#woobigpost-product-box_btn',function(){
        var thisObj = $(this);
        thisObj.prop('disabled',true);
        var message = $('#woobigpost-product-box_overwrite_settings').is(':checked') ? 'Please do not refresh page,  settings migration is running.': 'Use the below button to copy all of your woocommerce box settings across to the bigpost box settings all at once.';
        $('.woobigpost-product-box-msg p').html(message);
        thisObj.find('span').addClass('spin');
        $('#woobigpost-product-box_form').submit();

    });

    $(document).on('click','#woobigpost-product-box_overwrite_settings',function(){
        if($(this).is(':checked')){
            $(this).val('yes');
        } else {
            $(this).val('no');
        }
    });

    //when add location button is clicked
    $('.add_location_repeater').click(function(e){
        e.preventDefault();
        var elem = $(this).closest('tr').siblings('tr:first').clone();
        var last_id = $(this).closest('tr').siblings('tr.row_loc:last').find('input.key_val').val();
        elem.find('.from_suburb_list').html('');
        $('<button class="button button-primary remove_location">&minus;</button>').insertBefore(elem.find('button.bigpost_find_suburb'));
        $("<span>&nbsp;</span>").insertBefore(elem.find('button.bigpost_find_suburb'));
        elem.find("input").val('');
        var key_id = parseInt(last_id) + 1;
        var final = elem.find('input.key_val').val(key_id);
        final.end().insertBefore($(this).closest('tr'));
    });

    //when Remove location button is clicked
    $(document).on('click','.remove_location', function(e){
        e.preventDefault();
        var result = confirm("Are you sure you want to remove location?");
        if (result) {
            $(this).closest('tr').remove();
        }

    });

    $("#moveto_location").click(function(e) {
        e.preventDefault();
        $([document.documentElement, document.body]).animate({
            scrollTop: $("#woocommerce_woobigpost_12").offset().top
        }, 2000);
    });

    //for product variation carton setting
    $( document ).on( 'change', '.woobigpost-variation-no-of-cartons', function() {
        var no_of_cartons 	= $( this ).val();
        var carton_row      = $( this).closest('tr').next('tr.var_carton_row_settings');
        var rowCount 		= carton_row.find('tbody tr').length;
        var consolClass		= '';
        if(  carton_row.find('tbody tr td:last-child').hasClass('woobigpost-admin-no') ) {
            consolClass = 'woobigpost-admin-no';
        }

        if( no_of_cartons > 0 ) {
            if( no_of_cartons > rowCount ) {
                var row_diff = ( no_of_cartons - rowCount );

                for( var i = 1; i <= row_diff; i++ ) {
                    var row_clone = carton_row.find('tbody tr:first-child').clone();
                    var carton_num = rowCount+i;
                    row_clone.find('td:first-child').text('Carton '+ carton_num);
                    row_clone.find('td input').val('');
                    /*row_clone.find('td input').each(function(index){
                     //var current_id = $(this).attr('id');
                     //var new_id = current_id.replace(/.$/,no_of_cartons);
                     $(this).val('');
                     });*/
                    var fieldHTML = row_clone;
                    carton_row.find('tbody').append( fieldHTML );
                }

            } else if( no_of_cartons < rowCount ) {
                var row_diff = ( rowCount - no_of_cartons );
                for( var i=0 ; i < row_diff ; i++ ) {
                    carton_row.find('tbody tr:last').remove();
                }
            }
        }
    });

    $( document ).on( 'click', '.free_shipping', function() {
        if($(this).is(':checked')){
            $(this).next('.free_shipping_hidden').val('1');
        } else {
            $(this).next('.free_shipping_hidden').val('0');
        }
    });

    $( document ).on( 'click', '.show_plugin', function() {
        if($(this).is(':checked')){
            $(this).next('.show_plugin_hidden').val('1');
        } else {
            $(this).next('.show_plugin_hidden').val('0');
        }
    });

    /*$( document ).on( 'click', '.shipping_location', function() {
     if($(this).is(':checked')){
     $(this).next('.shipping_location_hidden').val('1');
     } else {
     $(this).next('.shipping_location_hidden').val('0');
     }
     });*/

    $( document ).on( 'click', '.woocommerce_woobigpost_free_shipping_config', function() {
        if($(this).val() == 'disabled'){
            $('.field_woocommerce_woobigpost_free_shipping_config').siblings().hide();
            $('.woocommerce_woobigpost_free_shipping_mixed_cart_option').prop('required',false);
            $('.woocommerce_woobigpost_free_shipping_freight_cost_condition').prop('required',false);
        } else {
            jQuery('.field_woocommerce_woobigpost_free_shipping_config').siblings().show();

            if($(this).val() == 'enable_specific_items' || $(this).val() == 'enable_all'){
                $('.woocommerce_woobigpost_free_shipping_mixed_cart_option ').prop('required',true);
            } else {
                $('.woocommerce_woobigpost_free_shipping_mixed_cart_option ').prop('required',false);
            }
        }

    });

    $( document ).on( 'change', '#woocommerce_woobigpost_free_shipping_max_freight_cost', function() {
        if($(this).val() != "" && $(this).val() > 0 ){
            $('.field_woocommerce_woobigpost_free_shipping_freight_cost_condition').show();
            $('.woocommerce_woobigpost_free_shipping_freight_cost_condition ').prop('required',true);
        } else {
            $('.woocommerce_woobigpost_free_shipping_freight_cost_condition ').prop('required',false);
            $('.field_woocommerce_woobigpost_free_shipping_freight_cost_condition').hide();
        }
    });

    $(document).on('woocommerce_variations_added', function(event) {
        var elem = $('.variation-needs-update:first-child');
        var id = elem.find('.remove_variation').attr('rel');
    });

    $('#woocommerce-product-data').on('woocommerce_variations_saved', function(event) {
        var post_id = $('.woobigpost_metabox').data('post');
        var e = {
            action: "woobigpost_metabox_update",
            post_id: post_id
        };

        jQuery( '#woobigpost-ajax-loader' ).fadeIn( 'slow' );
        jQuery.post(ajaxurl, e, function(response) {
            jQuery( '#woobigpost-ajax-loader' ).fadeOut( 'slow' );
            $('.woobigpost_metabox').find('tr[class^=var_]').remove();
            $('.woobigpost_metabox').append(response);
        });

    });

    if($('#woocommerce_woobigpost_display_tax_exclusive').prop('checked')){
        jQuery('#woocommerce_woobigpost_apply_woo_tax').removeAttr('disabled');
    }else{
        jQuery('#woocommerce_woobigpost_apply_woo_tax').attr('disabled', 'disabled');
    }

    $('#woocommerce_woobigpost_display_tax_exclusive').on('change', function(){
        if($(this).prop('checked')){
            jQuery('#woocommerce_woobigpost_apply_woo_tax').removeAttr('disabled');
        }else{
            jQuery('#woocommerce_woobigpost_apply_woo_tax').attr('disabled', 'disabled');
        }
    });

} ); // End Document ready

/**
 * Manage Hide show settings
 */
function woobigpost_hide_show_admin_settings( $obj = '' ) {

    // Shipping types blocks hide show
    jQuery( '.woocommerce_woobigpost_shipping_types' ).each( function() {
        var shippinType		= jQuery( this ).val();
        var shippinTypeLaw	= shippinType.toLowerCase();

        if( jQuery(this).is(':checked') ) {
            jQuery( '.' + shippinTypeLaw + '_shipping' ).parents( 'tr' ).fadeIn();
            jQuery( '.' + shippinTypeLaw + '_shipping.wc-settings-sub-title' ).fadeIn();
        } else {
            jQuery( '.' + shippinTypeLaw + '_shipping' ).parents( 'tr' ).hide();
            jQuery( '.' + shippinTypeLaw + '_shipping.wc-settings-sub-title' ).hide();
        }
    } );

    // Shipping Margin
    var is_ShippingMargin = jQuery( '.is_shipping_margin' ).val();
    if( is_ShippingMargin == 'No' ) {
        jQuery( '.shipping_margin' ).parents( 'tr' ).hide();
        jQuery( '#advanced_bigpost_margin' ).parents( 'tr' ).hide();
    } else {
        jQuery( '.shipping_margin' ).parents( 'tr' ).fadeIn();
        //jQuery( '#advanced_bigpost_margin' ).parents( 'tr' ).fadeIn();

        // Shipping mode ( simple or advance )
        var shippingMode = jQuery( '#woocommerce_woobigpost_margin_type' ).val();
        if( shippingMode == 'Simple' ) {
            jQuery( '#woocommerce_woobigpost_margin_value_simple' ).parents( 'tr' ).fadeIn();
            jQuery( '#advanced_bigpost_margin' ).parents( 'tr' ).hide();
        } else {
            jQuery( '#woocommerce_woobigpost_margin_value_simple' ).parents( 'tr' ).hide();
            jQuery( '#advanced_bigpost_margin' ).parents( 'tr' ).fadeIn();
        }
    }

    //free shipping options
    var free_ship_config = jQuery('.woocommerce_woobigpost_free_shipping_config:checked').val();
    if(free_ship_config == 'disabled'){
        jQuery('.field_woocommerce_woobigpost_free_shipping_config').siblings().hide();
        jQuery('.woocommerce_woobigpost_free_shipping_mixed_cart_option').prop('required',false);
        jQuery('.woocommerce_woobigpost_free_shipping_freight_cost_condition').prop('required',false);
    } else {
        jQuery('.field_woocommerce_woobigpost_free_shipping_config').siblings().show();
        if(jQuery('#woocommerce_woobigpost_free_shipping_max_freight_cost').val() != "" && jQuery('#woocommerce_woobigpost_free_shipping_max_freight_cost').val() > 0 ){
            jQuery('.field_woocommerce_woobigpost_free_shipping_freight_cost_condition').show();
            jQuery('.woocommerce_woobigpost_free_shipping_freight_cost_condition ').prop('required',true);
        } else {
            jQuery('.woocommerce_woobigpost_free_shipping_freight_cost_condition ').prop('required',false);
            jQuery('.field_woocommerce_woobigpost_free_shipping_freight_cost_condition').hide();
        }

    }

    var product_page = jQuery('.woocommerce_woobigpost_product_page:checked').val();

    jQuery( '#woocommerce_woobigpost_popup_button_text' ).parents( 'tr' ).hide();
    jQuery( '#woocommerce_woobigpost_product_position' ).parents( 'tr' ).hide();

    if(product_page=="popup"){
        jQuery( '#woocommerce_woobigpost_popup_button_text' ).parents( 'tr' ).show();
        jQuery( '#woocommerce_woobigpost_product_position' ).parents( 'tr' ).show();
    }

    if(product_page=="on_page"){
        jQuery( '#woocommerce_woobigpost_product_position' ).parents( 'tr' ).show();
    }
    
    jQuery('.order_sync_only_fields').parents('tr').hide();
    
    var order_sync_only = jQuery('#woocommerce_woobigpost_order_sync_only').val();
    if(order_sync_only == "Yes"){
        jQuery('.order_sync_only_fields').parents('tr').show();
    }else{
        jQuery('.order_sync_only_fields').parents('tr').hide();
    }
    
    jQuery('#woocommerce_woobigpost_order_sync_only').on('change',function(){
        if(jQuery(this).val() == "Yes"){
            jQuery('.order_sync_only_fields').parents('tr').show();
        }else{
            jQuery('.order_sync_only_fields').parents('tr').hide();
        }
    });

    jQuery('.restrict_carriers_fields').parents('tr').hide();
    jQuery('.restrict_items_fields').parents('tr').hide();

    var restrict_carriers = jQuery('#woocommerce_woobigpost_restrict_carriers').val();
    if(restrict_carriers == "Yes"){
        jQuery('.restrict_carriers_fields').parents('tr').show();
        jQuery('.seprater.restrict_carriers_fields').show();

        var id = jQuery('#woocommerce_woobigpost_carrier_list').val();
        jQuery('.restrict_items_fields').parents('tr').hide();
        jQuery('#woocommerce_woobigpost_restrict_carrier_'+id).parents('tr').show();
        jQuery('#woocommerce_woobigpost_restrict_carrier_'+id).parents('tr').next().show();

    }else{
        jQuery('.restrict_carriers_fields').parents('tr').hide();
        jQuery('.restrict_items_fields').parents('tr').hide();
        jQuery('.seprater.restrict_carriers_fields').hide();
    }

    jQuery('#woocommerce_woobigpost_restrict_carriers').on('change',function(){
        if(jQuery(this).val() == "Yes"){
            jQuery('.restrict_carriers_fields').parents('tr').show();
            jQuery('.seprater.restrict_carriers_fields').show();

            var id = jQuery('#woocommerce_woobigpost_carrier_list').val();
            jQuery('.restrict_items_fields').parents('tr').hide();
            jQuery('#woocommerce_woobigpost_restrict_carrier_'+id).parents('tr').show();
            jQuery('#woocommerce_woobigpost_restrict_carrier_'+id).parents('tr').next().show();
        }else{
            jQuery('.restrict_carriers_fields').parents('tr').hide();
            jQuery('.restrict_items_fields').parents('tr').hide();
            jQuery('.seprater.restrict_carriers_fields').hide();
        }
    });

    jQuery('#woocommerce_woobigpost_carrier_list').on('change',function(){
        var id = jQuery(this).val();
        jQuery('.restrict_items_fields').parents('tr').hide();
        jQuery('#woocommerce_woobigpost_restrict_carrier_'+id).parents('tr').show();
        jQuery('#woocommerce_woobigpost_restrict_carrier_'+id).parents('tr').next().show();
    });

    jQuery('.deselectall-itemtypes').on('click', function(){
        var i = jQuery(this).data('id');
        jQuery('.woocommerce_woobigpost_allowed_items_'+i).prop('checked', false);
    });

    jQuery('.selectall-itemtypes').on('click', function(){
        var i = jQuery(this).data('id');
        jQuery('.woocommerce_woobigpost_allowed_items_'+i).prop('checked', true);
    });
}