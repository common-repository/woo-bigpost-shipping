var components = {};

jQuery.ajaxQ = (function(){
  var id = 0, Q = {};

  jQuery(document).ajaxSend(function(e, jqx){
    jqx._id = ++id;
    Q[jqx._id] = jqx;
  });
  jQuery(document).ajaxComplete(function(e, jqx){
    delete Q[jqx._id];
  });

  return {
    abortAll: function(){
      var r = [];
      jQuery.each(Q, function(i, jqx){
        r.push(jqx._id);
        jqx.abort();
      });
      return r;
    }
  };

})();

jQuery( document ).ready( function($) {
    var cart_update = true;
    window.cart_updated = false;
    window.update_checkout = true;
	// Check if form is available then hide show elements
	if( $('.woobigpost-shipping-quote-form').length > 0 ) {
        woobigpost_hide_show_sqf_fields();

        if(WooBP.page_is == 'product'){
            if($('input.variation_id').length > 0){
                if($('input.variation_id').val() == '' || $('input.variation_id').val() < 1){
                    $('.no_variation td, .no_variation span').html('Please select from the available product options to generate a delivery quote.');
                    $('#qq-popup-button').attr('rel','').addClass('qq-disabled');
                    $('#sqf-shipping-cost').hide();
                    $('.sqf-suburb-box').hide();
                }
            } else  if ($('.component_options_select').length > 0) {
              $('.component_options_select').each(function() {
                var selected = '';
                var attr_id = $(this).attr('id');

                $.each($("#"+attr_id).data('options_data'), function(index, value) {
                  selected = value.is_selected ? value.option_id : '';
                })

                var id = attr_id.replace('component_options_', '');

                if(!components.hasOwnProperty('key_' + id) && selected != '') {
                  // add to array
                  components['key_' + id] = selected;
                }
              });

              woobigpost_find_shipping_costs(false);
            } else {
              woobigpost_find_shipping_costs(false);
              $('#qq-popup-button').attr('rel','modal:open').removeClass('qq-disabled');
            }

        }  else {
            woobigpost_find_shipping_costs(true);
        }

		var $elemets = 'input[name="BuyerIsBusiness"], input[name="have_confirm"], input[name="Forkliftsoption"], input[name="leave_auth"]';
		$( document ).on( 'change', $elemets, function() {

			// Hide or show fields
			woobigpost_hide_show_sqf_fields();

			// Find Shipping cost
            if( WooBP.page_is == 'product' ) {
               if($('input.variation_id').length > 0){
                   if($('input.variation_id').val() == '' || $('input.variation_id').val() < 1){
                       $('.no_variation td, .no_variation span').html('Please select from the available product options to generate a delivery quote.');
                       $('#qq-popup-button').attr('rel','').addClass('qq-disabled');;
                       $('#sqf-shipping-cost').hide();
                       $('.sqf-suburb-box').hide();
                   } else {
                       $('.no_variation td, .no_variation span').html('');
                       $('#qq-popup-button').attr('rel','modal:open').removeClass('qq-disabled');
                       $( '#sqf-shipping-cost').show();
                       $('.sqf-suburb-box').show();
                       woobigpost_find_shipping_costs(cart_update);
                   }

                   /*var variation_history = sessionStorage.getItem($('input.variation_id').val());
                   var data = JSON.parse(variation_history);

                   if(data.hasOwnProperty($(this).attr('name'))){

                   }*/

                   sessionStorage.removeItem($(this).attr('name'));
                   sessionStorage.setItem($(this).attr('name'), $(this).val());

               }  else {
                   woobigpost_find_shipping_costs(cart_update);
               }

            } else {
                woobigpost_find_shipping_costs(cart_update);
            }
		});

	    // for product variation
	    $('input.variation_id').change( function(){
	      if( '' != $('input.variation_id').val() ) {
	        var html='';
	        if($('.woobigpost_var_html_data').length > 0){
		        $.each($('.woobigpost_var_html_data').data('desc').data, function(index, element) {
		          if($('input.variation_id').val() == index){
		            html = element;
		            return;
		          }
		        });
		    }

	        //check the selected options before populating the html
	        var updated_html = $(html);
	        $('.woobigpost-sqf-fields tr:visible input:checked').each(function(index){
	          updated_html.find('input[name="'+$(this).attr('name')+'"][value='+$(this).val()+']').prop( "checked", true );
	        });

	        //check the history using sessionStorage
	        updated_html.find('.options_inner').each(function(index){
	          var name =  $(this).find('input:first-child').attr('name');
	          var value = sessionStorage.getItem(name);
	          updated_html.find('input[name="'+name+'"][value='+value+']').prop( "checked", true );
	        });

	        $('.sqf-single-product-page').find('.woo_var_replaceable').remove();
	        $('.no_variation span').html('');
	        $('#qq-popup-button').attr('rel','modal:open').removeClass('qq-disabled');;
	        $('.sqf-single-product-page').find('.woobigpost-sqf-fields tbody').append(updated_html);
	        woobigpost_hide_show_sqf_fields();
	        $('#sqf-shipping-cost').hide();
	        $('#sqf-suburb-list').show();
	        woobigpost_find_shipping_costs(false);
	      }else{
	        $('#qq-popup-button').attr('rel','').addClass('qq-disabled');
	        $('.no_variation td, .no_variation span').html('Please select from the available product options to generate a delivery quote.');
	      }
	    });

	    // for composite product
	    $('.component_options_select').change(function () {

	      var value = $(this).val();
	      var id = $(this).attr('id').replace('component_options_', '');

	      if(!components.hasOwnProperty('key_' + id) && value != "") {
	        // add to array
	        components['key_' + id] = value;
	      } else {
	        // remove
	        delete components['key_' + id];
	      }

	      woobigpost_find_shipping_costs(false);

	    });

	}else{
		if( WooBP.page_is != 'product' ) {
	        if($('.cart-bigpost-shipping').length == 0){
	        	$('.cart-bigpost-shipping').ready(function(){
					var $elemets = 'input[name="BuyerIsBusiness"], input[name="have_confirm"], input[name="Forkliftsoption"], input[name="leave_auth"]';
					$( document ).on( 'change', $elemets, function() {

						// Hide or show fields
						woobigpost_hide_show_sqf_fields();

						woobigpost_find_shipping_costs(true);
					});
				});
			}
		}else{
			woobigpost_find_shipping_costs(true);
		}
	}


	// Set suburb / to-suburb-item
	$( document ).on( 'click', '#sqf-suburb-list .woo-bp-suburb-item', function() {

		var suburb      = $( this ).attr( 'data-suburb' );
		var state       = $( this ).attr( 'data-state' );
		var postcode    = $( this ).attr( 'data-postcode' );
		var postcodeid  = $( this ).val();

		if( WooBP.page_is == 'checkout' ) {
			
			$( document.body ).trigger('update_checkout', { update_shipping_method: true } );

			if( $('#ship-to-different-address-checkbox').is(':checked') ) {

				$('#shipping_state option').removeAttr('disabled');
				$('#shipping_state').attr('disabled','disabled');
				
				$( '#shipping_city' ).val( suburb );
				$( '#shipping_state' ).val( state ).trigger( "change" );
				if($('#shipping_state').hasClass("select2-hidden-accessible")){
					$( '#shipping_state' ).select2();	
				}
				$( '#shipping_postcode' ).val( postcode );
			} else {

				$('#billing_state option').removeAttr('disabled');
				$('#billing_state').attr('disabled','disabled');

				$( '#billing_city' ).val( suburb ).trigger( "change" );
				$( '#billing_state' ).val( state ).trigger( "change" );
				if($('#billing_state').hasClass("select2-hidden-accessible")){
					$( '#billing_state' ).select2();	
				}
				$( '#billing_postcode' ).val( postcode );
			}

			if(jQuery('.wc-block-components-address-form__postcode').length > 0){
				
				$('#shipping-state input').attr('disabled','disabled');
				
				$( '#shipping-city' ).val( suburb );
				$( '#shipping-state input' ).val( state ).trigger( "change" );
				
				$( '#shipping-postcode' ).val( postcode );
			}
		}

		if (WooBP.page_is === 'cart') {
			$('#calc_shipping_state').val( state ).trigger( "change" );
			if($('#calc_shipping_state').hasClass("select2-hidden-accessible")){
				$('#calc_shipping_state').select2();
			}
			$('#calc_shipping_city').val( suburb );
		}

        if (WooBP.page_is === 'product') {
            $('#sqf-suburb-list').hide();
        }

		var data = {
			'action': 'woobigpost_set_shipping_suburb',
			'tosuburb':  suburb,
			'tostate':  state,
			'topostcode':  postcode,
			'topostcodeid':  postcodeid
		};

		$( '#woobigpost-suburb-loader' ).show();
		$( '#woobigpost-ajax-loader' ).fadeIn( 'slow' );
		$('.cart-bigpost-shipping').show();
		$('button[name="calc_shipping"]').prop('disabled', false);

		var interval = setInterval(function(){
			//delay this until ?wc-ajax=update_order_review is finished
			
			if(jQuery.active == 0){
				clearInterval(interval);
				$.post( WooBP.ajaxurl, data, function(response) {
					if( response ) {
						$( '#toDepotId' ).val( response );
					}

					jQuery( document.body ).trigger( 'update_checkout', {
				        update_shipping_method: true
				    });

					// Set the values
					$('#sqf-to-suburb1').val( suburb + ' ' + state + ' - ' + postcode );
					$('#toSuburb').val( suburb );
					$('#toState').val( state );
					$('#toPostcode').val( postcode );
					$('#toPostcodeId').val( postcodeid );

					
					// $('#sqf-suburb-list').hide();

					$( '#woobigpost-ajax-loader' ).fadeOut( 'slow' );
					$( '#woobigpost-suburb-loader' ).fadeOut();

					// find the shipping
					woobigpost_find_shipping_costs(cart_update);
				});
			}else{
				if($('#toSuburb').length > 0){
					jQuery.ajaxQ.abortAll();	
				}
			}
		}, 1000);

	} );

	// Set shipping / carrier id
	$( document ).on( 'change', '.bigpost-carrier-id', function() {

		var data = {
			'action': 'woobigpost_update_cart_shipping',
			'carrierid': $( this ).attr( 'data-carrierid' ),
			'carriername': $( this ).attr( 'data-carriername' ),
			'charge': $( this ).attr( 'data-charge' ),
			'tax': $( this ).attr( 'data-tax' ),
			'Total': $( this ).attr( 'data-total' ),
			'ShippingType': $( this ).attr( 'data-shippingtype' ),
			'authority': $( this ).attr( 'data-authority' )
		};

		$( '#woobigpost-ajax-loader' ).fadeIn( 'slow' );
		$.post( WooBP.ajaxurl, data, function(response) {

			var response = JSON.parse( response );
			if( response.status == 'success' ) {
				$(".cart_totals .shop_table tbody tr.shipping").remove();
				$(".cart_totals .shop_table tbody tr.order-total").remove();
				$(".cart_totals .shop_table tbody tr").last().after( response.options );
			}
			$( '#woobigpost-ajax-loader' ).fadeOut( 'slow' );
			$( document.body ).trigger('update_checkout', { update_shipping_method: true } );
		} );
	} );

	/* get shipping status by consignment id  */
	$(document).on('click', '#get_shipping_status_button', function(){
		var consignment_number = $('#consignment_number').val();
		consignment_number = $.trim(consignment_number);
		if( consignment_number != '' ) {
			$('#get_shipping_status_button').prop('disabled', true);
			$('#loader-shipping-status').show();
			$.post(             
				WooBP.ajaxurl, 
				{
					'action'            : 'woobigpost_get_shipping_status',
					'consignment_number': consignment_number
				}, 
				function(response){
					$('#get_shipping_status_button').prop('disabled', false);  
					$('#shipping_status_div').html('');
					$('#shipping_status_div').html(response);
					$('#loader-shipping-status').hide();        
				}
			);
		}
	});


    if(WooBP.page_is == 'product'){
        jQuery('.qty').on('change', function(){
            woobigpost_find_shipping_costs(false);
        });


        // Suburb list frontend
		$( document ).on( 'keyup', '#sqf-to-suburb1', function() {

			var suburb = jQuery(this).val();
			if( suburb.length > 3 ) {

				var data = {
					'action': 'woobigpost_get_suburb_list',
					'type': 'to',
					'value': suburb
				};

				$( "#woobigpost-ajax-loader" ).fadeIn( "slow" );

				$.post( WooBP.ajaxurl, data, function(response) {
					$( "#woobigpost-ajax-loader" ).fadeOut( "slow" );
					$( "#sqf-suburb-list" ).html( response ).fadeIn( "slow" );
				} );
			}
		} );
    }



	if( WooBP.page_is == 'checkout' ) {
		woobigpost_manage_address();
		$( document ).on( 'click', '#ship-to-different-address-checkbox, .wc-block-components-address-card__edit', function() {
			woobigpost_manage_address();
		} );

        $( document.body ).on('click', '#place_order', function() {
            $('#billing_state').removeAttr('disabled');
            $('#billing_state option:not(:selected)').prop('disabled', true);
            $('#shipping_state').removeAttr('disabled');
        } );

        $(document.body).on('updated_checkout', function() {
			woobigpost_hide_show_sqf_fields();

			if(typeof jQuery('#exclude_tax').val() != "undefined" && jQuery('#exclude_tax').val() != "yes"){
				var shipping_amount = jQuery('input.shipping_method:checked').next().find('.amount').text();
				shipping_amount = shipping_amount.replace("$","");
				shipping_amount = parseFloat(shipping_amount);
				shipping_amount = shipping_amount - (shipping_amount / 1.1);

				var order_tax = jQuery('.includes_tax').find('.amount').text();
				order_tax = order_tax.replace("$","");
				order_tax = parseFloat(order_tax);

				var final_tax = shipping_amount + order_tax;

				if(final_tax){
					jQuery('.includes_tax').text("(Includes $"+final_tax.toFixed(2)+" Tax)");
				}
			}
        })

        $( document ).on( 'keyup', '#billing_postcode, #shipping_postcode, #shipping-postcode', debounce(function() {

        	if ($('#ship-to-different-address-checkbox').prop('checked') && $(this).prop('id') === 'billing_postcode' ) {
        		return false;
        	}


        	if (true) {}

			var suburb = jQuery(this).val();
			var existingSubUrb = $('#calc_shipping_city').val();

			if( suburb.length > 3 ) {
				var data = {
					'action': 'woobigpost_get_suburb_list',
					'type': 'to',
					'value': suburb
				};

				$('#sqf-suburb-list').html('Loading suburbs...');

				$.post( WooBP.ajaxurl, data, function(response) {
					$( "#sqf-suburb-list" ).html( response ).fadeIn( "slow" );
 					
					$('#sqf-suburb-list .woo-bp-suburb-item[data-suburb="' + existingSubUrb + '"]').prop('checked', true);
					
					if($('#ship-to-different-address-checkbox').prop('checked')){
						if(jQuery('#shipping_city').val() != ''){
							var c = jQuery('#shipping_city').val();
							jQuery('input[data-suburb="'+c+'"]').trigger('click');
						}						
					}else{
						if(jQuery('#billing_city').val() != ''){
							var c = jQuery('#billing_city').val();
							jQuery('input[data-suburb="'+c+'"]').trigger('click');
						}
					}
				} );
			}

		}, 1000));

        jQuery('#hidden-billing_state').val(jQuery('select[name=billing_state]').val());

	    if(jQuery('#ship-to-different-address-checkbox').prop('checked')){
	      jQuery('#hidden-shipping_state').removeAttr('disabled');
	      jQuery('#hidden-shipping_state').val(jQuery('select[name=shipping_state]').val());
	    }else{
	      jQuery('#hidden-shipping_state').attr('disabled', 'disabled');
	    }


	    jQuery('#billing_state').change(function(){
	      jQuery('#hidden-billing_state').val(jQuery('select[name=billing_state]').val());
	    });

	    
	    jQuery('#shipping_state').change(function(){
	      jQuery('#hidden-shipping_state').val(jQuery('select[name=shipping_state]').val());
	    });

	    jQuery('#ship-to-different-address-checkbox').change(function(){
	      if(jQuery(this).prop('checked')){
	        jQuery('#hidden-shipping_state').removeAttr('disabled');
	      }else{
	        jQuery('#hidden-shipping_state').attr('disabled', 'disabled');
	      }
	    });
		
		if(jQuery('#billing_postcode').val() != ''){
			jQuery('#billing_postcode').keyup();
		}
		
		if(jQuery('#shipping_postcode').val() != ''){
			jQuery('#shipping_postcode').keyup();
		}
	}


	if (WooBP.page_is === 'cart') {

		$(document.body).on('update_wc_div', function() {
        	if ($('#toSuburb').val() === '') {
				$('.cart-bigpost-shipping').hide();
			}else {
				$('.cart-bigpost-shipping').show();
			}
        })

        if ($('#toSuburb').val() === '') {
				$('.cart-bigpost-shipping').hide();
			}else {
				$('.cart-bigpost-shipping').show();
			}

		$(document).on('click', '.shipping-calculator-button', function(e) {
			$('button[name="calc_shipping"]').prop('disabled', true);
			$('.additional-elements').remove();
			$('#calc_shipping_postcode_field').append('\
				<div class="additional-elements">\
					<p class="small-text">Fill up the Postcode/ZIP to show list of suburbs</p>\
					<div id="sqf-suburb-list"></div>\
				</div>\
			')

			if ($('#calc_shipping_postcode').val().length > 3) {
				$('#calc_shipping_postcode').trigger('keyup');
			}else {
				$('#sqf-suburb-list').html('No suburb found...');
			}
		})

		$( document ).on( 'keyup', '#calc_shipping_postcode', debounce(function() {

			var suburb = jQuery(this).val();
			var existingSubUrb = $('#calc_shipping_city').val();
			var data = {
				'action': 'woobigpost_get_suburb_list',
				'type': 'to',
				'value': suburb
			};

			$('#sqf-suburb-list').html('Loading suburbs...');
			$('button[name="calc_shipping"]').prop('disabled', true);

			$.post( WooBP.ajaxurl, data, function(response) {
				$( "#sqf-suburb-list" ).html( response ).fadeIn( "slow" );

				$('#sqf-suburb-list .woo-bp-suburb-item[data-suburb="' + existingSubUrb + '"]').prop('checked', true);
			} );

		}, 1000));


        //when update cart button is clicked
        $(document).on("click", "[name='update_cart']", function(){
            window.cart_updated = false;
        });

        // Manage widget hide / show elements after cart updates
        $( document.body ).on( 'updated_cart_totals', function() {
            woobigpost_hide_show_sqf_fields();
            /*if(window.cart_updated === false){
                cart_update = false;
                woobigpost_find_shipping_costs(cart_update).done(function() {
                    cart_update = true;
                });
            }*/
        });
		
	}

	$('.qq-disabled').on('click',function(){
		$('.no_variation span').css('text-decoration','underline');
		setTimeout(function(){ $('.no_variation span').css('text-decoration','none'); }, 400);
	})

    window.ajax_loading = false;
    $.checkAjaxRunning = function(){
        console.log($.active);
    }

    $( 'form.checkout' ).on( 'checkout_place_order', function() {
        var data = {
            'action': 'woobigpost_check_session',
            'postcodeid': $('#toPostcodeId').val()
        };
        
        var ret = true;
        
        $.ajax({
            type: 'POST',
            url:WooBP.ajaxurl,
            data: data,
            async: false,  
            success:function(response) {
                if(response == 0){
                    ret = false;
                    alert("Mismatched shipping suburb. Please try again.");
                    location.reload();
                }
            }
        });
        
        return ret;
    });
});

/**
 * Hide show form fields
 */
function woobigpost_hide_show_sqf_fields() {

	jQuery( '.sqf-opts' ).hide();

	// Check top home or business opts
	var BuyerIsBusiness = jQuery( 'input[name="BuyerIsBusiness"]:checked' ).val();
	if( BuyerIsBusiness == '0' ) {
		jQuery( '.sqf-confirm-opt' ).show();
		jQuery( '.sqf-authority-opt' ).show();
	} else if( BuyerIsBusiness == '1' ) {
		jQuery( '.sqf-forklift-opt' ).show();
		jQuery('.sqf-confirm-no td').html('');
	}

	// Confirm opts check
	var haveConfirm = jQuery( 'input[name="have_confirm"]:checked' ).val();
	if( haveConfirm == 0 ) {

		//jQuery('.sqf-suburb-box').hide();

		jQuery('.sqf-confirm-no td').html('Please choose an alternative delivery option');
	} else {
		jQuery('.sqf-confirm-no td').html('');
		//jQuery('.sqf-suburb-box').show();
	}
	
	if( BuyerIsBusiness == '1' ) {
		jQuery('.sqf-confirm-no td').html('');
	}

	//jQuery( '#sqf-shipping-cost' ).html('');
}

/**
 * Find shipping cost
 */
function woobigpost_find_shipping_costs(cart_update) {
	var suburb      = jQuery('#toSuburb').val();
	var state       = jQuery('#toState').val();
	var postcode    = jQuery('#toPostcode').val();
	var postcodeid  = jQuery('#toPostcodeId').val();

    if(WooBP.page_is == "product"){
        if(jQuery('input.variation_id').length > 0 && jQuery('input.variation_id').val() != ''){
            var productId   = jQuery('input.variation_id').val();
        } else {
            var productId   = jQuery('#productID').val();
        }
    }

	var depotid     = jQuery('#toDepotId').val();
	var have_confirm = jQuery('input[name=have_confirm]:checked').val();
	
	if(typeof have_confirm == 'undefined'){
		have_confirm = jQuery('input[name=have_confirm]').val();   
	}
	
	var BuyerIsBusiness = jQuery('input[name=BuyerIsBusiness]:checked').val();
	var has_forklift    = jQuery('input[name=Forkliftsoption]:checked').val();
	var leave_auth      = jQuery('input[name=leave_auth]:checked').val();
	
    if( have_confirm != '1' && have_confirm != '3' && BuyerIsBusiness != 1) {
    	var have_confirm    = jQuery('input[name=have_confirm]:checked').val();
    } else if(BuyerIsBusiness == 1) {
        var have_confirm    = 1;
    }
    
	// Check if suburb is not entered then return
	if( suburb == '' ) return false;
	if (WooBP.page_is === 'product') {
		if( jQuery('#sqf-to-suburb1').length > 0 && jQuery('#sqf-to-suburb1').val() == '' ) {
			return false;
		}
	}

	var doAjax = 0;
	if( BuyerIsBusiness == '0' ) {
		console.log("if...", BuyerIsBusiness);

		doAjax = 1;
		if( jQuery('input[name=have_confirm]').length > 0 && typeof have_confirm == 'undefined') {
			doAjax = 0;
		}
		if( jQuery('input[name=leave_auth]').length > 0 && typeof leave_auth == 'undefined' ) {
			doAjax = 0;
		}
	} else if( BuyerIsBusiness == '1' && (jQuery('input[name=Forkliftsoption]').length <= 0 || typeof has_forklift != 'undefined') ) {
		doAjax = 1;
	}else{	
		console.log("else...", BuyerIsBusiness);

		doAjax = 1;
		BuyerIsBusiness = 0;
		has_forklift    = 0;
		leave_auth      = 1;
		have_confirm 	= 1;
	}

    var product_quantity = 1; //default

    if( WooBP.page_is == "product"){
        product_quantity = jQuery('.qty').val();
    }

    var data = {
        'action': 'woobigpost_find_shipping_costs',
        'todepotid': depotid,
        'tosuburb': suburb,
        'tostate': state,
        'topostcode': postcode,
        'topostcodeid': postcodeid,
        'buyer_is_business': BuyerIsBusiness,
        'has_forklift': has_forklift,
        'leave_auth': leave_auth,
        'have_confirm': have_confirm,
        'productId': productId,
        'page_is': WooBP.page_is,
        'product_quantity': product_quantity,
    };

    if( WooBP.page_is == "cart" || WooBP.page_is == "checkout"){
        if( doAjax == 1 && productId != 0) {

        	var ajax_timer;
            var timer = 0;
            jQuery( '#woobigpost-ajax-loader').html("<span class='loading_text'>Calculating Live Rates...</span>").fadeIn( 'slow' );
            data['action'] = 'woobigpost_set_fields_on_session';
            jQuery( '#woobigpost-ajax-loader' ).fadeIn( 'slow' );

            console.log("doAjax 2",WooBP.ajaxurl);
            return jQuery.post( WooBP.ajaxurl, data, function(response) {
            	console.log("response", response);
                jQuery( '#sqf-shipping-cost' ).html('');
                jQuery( '#woobigpost-ajax-loader' ).fadeOut( 'slow' );
                jQuery('.cart-bigpost-shipping').show();

                if( WooBP.page_is == "checkout" && window.update_checkout === true){
                    jQuery( document.body ).trigger( 'update_checkout', {
                        update_shipping_method: true
                    });
                }

                if( WooBP.page_is == "cart" && cart_update == true) {
                    jQuery( document.body ).trigger( 'updated_shipping_method' );
                    jQuery( document.body ).trigger( 'wc_update_cart');
                }

            }).done(function(){
                window.cart_updated = true;
                window.update_checkout = true;
            });

            ajax_timer = setInterval(function(){
                if(timer > 5 && bp_ajax.readyState == 1){
                    jQuery( '#woobigpost-ajax-loader').html("<span class='loading_text'>Still calculating live rates - this is taking a while!</span>");
                }
                timer++;

            }, 1000, true);
        }
    } else {
        // Check if doAjax
        if( doAjax == 1 && productId != 0) {
            var ajax_timer;
            var timer = 0;
            jQuery( '#woobigpost-ajax-loader').html("<span class='loading_text'>Calculating Live Rates...</span>").fadeIn( 'slow' );
            data['components'] = {components: components};
            var bp_ajax = jQuery.post( WooBP.ajaxurl, data, function(response) {
                jQuery( '#sqf-shipping-cost' ).html('');
                jQuery( '#sqf-shipping-cost').show();
                jQuery( '#sqf-shipping-cost' ).html( response );
                jQuery( '#woobigpost-ajax-loader' ).fadeOut( 'slow' );

                if( WooBP.page_is == "checkout" && window.update_checkout === true){
                    jQuery( document.body ).trigger( 'update_checkout', {
                        update_shipping_method: true
                    });
                }

                if( WooBP.page_is == "cart" && cart_update == true) {
                    jQuery( document.body ).trigger( 'updated_shipping_method' );
                    jQuery( document.body ).trigger( 'wc_update_cart');
                }

            }).done(function(){
                window.cart_updated = true;
                window.update_checkout = true;
                clearInterval(ajax_timer);
            });

            ajax_timer = setInterval(function(){
                if(timer > 5 && bp_ajax.readyState == 1){
                    jQuery( '#woobigpost-ajax-loader').html("<span class='loading_text'>Still calculating live rates - this is taking a while!</span>");
                }
                timer++;

            }, 1000, true);

            return bp_ajax;
        }
    }


}

/**
 * Manage Addresses
 */
function woobigpost_manage_address() {
	
	if(jQuery('.wc-block-components-address-form__postcode').length > 0){
		jQuery( '#shipping-city' ).val( jQuery('input#toSuburb').val() );
		jQuery( '#shipping-state input' ).val( jQuery('input#toState').val() ).trigger( "change" );
		jQuery( '#shipping-postcode' ).val( jQuery('input#toPostcode').val() );

		jQuery('.wc-block-components-address-form__postcode').append('\
			<div class="additional-elements">\
				<p class="small-text">Fill up the Postcode/ZIP to show list of suburbs</p>\
				<div id="sqf-suburb-list"></div>\
			</div>\
		');

		jQuery( '#shipping-city' ).prop("readonly", true);
    	jQuery( '#shipping-state input' ).prop("disabled", true);

		jQuery( '#shipping-city' ).on("click", function(){
			jQuery( '#shipping-city' ).val( jQuery('input#toSuburb').val() );
		});

		jQuery( '#shipping-city' ).on("click", function(){
			jQuery( '#shipping-city' ).val( jQuery('input#toSuburb').val() );
		});

    	jQuery( '#shipping-state input' ).on("click", function(){
			jQuery( '#shipping-city input' ).val( jQuery('input#toState').val() );
		});

    	return;
	}

	if( jQuery('#ship-to-different-address-checkbox').is(':checked') ) {

        jQuery( '#shipping_city' ).val( jQuery('input#toSuburb').val() );
		jQuery( '#shipping_state' ).val( jQuery('input#toState').val() ).trigger( "change" );
		jQuery( '#shipping_postcode' ).val( jQuery('input#toPostcode').val() );

		jQuery('.additional-elements').remove();
		jQuery('#shipping_postcode_field').append('\
			<div class="additional-elements">\
				<p class="small-text">Fill up the Postcode/ZIP to show list of suburbs</p>\
				<div id="sqf-suburb-list"></div>\
			</div>\
		');

		jQuery( '#shipping_city' ).prop("readonly", true);
    	jQuery( '#shipping_state' ).prop("disabled", true);

    	jQuery( '#billing_city' ).prop("readonly", false);
    	jQuery( '#billing_state' ).prop("disabled", false);

	} else {

    	jQuery( '#billing_city' ).val( jQuery('#shipping_city').val() ).trigger( "change" );
		jQuery( '#billing_state' ).val( jQuery('#shipping_state').val() ).trigger( "change" );
		jQuery( '#billing_postcode' ).val( jQuery('#shipping_postcode').val() );

		jQuery('.additional-elements').remove();
		jQuery('#billing_postcode_field').append('\
			<div class="additional-elements">\
				<p class="small-text">Fill up the Postcode/ZIP to show list of suburbs</p>\
				<div id="sqf-suburb-list"></div>\
			</div>\
		');

	  	jQuery( '#billing_city' ).prop("readonly", true);
    	jQuery( '#billing_state' ).prop("disabled", true);
	}
	
}

var debounce = function debounce(func, delay){
    var inDebounce;
    return function(){
        var context = this;
        var args = arguments;
        clearTimeout(inDebounce);
        inDebounce = setTimeout(function(){
            return func.apply(context, args)
        }, delay);
    }
}