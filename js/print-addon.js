jQuery( document ).ready( function($) {
	jQuery('.single_add_to_cart_button').click(function(){
		var x = jQuery('.product-addon-totals').find('.amount:first').text();;
		jQuery(this).append('<input type="hidden" value="'+x+'" name="totalPriceValue">');
		//return false;
	})
} );
