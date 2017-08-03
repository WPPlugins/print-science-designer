<?php
function add_to_cart_redirect($url) {
    global $woocommerce;   // If product is of the subscription type
    if (is_numeric($_REQUEST['add-to-cart'])) {  // Remove default cart message
        $url = add_query_arg(array('r' => 's', 'ps_product_id' => $_REQUEST['personalize'], 'variation_id' => $_REQUEST['variation_id']), $woocommerce->cart->get_cart_url());
    }
    return $url;
}

function woocommerce_admin_notice_handler() {
    $errors = get_option('my_admin_errors');
    if ($errors) {
        echo '<div class="error"><p>' . $errors . '</p></div>';
    }
    update_option('my_admin_errors', "");
}

/* * overwrite display of cart* */
add_filter('wc_get_template', 'personalize_woo_get_template', 10, 2);
function personalize_woo_get_template($located, $template_name) {
	switch ($template_name) {
		case 'cart/cart.php':
			$located = dirname(__FILE__) . '/templates/cart.php';
		break;
	}
	return $located;
}

/** admin end on product page start
 * Custom Tabs for Product display
 * 
 * Outputs an extra tab to the default set of info tabs on the single product page.
 */
add_action('woocommerce_product_write_panel_tabs', 'personalize_tab_options_tab');
function personalize_tab_options_tab() {
	global $personalize_settings;
	if (function_exists('print_products_file_source_tab')) {
		add_filter('print_products_file_source_options', 'personalize_file_source_options', 10);
	} else {
		if ($personalize_settings) {
			?>
		    <li class="inventory_tab inventory_options"><a href="#personalization"><?php _e('Personalization', 'personalize'); ?></a></li>
			<?php
		}
	}
}

/**
 * Custom Tab Options
 * 
 * Provides the input fields and add/remove buttons for custom tabs on the single product page.
 */
add_action('woocommerce_product_write_panels', 'personalize_tab_options');
function personalize_tab_options() {
	global $post;
	if (!function_exists('print_products_file_source_tab')) {
	    $personalize = get_post_meta($post->ID, 'personalize', true); ?>
		<div id="personalization" class="panel woocommerce_options_panel">
			<div class="options_group">
				<p class="form-field"> 
					<?php woocommerce_wp_select(array('id' => 'personalize', 'class' => 'wc_input_price short', 'label' => __('Enable Personalization', 'personalize'), 'options' => array('n' => 'No', 'y' => 'Yes'), 'description' => __('Enable personalization via Print Science Designer.', 'woothemes'), 'value' => $personalize)); ?>
				</p>
				<?php personalize_designer_fields(); ?>
			</div>	
		</div>
		<?php
	}
}

function personalize_designer_fields() {
	global $wpdb, $post, $EXTERNAL_MYSQL_DATABASES;
	$a_product_id = get_post_meta($post->ID, 'a_product_id', true);
	$a_template_id = get_post_meta($post->ID, 'a_template_id', true);
	$personalize_dpdf_button = (int)get_post_meta($post->ID, '_personalize_dpdf_button', true);
	$personalize_db_links = get_post_meta($post->ID, '_personalize_db_links', true);
	if (!is_array($personalize_db_links)) {
		$personalize_db_links = array(0 => array('active' => 0, 'access' => '', 'mysql' => '', 'lookup' => '', 'source' => '', 'namespace' => '', 'field' => '', 'label' => '', 'photourl' => ''));
	}
	if (!$EXTERNAL_MYSQL_DATABASES) { $EXTERNAL_MYSQL_DATABASES = array(); }

	if (personalize_has_google_api()) {
		$personalize_gsheet_access_vals = array('direct' => __('Direct mySQL access', 'personalize'), 'registered' => __('Registered database', 'personalize'));
	} else {
		$personalize_gsheet_access_vals = array('registered' => __('Registered database', 'personalize'));
	}
	$personalize_source_vals = array('account' => __('wp2print User account', 'personalize'), 'collect' => __('Collect key field in product page', 'personalize'));
	$personalize_field_vals = $wpdb->get_results(sprintf("SELECT DISTINCT meta_key FROM %susermeta ORDER BY meta_key", $wpdb->prefix));
	$size_color_fields = false;
	if (function_exists('print_products_load_textdomain')) {
		$product_type = print_products_get_type($post->ID);
		if ($product_type == 'fixed' || $product_type == 'book' || $product_type == 'area') {
			$print_products_settings = get_option('print_products_settings');
			$size_attribute = $print_products_settings['size_attribute'];
			$colour_attribute = $print_products_settings['colour_attribute'];
			if ($size_attribute && $colour_attribute) {
				$product_matrix_options = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_matrix_types WHERE product_id = %s AND mtype = 0 ORDER BY sorder", $wpdb->prefix, $post->ID));
				if ($product_matrix_options) {
					foreach($product_matrix_options as $product_matrix_option) {
						$aterms = unserialize($product_matrix_option->aterms);
						$tsizes = $aterms[$size_attribute];
						$tcolours = $aterms[$colour_attribute];
						if ($tsizes && $tcolours) {
							$size_color_fields = true;
						}
					}
					if ($size_color_fields) {
						$terms_names = array();
						$attr_terms = $wpdb->get_results(sprintf("SELECT * FROM %sterms WHERE term_id IN (%s)", $wpdb->prefix, implode(",", array_merge($tsizes, $tcolours))));
						if ($attr_terms) {
							foreach($attr_terms as $attr_term) {
								$terms_names[$attr_term->term_id] = $attr_term->name;
							}
						}
					}
				}
			}
		}
	}
	if ($size_color_fields) {
		$personalize_sc_product_id = get_post_meta($post->ID, '_personalize_sc_product_id', true);
		$personalize_sc_template_id = get_post_meta($post->ID, '_personalize_sc_template_id', true);
		?>
		<div style="padding-left:10px;">
			<table>
				<tr>
					<td><strong><?php _e('Size', 'wp2print'); ?>&nbsp;&nbsp;</strong></td>
					<td><strong><?php _e('Colour', 'wp2print'); ?>&nbsp;&nbsp;</strong></td>
					<td><strong><?php _e('Product ID', 'personalize'); ?></strong></td>
					<td><strong><?php _e('Template ID', 'personalize'); ?></strong></td>
				</tr>
				<?php foreach($tsizes as $tsize) { ?>
					<?php foreach($tcolours as $tcolour) { ?>
						<tr>
							<td><?php echo $terms_names[$tsize]; ?>&nbsp;&nbsp;</td>
							<td><?php echo $terms_names[$tcolour]; ?>&nbsp;&nbsp;</td>
							<td><input type="text" name="personalize_sc_product_id[<?php echo $tsize; ?>][<?php echo $tcolour; ?>]" value="<?php echo $personalize_sc_product_id[$tsize][$tcolour]; ?>" style="width:120px;"></td>
							<td><input type="text" name="personalize_sc_template_id[<?php echo $tsize; ?>][<?php echo $tcolour; ?>]" value="<?php echo $personalize_sc_template_id[$tsize][$tcolour]; ?>" style="width:120px;"></td>
						</tr>
					<?php } ?>
				<?php } ?>
			</table>
		</div>
	<?php } else { ?>
		<p class="form-field">
			<label><?php _e('Product ID', 'personalize'); ?></label>
			<input type="text" name="a_product_id" class="short" value="<?php echo $a_product_id; ?>">
		</p>
		<p class="form-field">
			<label><?php _e('Template ID', 'personalize'); ?></label>
			<input type="text" name="a_template_id" class="short" value="<?php echo $a_template_id; ?>">
		</p>
	<?php } ?>
	<p class="form-field">
		<label style="width:210px;"><?php _e('Display Download PDF button in cart', 'personalize'); ?></label>
		<input type="checkbox" name="personalize_dpdf_button" value="1"<?php if ($personalize_dpdf_button == 1) { echo ' CHECKED'; } ?>>
	</p>
	<p class=""><a href="#advanced" class="advanced-config-link" style="font-size:13px;"><?php _e('Advanced configuration', 'personalize'); ?> <font>+</font></a></p>
	<div class="personalize-advanced-config" style="display:none;"><hr />
	<p class="form-field"><label style="width:80%;"><?php _e('Database Links configuration', 'personalize'); ?>:</strong></p>
	<?php $aconfig = false; ?>
	<?php for ($n=0; $n<=1; $n++) { ?>
		<hr />
		<div class="database-link-<?php echo $n; ?>">
		<p class="form-field">
			<label><?php _e('Active', 'personalize'); ?></label>
			<input type="checkbox" name="personalize_db_links[<?php echo $n; ?>][active]" value="1"<?php if ($personalize_db_links[$n]['active'] == 1) { echo ' CHECKED'; $aconfig = true; } ?>>
		</p>
		<!-- ///////////////////////////////////////////////////////////////////////////////////////// -->
		<p class="form-field">
			<label><?php _e('Google sheet access', 'personalize'); ?></label>
			<select name="personalize_db_links[<?php echo $n; ?>][access]" class="gsheet-access" style="width:80%;" onchange="personalize_gsheet_access_change(<?php echo $n; ?>)">
				<?php foreach($personalize_gsheet_access_vals as $akey => $aval) { $s = ''; if ($akey == $personalize_db_links[$n]['access']) { $s = ' SELECTED'; } ?>
					<option value="<?php echo $akey; ?>"<?php echo $s; ?>><?php echo $aval; ?></option>
				<?php } ?>
			</select>
		</p>
		<!-- ///////////////////////////////////////////////////////////////////////////////////////// -->
		<p class="form-field form-field-direct">
			<label><?php _e('Hardcoded MySQL DB', 'personalize'); ?></label>
			<select name="personalize_db_links[<?php echo $n; ?>][mysql]" class="mysql-db" style="width:80%;" onchange="personalize_mysql_change(<?php echo $n; ?>)">
				<option value="">-- <?php _e('Select Database', 'personalize'); ?> --</option>
				<?php foreach($EXTERNAL_MYSQL_DATABASES as $akey => $aval) { $s = ''; if ($akey == $personalize_db_links[$n]['mysql']) { $s = ' SELECTED'; } ?>
					<option value="<?php echo $akey; ?>"<?php echo $s; ?>><?php echo $akey; ?></option>
				<?php } ?>
			</select>
		</p>

		<p class="form-field form-field-registered">
			<label><?php _e('Registered lookup id', 'personalize'); ?></label>
			<input type="text" name="personalize_db_links[<?php echo $n; ?>][lookup]" class="short" value="<?php echo $personalize_db_links[$n]['lookup']; ?>">
		</p>
		<p class="form-field form-field-registered">
			<label><?php _e('Source of keyfield', 'personalize'); ?></label>
			<select name="personalize_db_links[<?php echo $n; ?>][source]" class="keyfield-source" style="width:80%;" onchange="personalize_source_change(<?php echo $n; ?>)">
				<?php foreach($personalize_source_vals as $akey => $aval) { $s = ''; if ($akey == $personalize_db_links[$n]['source']) { $s = ' SELECTED'; } ?>
					<option value="<?php echo $akey; ?>"<?php echo $s; ?>><?php echo $aval; ?></option>
				<?php } ?>
			</select>
		</p>

		<p class="form-field form-field-namespace" style="display:none;">
			<label><?php _e('Namespace', 'personalize'); ?></label>
			<input type="text" name="personalize_db_links[<?php echo $n; ?>][namespace]" class="short" value="<?php echo $personalize_db_links[$n]['namespace']; ?>">
		</p>
		<p class="form-field form-field-field" style="display:none;">
			<label><?php _e('Key field', 'personalize'); ?></label>
			<select name="personalize_db_links[<?php echo $n; ?>][field]" class="tbl-field" style="width:80%;">
				<option value="">-- <?php _e('Select field', 'personalize'); ?> --</option>
				<?php foreach($personalize_field_vals as $aval) { $s = ''; if ($aval->meta_key == $personalize_db_links[$n]['field']) { $s = ' SELECTED'; } ?>
					<option value="<?php echo $aval->meta_key; ?>"<?php echo $s; ?>><?php echo $aval->meta_key; ?></option>
				<?php } ?>
			</select>
		</p>
		<p class="form-field form-field-label" style="display:none;">
			<label><?php _e('Field label', 'personalize'); ?></label>
			<input type="text" name="personalize_db_links[<?php echo $n; ?>][label]" class="short" value="<?php echo $personalize_db_links[$n]['label']; ?>">
		</p>
		<p class="form-field form-field-photourl" style="display:none;">
			<label><?php _e('Photos URL', 'personalize'); ?></label>
			<input type="text" name="personalize_db_links[<?php echo $n; ?>][photourl]" class="short" value="<?php echo $personalize_db_links[$n]['photourl']; ?>">
		</p>
		</div>
	<?php } ?>
	</div>
	<script>
	var def = true;
	jQuery(document).ready(function() {
		personalize_gsheet_access_check();
		def = false;
		jQuery('.advanced-config-link').click(function(){
			if (jQuery('.personalize-advanced-config').is(':visible')) {
				jQuery('.personalize-advanced-config').animate({height: 'hide'});
				jQuery('.advanced-config-link font').text('+');
			} else {
				jQuery('.personalize-advanced-config').animate({height: 'show'}, 200);
				jQuery('.advanced-config-link font').text('-');
			}
			return false;
		});
		<?php if ($aconfig) { ?>
		jQuery('.advanced-config-link').trigger('click');
		<?php } ?>
	});
	function personalize_gsheet_access_check() {
		for (var n=0; n<=1; n++) {
			personalize_gsheet_access_change(n);
		}
	}
	function personalize_gsheet_access_change(n) {
		var dldiv = '.database-link-'+n;
		var gsaccess = jQuery(dldiv + ' select.gsheet-access').val();
		if (gsaccess == 'direct') {
			jQuery(dldiv + ' p.form-field-registered').hide();
			jQuery(dldiv + ' p.form-field-direct').show();
		} else if (gsaccess == 'registered') {
			jQuery(dldiv + ' p.form-field-direct').hide();
			jQuery(dldiv + ' p.form-field-registered').show();
		}
		personalize_source_change(n);
		personalize_mysql_change(n);
	}
	function personalize_source_change(n) {
		var dldiv = '.database-link-'+n;
		var gsaccess = jQuery(dldiv + ' select.gsheet-access').val();
		var ksource = jQuery(dldiv + ' select.keyfield-source').val();
		if (gsaccess == 'registered') {
			if (ksource == 'account') {
				jQuery(dldiv + ' p.form-field-field').show();
				jQuery(dldiv + ' p.form-field-label').hide();
			} else if (ksource == 'collect') {
				jQuery(dldiv + ' p.form-field-field').hide();
				jQuery(dldiv + ' p.form-field-label').show();
			}
		}
	}
	function personalize_mysql_change(n) {
		var dldiv = '.database-link-'+n;
		var gsaccess = jQuery(dldiv + ' select.gsheet-access').val();
		var mysqldb = jQuery(dldiv + ' select.mysql-db').val();
		if (gsaccess == 'direct') {
			if (mysqldb == 'wp2print-Houses') {
				jQuery(dldiv + ' p.form-field-label').show();
				jQuery(dldiv + ' p.form-field-photourl').show();
				jQuery(dldiv + ' p.form-field-field').hide();
				jQuery(dldiv + ' p.form-field-namespace').hide();
			} else if (mysqldb == 'wp2print-Agents') {
				jQuery(dldiv + ' p.form-field-field').show();
				jQuery(dldiv + ' p.form-field-label').hide();
				jQuery(dldiv + ' p.form-field-photourl').hide();
				jQuery(dldiv + ' p.form-field-namespace').hide();
			} else {
				jQuery(dldiv + ' p.form-field-field').hide();
				jQuery(dldiv + ' p.form-field-label').hide();
				jQuery(dldiv + ' p.form-field-photourl').hide();
				jQuery(dldiv + ' p.form-field-namespace').hide();
				if (mysqldb != '') {
					jQuery(dldiv + ' p.form-field-label').show();
					jQuery(dldiv + ' p.form-field-namespace').show();
				}
			}
		}
	}
	</script>
	<?php
}

/**
 * Process meta
 * 
 * Processes the custom tab options when a post is saved
 */
add_action('woocommerce_process_product_meta', 'process_product_meta_personalize_tab');
function process_product_meta_personalize_tab($post_id) {
	$personalize_db_links = $_POST['personalize_db_links'];
	foreach($personalize_db_links as $lkey => $lvals) {
		$active = (int)$personalize_db_links[$lkey]['active'];
		if ($active == 0) {
			$personalize_db_links[$lkey]['active'] = 0;
			$personalize_db_links[$lkey]['access'] = '';
			$personalize_db_links[$lkey]['mysql'] = '';
			$personalize_db_links[$lkey]['lookup'] = '';
			$personalize_db_links[$lkey]['source'] = '';
			$personalize_db_links[$lkey]['namespace'] = '';
			$personalize_db_links[$lkey]['field'] = '';
			$personalize_db_links[$lkey]['label'] = '';
		}
	}
	update_post_meta($post_id, 'personalize', $_POST['personalize']);
	update_post_meta($post_id, 'a_product_id', $_POST['a_product_id']);
	update_post_meta($post_id, 'a_template_id', $_POST['a_template_id']);
	update_post_meta($post_id, '_personalize_dpdf_button', $_POST['personalize_dpdf_button']);
	update_post_meta($post_id, '_personalize_sc_product_id', $_POST['personalize_sc_product_id']);
	update_post_meta($post_id, '_personalize_sc_template_id', $_POST['personalize_sc_template_id']);
	update_post_meta($post_id, '_personalize_db_links', $personalize_db_links);
}

add_filter('woocommerce_cart_item_class', 'print_designer_woocommerce_cart_item_class');
function print_designer_woocommerce_cart_item_class($class) {
	$class .= ' woo-cart-item';
	return $class;
}

/* * * Change text of add to cart for personalization
 *
 */
add_filter('add_to_cart_text', 'woo_custom_cart_button_text'); // < 2.1
add_filter('woocommerce_product_add_to_cart_text', 'woo_custom_cart_button_text'); // 2.1 +
add_filter('woocommerce_product_single_add_to_cart_text', 'woo_custom_cart_button_text'); // 2.1 +
add_filter('single_add_to_cart_text', 'woo_custom_cart_button_text');
function woo_custom_cart_button_text() {
    global $post, $personalize_settings;
    $custom_tab_options = array(
        'personalize' => get_post_meta($post->ID, 'personalize', true),
        'a_product_id' => get_post_meta($post->ID, 'a_product_id', true),
    );
    if ($custom_tab_options['personalize'] == 'y' && $personalize_settings) {
        return __('Design online', 'personalize');
    } else {
        return __('Add to cart', 'woocommerce');
    }
}

add_filter('add_to_cart_class', 'woo_custom_cart_button_class');
function woo_custom_cart_button_class() {
    global $post, $wpdb, $personalize_settings;
    $personalize = get_post_meta($post->ID, 'personalize', true);
    $window_type = personalize_get_window_type();
    if ($personalize == 'y' && $personalize_settings) {
        if ($window_type == 'Modal Pop-up window') {
            return 'personalizep';
        } else {
            return 'personalize';
        }
    } else {
        return 'add_to_cart_button';
    }
}

add_filter('woocommerce_loop_add_to_cart_link', 'woo_loop_add_to_cart_link', 10, 2);
function woo_loop_add_to_cart_link($link, $product) {
    global $personalize_settings;
    $personalize = get_post_meta($product->id, 'personalize', true);
    $window_type = personalize_get_window_type();
    if ($personalize == 'y' && $personalize_settings) {
		$class = 'personalize';
        if ($window_type == 'Modal Pop-up window') {
            $class = 'personalizep';
        }
		$link = str_replace('ajax_add_to_cart', '', $link);
		$link = str_replace('add_to_cart_button', '', $link);
		$link = str_replace('class="', 'class="'.$class.' ', $link);
    }
	return $link;
}

add_filter('woocommerce_hidden_order_itemmeta', 'psd_hidden_order_itemmeta');
function psd_hidden_order_itemmeta($metakeys) {
	$metakeys[] = '_edit_session_key';
	$metakeys[] = '_pdf_link';
	$metakeys[] = '_image_link';
	return $metakeys;
}

add_action('woocommerce_before_order_itemmeta', 'psd_before_order_itemmeta', 10, 3);
function psd_before_order_itemmeta($item_id, $item, $_product) {
	global $wpdb, $thepostid, $theorder, $woocommerce;
	$wp2print = false;
	$designer_image = ''; $pdf_link = '';
	if ($_product) {
	    if (!is_object($theorder)) { $theorder = new WC_Order($thepostid); }
	    $order = $theorder;
		if ($metadata = $order->has_meta($item_id)) {
			foreach ($metadata as $meta) {
				if ($meta['meta_key'] == '_pdf_link') {
					$pdf_link = $meta['meta_value'];
				} else if ($meta['meta_key'] == '_image_link') {
					$designer_image = $meta['meta_value'];
				}
			}
		}
	}
	if (function_exists('print_products_price_matrix_attr_names_init')) {
		$wp2print = true;
		print_products_price_matrix_attr_names_init();
		$order_item_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_order_items WHERE item_id = '%s'", $wpdb->prefix, $item_id));
		if ($order_item_data) {
			$item_sku = print_products_get_item_sku($order_item_data);
			if ($item_sku) {
				echo ' &ndash; (' . esc_html($item_sku) . ')';
			}
			print_products_product_attributes_list_html($order_item_data);
			$artwork_files = unserialize($order_item_data->artwork_files);
			if ($artwork_files) { ?>
				<div class="print-products-area">
					<ul class="product-attributes-list">
						<li><?php _e('Artwork Files', 'print-products'); ?>:</li>
						<li><ul class="product-artwork-files-list">
							<?php foreach($artwork_files as $artwork_file) {
								echo '<li>'.print_products_artwork_file_html($artwork_file, 'download').'</li>';
							} ?>
						</ul></li>
					</ul>
				</div>
			<?php }
		}
	}
	if (strlen($designer_image)) {
		$dimages = explode(',', $designer_image); ?>
		<div class="print-products-area">
			<ul class="product-attributes-list">
				<li><?php _e('Designer File(s)', 'personalize'); ?>:</li>
				<li>
					<ul class="product-artwork-files-list">
						<?php foreach($dimages as $dimage) { ?>
							<li><a href="<?php echo $dimage; ?>" target="_blank"><img src="<?php echo $dimage; ?>" style="width:70px;border:1px solid #C1C1C1;"></a></li>
						<?php } ?>
					</ul>
				</li>
		</div>
		<?php
	}
	if (strlen($pdf_link)) { $pdf_links = explode(',', $pdf_link); ?>
		<div class="print-products-area">
			<ul class="product-attributes-list">
				<li><?php _e('PDF File(s)', 'personalize'); ?>:</li>
				<li>
					<ul class="product-artwork-files-list">
						<?php foreach($pdf_links as $pdf_link) { ?>
							<li><a href="<?php echo $pdf_link; ?>" title="<?php _e('Download', 'personalize'); ?>" target="_blank"><img src="<?php echo plugins_url(); ?>/print-science-designer/images/icon_doc_pdf.png"></a></li>
						<?php } ?>
					</ul>
				</li>
		</div>
	<?php }
}

add_action('woocommerce_order_item_meta_end', 'print_designer_woo_order_item_meta_start', 10, 2);
function print_designer_woo_order_item_meta_start($item_id, $item) {
	global $api_info;
	if ((int)$api_info->show_pdf) {
		$pdf_link = wc_get_order_item_meta($item_id, '_pdf_link', true);
		if (strlen($pdf_link)) {
			$pdf_links = explode(',', $pdf_link);
			foreach($pdf_links as $pdf_link) {
				echo '<br/><a href="'.$pdf_link.'">'.__('Download PDF', 'personalize').'</a>';
			}
		}
	}
}

add_filter('woocommerce_add_cart_item_data', 'namespace_force_individual_cart_items', 10, 5);
function namespace_force_individual_cart_items($cart_item_data, $product_id, $post_data = null) {
    global $woocommerce;
	global $wpdb;
	if ( is_null( $post_data ) ) {
		$post_data = $_POST;
	}
    $variation = '';
    $added_to_cart = array();
    $adding_to_cart = get_product($product_id);
    $attributes = $adding_to_cart->get_attributes();
    $variations = array();
    $variation = get_product($post_data);
    // Verify all attributes
    foreach ($attributes as $attribute) {
        if (!$attribute['is_variation']) {
            continue;
        }
        $taxonomy = 'attribute_' . sanitize_title($attribute['name']);
        if (isset($_REQUEST[$taxonomy])) {
            // Get value from post data
            // Don't use wc_clean as it destroys sanitized characters
            $value = sanitize_title(trim(stripslashes($_REQUEST[$taxonomy])));
            // Get valid value from variation
            $valid_value = $variation->variation_data[$taxonomy];
            // Allow if valid
            if ($valid_value == '' || $valid_value == $value) {
                if ($attribute['is_taxonomy']) {
                    $variations[$taxonomy] = $value;
                } else {
                    // For custom attributes, get the name from the slug
                    $options = array_map('trim', explode(WC_DELIMITER, $attribute['value']));
                    foreach ($options as $option) {
                        if (sanitize_title($option) == $value) {
                            $value = $option;
                            break;
                        }
                    }
                    $variations[$taxonomy] = $value;
                }
                continue;
            }
        }
        $all_variations_set = false;
    }
	$unique_cart_item_key = md5(microtime() . rand() . "Hi Mom!");

	foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $cart_item ) {
		if($cart_item['product_id'] == $product_id ){
			if ($post_data == '') {
				if(!isset($_SESSION['pro_' . $product_id . '_' . $cart_item_key])) {
					$_SESSION['pro_' . $product_id . '_' . $cart_item_key] = $_SESSION['sessionkey'];
				}
			} else {
				if(!isset($_SESSION['pro_' . $product_id . '_' . $post_data . '_' . $cart_item_key])) {
					$_SESSION['pro_' . $product_id . '_' . $post_data . '_' . $cart_item_key] = $_SESSION['sessionkey'];
				}
			}
		}
	}
	
	$insert = array();
	$insert['uniqueID'] = $unique_cart_item_key;
	$wpdb->insert(CART_DATA_TABLE, $insert);

	$cart_item_data['unique_key'] = $unique_cart_item_key;
	$cart_item_data['successUrl'] = $_SESSION['successUrl'];
	if ($_REQUEST['db_link_key']) {
		$cart_item_data['db_link_key'] = $_REQUEST['db_link_key'];
	}

    return $cart_item_data;
}

add_action('woocommerce_add_to_cart', 'psd_add_to_cart_action', 10, 6);
function psd_add_to_cart_action($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
	global $wpdb;
	$personalize = get_post_meta($product_id, 'personalize', true);
	$spkey = 'pro_' . $product_id . '_' . $cart_item_key;
	if ($variation_id != '') {
		$spkey = 'pro_' . $product_id . '_' . $variation_id . '_' . $cart_item_key;
	}
	if (!$_SESSION[$spkey]) {
		$_SESSION[$spkey] = $_SESSION['sessionkey'];
	}
	if ($_REQUEST['atcaction'] != 'artwork' && $personalize == 'y') {
		$woocommerce_cart_page_id = get_option('woocommerce_cart_page_id');
		$arr_return = get_response_from_api($_SESSION[$spkey]);
		$cart_url = get_permalink($woocommerce_cart_page_id);
		if (strpos($cart_url, '?')) { $cart_url .= '&'; } else { $cart_url .= '?'; }
		$editURL = $cart_url.'re_edit='.$product_id.'&cart_item_key='.$cart_item_key;
		$printImage = implode(',', $arr_return['img_urls']);
	}
	$update = array();
	$update['printImage'] = $printImage;
	$update['editURL'] = $editURL;
	$update['cart_item_key'] = $cart_item_key;
	$update['sessionKey'] = $_SESSION['sessionkey'];
	$wpdb->update(CART_DATA_TABLE, $update, array('uniqueID' => $cart_item_data['unique_key']));

	if ($_REQUEST['redesign'] == 'true') {
		$order_items = $wpdb->get_results(sprintf("SELECT * FROM %swoocommerce_order_itemmeta WHERE meta_key = '_edit_session_key' AND meta_value = '%s'", $wpdb->prefix, $_SESSION['sessionkey']));
		if ($order_items) {
			foreach($order_items as $order_item) {
				$update = array();
				$update['meta_value'] = $printImage;
				$wpdb->update($wpdb->prefix.'woocommerce_order_itemmeta', $update, array('order_item_id' => $order_item->order_item_id, 'meta_key' => '_image_link'));
			}
		}
	}
}

add_action('woocommerce_add_order_item_meta', 'save_item_meta', 10, 3);
function save_item_meta($item_id, $product, $cart_item_key) {
    global $woocommerce, $wpdb;

	$personalize = get_post_meta($product['product_id'], 'personalize', true);
	if ($personalize == 'y') {
		$atcaction = 'design';
		$sessionKey = personalize_get_session_key($cart_item_key);

		if (function_exists('print_products_checkout_process_init')) {
			$prod_cart_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_cart_data WHERE cart_item_key = '%s'", $wpdb->prefix, $cart_item_key));
			if ($prod_cart_data && $prod_cart_data->atcaction) {
				$atcaction = $prod_cart_data->atcaction;
			}
		}
		if ($atcaction == 'design') {
			if (isset($product['variation_id']) && $product['variation_id'] != '') {
				unset($_SESSION['pro_' . $product['product_id'] . '_' . $product['variation_id'] . '_' . $cart_item_key]);
			} else {
				unset($_SESSION['pro_' . $product['product_id'] . '_' . $cart_item_key]);
			}
			$arr_return = get_response_from_api($sessionKey);
			if ($arr_return) {
				$pdflink = implode(",", $arr_return['pdf_urls']);
				$imagelink = implode(",", $arr_return['img_urls']);

				wc_add_order_item_meta($item_id, '_edit_session_key', $sessionKey);
				wc_add_order_item_meta($item_id, '_pdf_link', $pdflink);
				wc_add_order_item_meta($item_id, '_image_link', $imagelink);

				$cart_data = WC()->cart->get_cart();
				$cart_item_data = $cart_data[$cart_item_key];
				if ($cart_item_data['db_link_key']) {
					wc_add_order_item_meta($item_id, '_db_link_key', $cart_item_data['db_link_key']);
				}

				if ( is_plugin_active('woocommerce-product-addons/woocommerce-product-addons.php') ) {
					if ( ! empty( $arr_return['addons'] ) ) {
						foreach ( $arr_return['addons'] as $addon ) {
							$name = $addon['name'];
							woocommerce_add_order_item_meta( $item_id, $name, $addon['value'] );
						}
					}
				}
			}
		}
	}
}

add_action('woocommerce_order_status_changed', 'personalize_order_status_changed', 10, 3);
function personalize_order_status_changed($order_id, $old_status, $new_status) {
	global $wpdb;
	if (!is_admin()) {
		if (WC()->cart->get_cart() && $new_status != 'cancelled') {
			foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
				$wpdb->delete(CART_DATA_TABLE, array('cart_item_key' => $cart_item_key));
			}
		}
	}
}

add_action('woocommerce_cart_item_removed', 'personalize_cart_item_remove_data', 10, 2);
function personalize_cart_item_remove_data($cart_item_key, $item) {
	global $wpdb;
	$wpdb->delete(CART_DATA_TABLE, array('cart_item_key' => $cart_item_key));
}

function validate_add_cart_item_personal( $passed, $product_id, $qty, $post_data = null ) {
	$product_addons = get_product_addons( $product_id );
	if ( is_array( $product_addons ) && ! empty( $product_addons ) ) {
		include_once( WP_PLUGIN_DIR.'/woocommerce-product-addons/classes/fields/abstract-class-product-addon-field.php' );
		foreach ( $product_addons as $addon ) {		
			$value = isset( $_REQUEST[ 'addon-' . $addon['field-name'] ] ) ? $_REQUEST[ 'addon-' . $addon['field-name'] ] : '';
			if ( is_array( $value ) ) {
				$value = array_map( 'stripslashes', $value );
			} else {
				$value = stripslashes( $value );
			}

			switch ( $addon['type'] ) {
				case "checkbox" :
				case "radiobutton" :
					include_once( WP_PLUGIN_DIR.'/woocommerce-product-addons/classes/fields/class-product-addon-field-list.php' );
					$field = new Product_Addon_Field_List( $addon, $value );
				break;
				case "custom" :
				case "custom_textarea" :
				case "custom_price" :
				case "input_multiplier" :
					include_once( WP_PLUGIN_DIR.'/woocommerce-product-addons/classes/fields/class-product-addon-field-custom.php' );
					$field = new Product_Addon_Field_Custom( $addon, $value );
				break;
				case "select" :
					include_once( WP_PLUGIN_DIR.'/woocommerce-product-addons/classes/fields/class-product-addon-field-select.php' );
					$field = new Product_Addon_Field_Select( $addon, $value );
				break;
				case "file_upload" :
					include_once( WP_PLUGIN_DIR.'/woocommerce-product-addons/classes/fields/class-product-addon-field-file-upload.php' );
					$field = new Product_Addon_Field_File_Upload( $addon, $value );
				break;
			}
			$data = $field->validate();
			if($data!==true){
				if ( is_wp_error( $data ) ) {
					wc_add_notice( $data->get_error_message(), 'error' );
					return false;
				}
		   }

		}
	}
	return $passed;	
}

add_filter('woocommerce_hidden_order_itemmeta', 'personalize_woo_hidden_order_itemmeta');
function personalize_woo_hidden_order_itemmeta($itemmeta) {
	$itemmeta[] = '_db_link_key';
	return $itemmeta;
}

add_action('woocommerce_before_add_to_cart_button', 'personalize_woocommerce_before_add_to_cart_button');
function personalize_woocommerce_before_add_to_cart_button() {
	global $post;
	$personalize_db_links = get_post_meta($post->ID, '_personalize_db_links', true);
	if ($personalize_db_links) {
		foreach($personalize_db_links as $lkey => $personalize_db_link) {
			if ($personalize_db_link['active'] && personalize_db_link_has_field($personalize_db_link)) { ?>
				<div class="db-link-key">
					<label><?php echo $personalize_db_link['label']; ?>:</label>
					<input type="text" name="db_link_key" data-empty-error="<?php echo $personalize_db_link['label']; ?> <?php _e('is required field', 'personalize'); ?>."  data-not-found-error="<?php _e('This MLS ID was not found in the database.', 'personalize'); ?>" data-mysql="<?php echo $personalize_db_link['mysql']; ?>" data-siteurl="<?php bloginfo('url'); ?>">
					<img src="<?php echo plugins_url(); ?>/print-science-designer/images/loading.gif" class="db-link-key-loading" style="display: inline; visibility:hidden;">
				</div>
				<?php
				return;
			}
		}
	}
}
?>