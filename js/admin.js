jQuery(document).ready(function($) {
	 jQuery('#woocommerce-order-items1').on( 'click', 'input.check-column', function() {
		if ( jQuery(this).is(':checked') )
			jQuery('#woocommerce-order-items1').find('.check-column input').attr('checked', 'checked');
		else
			jQuery('#woocommerce-order-items1').find('.check-column input').removeAttr('checked');
	} );
     jQuery('#woocommerce-order-items1').on( 'click', '.do_bulk_action', function() {

		var action = jQuery(this).closest('.bulk_actions').find('select').val();
		var selected_rows1 = jQuery('#woocommerce-order-items1').find('.check-column input:checked');
		var selected_rows = jQuery('#woocommerce-order-items').find('.check-column input:checked');
		var item_ids = [];

		jQuery(selected_rows1).each( function() {

			var $item = jQuery(this).closest('tr.item, tr.fee');

			item_ids.push( $item.attr( 'data-order_item_id' ) );

		} );
		jQuery(selected_rows).each( function() {

			var $item = jQuery(this).closest('tr.item, tr.fee');

			item_ids.push( $item.attr( 'data-order_item_id' ) );

		} );

		if ( item_ids.length == 0 ) {
			alert( woocommerce_writepanel_params.i18n_select_items );
			return false;
		}

		if ( action == 'delete' ) {

			var answer = confirm( woocommerce_writepanel_params.remove_item_notice );

			if ( answer ) {

				jQuery('table.woocommerce_order_items1').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_writepanel_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

				var data = {
					order_item_ids: 	item_ids,
					action: 			'woocommerce_remove_order_item',
					security: 			woocommerce_writepanel_params.order_item_nonce
				};

				$.ajax( {
					url: woocommerce_writepanel_params.ajax_url,
					data: data,
					type: 'POST',
					success: function( response ) {
						jQuery(selected_rows1).each( function() {
							jQuery(this).closest('tr.item, tr.fee').remove();
						} );
						jQuery(selected_rows).each( function() {
							jQuery(this).closest('tr.item, tr.fee').remove();
						} );
						jQuery('table.woocommerce_order_items1').unblock();
					}
				} );

			}

		} else if ( action == 'reduce_stock' ) {

			jQuery('table.woocommerce_order_items1').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_writepanel_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

			var quantities = {};

			jQuery(selected_rows).each( function() {

				var $item = jQuery(this).closest('tr.item, tr.fee');
				var $qty  = $item.find('input.quantity');

				quantities[ $item.attr( 'data-order_item_id' ) ] = $qty.val();
			} );

			var data = {
				order_id:			woocommerce_writepanel_params.post_id,
				order_item_ids: 	item_ids,
				order_item_qty: 	quantities,
				action: 			'woocommerce_reduce_order_item_stock',
				security: 			woocommerce_writepanel_params.order_item_nonce
			};

			$.ajax( {
				url: woocommerce_writepanel_params.ajax_url,
				data: data,
				type: 'POST',
				success: function( response ) {
					alert( response );
					jQuery('table.woocommerce_order_items1').unblock();
				}
			} );

		} else if ( action == 'increase_stock' ) {

			jQuery('table.woocommerce_order_items1').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_writepanel_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

			var quantities = {};

			jQuery(selected_rows).each( function() {

				var $item = $(this).closest('tr.item, tr.fee');
				var $qty  = $item.find('input.quantity');

				quantities[ $item.attr( 'data-order_item_id' ) ] = $qty.val();
			} );

			var data = {
				order_id:			woocommerce_writepanel_params.post_id,
				order_item_ids: 	item_ids,
				order_item_qty: 	quantities,
				action: 			'woocommerce_increase_order_item_stock',
				security: 			woocommerce_writepanel_params.order_item_nonce
			};

			jQuery.ajax( {
				url: woocommerce_writepanel_params.ajax_url,
				data: data,
				type: 'POST',
				success: function( response ) {
					alert( response );
					$('table.woocommerce_order_items').unblock();
				}
			} );
		}

		return false;
	} );
	 
	 // Add a line item
	jQuery('#woocommerce-order-items1 button.add_order_item').click(function(){

		var add_item_ids = jQuery('select#add_item_id').val();

		if ( add_item_ids ) {

			count = add_item_ids.length;

			jQuery('table.woocommerce_order_items').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_writepanel_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

			$.each( add_item_ids, function( index, value ) {

				var data = {
					action: 		'woocommerce_add_order_item',
					item_to_add: 	value,
					order_id:		woocommerce_writepanel_params.post_id,
					security: 		woocommerce_writepanel_params.order_item_nonce
				};

				$.post( woocommerce_writepanel_params.ajax_url, data, function( response ) {

					$('table.woocommerce_order_items tbody#order_items_list').append( response );

					if (!--count) {
						jQuery('select#add_item_id, #add_item_id_chzn .chzn-choices').css('border-color', '').val('');
					    jQuery(".tips").tipTip({
					    	'attribute' : 'data-tip',
					    	'fadeIn' : 50,
					    	'fadeOut' : 50,
					    	'delay' : 200
					    });
					    jQuery('select#add_item_id').trigger("liszt:updated");
					    jQuery('table.woocommerce_order_items').unblock();
					}

					jQuery('#order_items_list tr.new_row').trigger('init_row').removeClass('new_row');
				});

			});

		} else {
			jQuery('select#add_item_id, #add_item_id_chzn .chzn-choices').css('border-color', 'red');
		}
		return false;
	});
		// Add a fee
	jQuery('#woocommerce-order-items1 button.add_order_fee').click(function(){

		$('table.woocommerce_order_items').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_writepanel_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

		var data = {
			action: 		'woocommerce_add_order_fee',
			order_id:		woocommerce_writepanel_params.post_id,
			security: 		woocommerce_writepanel_params.order_item_nonce
		};

		$.post( woocommerce_writepanel_params.ajax_url, data, function( response ) {
			jQuery('table.woocommerce_order_items1 tbody#order_items_list').append( response );
			jQuery('table.woocommerce_order_items1').unblock();
		});
		return false;  
	});
 
	
});
function change_hidden(lid,tval){
        jQuery("[name='line_total["+lid+"]']").val(tval)  ;	
         //tval		 
 }
 
 function change_tax_hidden(lid,tval){
        jQuery("[name='line_tax["+lid+"]']").val(tval)  ;	
         //tval		 
 }
 