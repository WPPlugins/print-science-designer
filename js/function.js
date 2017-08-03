var fromfunc = false;
jQuery(document).ready(function($) {
	jQuery('.product-thumbnail').show();
	jQuery('table.cart thead').css('text-indent', 'inherit').find('th').css('padding', '0.857em 1.387em');
	jQuery.each(jQuery('.product_type_simple'), function() {
        if(jQuery(this).html()=='Personalize'){
		    jQuery(this).removeClass('add_to_cart_button').addClass('personalize');
		}
    });

	jQuery('a.personalizep').click(function(){
		var host = jQuery("[name='host']").val();
		var href = jQuery(this).attr('href');
		var url = host + href;
		jQuery(this).bind('click');
		jQuery('#popup_frame').attr('src',url);
	});

	if (!jQuery('button.personalizep').size()) {
		jQuery('button.personalize').click(function(){
			if (jQuery('.db-link-key').size()) {
				personalize_check_db_link_key(0);
				return false;
			} else {
				personalize_show_designer_loading();
				return true;
			}
		});
	}

	jQuery('button.personalizep').click(function(){
		if (jQuery('.db-link-key').size() && !fromfunc) {
			personalize_check_db_link_key(1);
			return false;
		} else {
			personalize_process_popup();
			return true;
		}
	}); 

	jQuery(window).load(function() {
		jQuery('.personalizationGallery').each(function(index, el) {
			var imgHeightList = [];
			
			jQuery(el).find('img').each(function(index, el) {	
				jQuery(el).css('display', 'block');
				imgHeightList.push(jQuery(el).height()); 
			});
			
			jQuery(el).data('tmpHeight', Math.max.apply(null, imgHeightList));
		});
		
		jQuery('.personalizationGallery').cycle({ 
			fx:     'fade', 
			speed:   300, 
			timeout: 3000,
			pause:   1
		});
			
		jQuery('.personalizationGallery').each(function(index, el) {
			jQuery(el).css('height', jQuery(el).data('tmpHeight'));
		});		
	});
});

function personalize_process_popup() {
	var data_array = jQuery(".variations_form").serialize();
	var product_addon = jQuery(".addon").serialize();
	var server_url = jQuery("[name='server_url']").val();	
	var product_type = jQuery('.add-cart-form .product-type').val();
	var atcaction = jQuery('.add-cart-form .atc-action').val();

	if(data_array == '' && product_addon == ''){
		var attcart = jQuery("[name='add-to-cart']").val();
		var quantity = jQuery("[name='quantity']").val();	
		data_array = 'add-to-cart='+attcart+'&quantity='+quantity; 
	}
	if(product_addon != '' && data_array == "" ){
		var attcart= jQuery("[name='add-to-cart']").val();
		var quantity= jQuery("[name='quantity']").val();	
		data_array = 'add-to-cart='+attcart+'&quantity='+quantity+'&'+product_addon;
	} 
	if(product_addon=='' && data_array != "" ){
		var attcart= jQuery("[name='add-to-cart']").val();
		var quantity= jQuery("[name='quantity']").val();	
		data_array = 'add-to-cart='+attcart+'&quantity='+quantity+'&'+data_array;
	} 
	if(product_addon!='' && data_array != "" ){
		var attcart= jQuery("[name='add-to-cart']").val();
		var quantity= jQuery("[name='quantity']").val();	
		data_array = 'add-to-cart='+attcart+'&quantity='+quantity+'&'+data_array+'&'+product_addon;
	} 
	if (product_type) {
		data_array += '&print_products_checkout_process_action=add-to-cart&product_type='+product_type+'&atcaction='+atcaction;
		if (jQuery('.add-cart-form .sm-params').size()) {
			var smparams = jQuery('.add-cart-form .sm-params').val();
			data_array += '&smparams='+smparams;
		}
		if (jQuery('.add-cart-form .fm-params').size()) {
			var fmparams = jQuery('.add-cart-form .fm-params').val();
			data_array += '&fmparams='+fmparams;
		}
		if (jQuery('.add-cart-form .db-link-key').size()) {
			var db_link_key = jQuery('.add-cart-form .db-link-key input').val();
			data_array += '&db_link_key='+db_link_key;
		}
		if (product_type == 'book') {
			jQuery('.numbers-list .quantity').each(function(){
				var pqtyname = jQuery(this).attr('name');
				var pqty = jQuery(this).val();
				data_array += '&'+pqtyname+'='+pqty;
			});
		} else if (product_type == 'area') {
			var width = jQuery('.add-cart-form .width').val();
			var height = jQuery('.add-cart-form .height').val();
			data_array += '&width='+width+'&height='+height;
		}
		if (product_type == 'book' || product_type == 'fixed' || product_type == 'area') {
			if (price < 0) { return false; }
		}
	}
	var url = server_url;
	var strfind = server_url.indexOf('?');
	if(strfind > 0) {
		var querystr = url;
		url += "&" + data_array;
	} else {
		url += "?" + data_array;
	}

	jQuery('button.personalizep').bind('click');
	jQuery('#popup_frame').attr('src',url);
	document.getElementById('popup_frame').onload = function() {
		jQuery('#popup-wrapper').css('background-position', '-3000px -3000px');
	};
}

function personalize_check_db_link_key(p) {
	var dlkval = strtrim(jQuery('.db-link-key input').val());
	var mysql = jQuery('.db-link-key input').attr('data-mysql');
	var siteurl = jQuery('.db-link-key input').attr('data-siteurl');
	var empty_error = jQuery('.db-link-key input').attr('data-empty-error');
	var not_found_error = jQuery('.db-link-key input').attr('data-not-found-error');
	if (dlkval == '') {
		alert(empty_error);
	} else {
		jQuery('.db-link-key .db-link-key-loading').css('visibility', 'visible');
		jQuery.post(
			siteurl,
			{
				PersonalizeAjaxAction: 'check-db-link-key',
				db_link_key: dlkval,
				mysql_db: mysql
			},
			function(data) {
				jQuery('.db-link-key .db-link-key-loading').css('visibility', 'hidden');
				if (data == 'success') {
					if (p == 1) {
						fromfunc = true;
						jQuery('button.personalizep').trigger('click');
						fromfunc = false;
					} else {
						personalize_show_designer_loading();
						jQuery('form.cart').submit();
					}
				} else {
					alert(not_found_error);
				}
			}
		);
	}
}

function personalize_show_designer_loading() {
	jQuery('.designer-loading-mask').show();
}

function closethepopup(){
	window.parent.location.href=jQuery('#server_url').val();
	jQuery('#popup_frame').attr("src", "");
}

function strtrim(str) {
	if (str != 'undefined') {
		return str.replace(/^\s+|\s+$/g,"");
	} else {
		return '';
	}
}

jQuery(function () {
	var maskWidth = document.body.clientWidth;
	var maskHeight = jQuery(window).height();
    var margin =  jQuery("[name='margin']").val();
    jQuery("#popup-wrapper").css("width",(maskWidth - (2*margin)));
	jQuery("#popup-wrapper").css("height",(maskHeight - 2*margin));
	jQuery("#popup_frame").css("width",(maskWidth - 2*margin));
	jQuery("#popup_frame").css("height",(maskHeight - 2*margin));	 

	jQuery("#popup-wrapper").modalPopLite({openButton:".personalizep",closeButton:"#close-btn", isModal: true });
});
