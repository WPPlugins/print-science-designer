<?php
/**
 * Cart Page
 *
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 2.3.8
 */

global $woocommerce, $wpdb, $attribute_names, $terms_names;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$printproducts = false;
if (function_exists('print_products_price_matrix_attr_names_init')) {
	print_products_price_matrix_attr_names_init();
	$printproducts = true;
}

$window_type = personalize_get_window_type();
$classN = "personalizep";
if ($window_type == 'New Window') { $classN = "personalize"; }

$table_name = $wpdb->prefix . "cart_data";

wc_print_notices();

do_action( 'woocommerce_before_cart' ); ?>

<form action="<?php echo esc_url( WC()->cart->get_cart_url() ); ?>" method="post">

<?php do_action( 'woocommerce_before_cart_table' ); ?>

<table class="shop_table shop_table_responsive cart woocommerce-cart-form__contents" cellspacing="0">
	<thead>
		<tr>
			<th class="product-remove">&nbsp;</th>
			<th class="product-thumb">&nbsp;</th>
			<th class="product-name"><?php _e( 'Product', 'woocommerce' ); ?></th>
			<?php if ($printproducts) { ?>
				<th class="product-quantity"><?php _e( 'Quantity', 'woocommerce' ); ?></th>
				<th class="product-price"><?php _e( 'Price', 'woocommerce' ); ?></th>
			<?php } else { ?>
				<th class="product-price"><?php _e( 'Price', 'woocommerce' ); ?></th>
				<th class="product-quantity"><?php _e( 'Quantity', 'woocommerce' ); ?></th>
				<th class="product-subtotal"><?php _e( 'Total', 'woocommerce' ); ?></th>
			<?php } ?>
		</tr>
	</thead>
	<tbody>
		<?php do_action( 'woocommerce_before_cart_contents' ); ?>

		<?php
		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$_product     = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
			$product_id   = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
			$personalize_dpdf_button = (int)get_post_meta($product_id, '_personalize_dpdf_button', true);

			$custom_tab_options = array(
				'personalize' => get_post_meta($product_id, 'personalize', true),
				'a_product_id' => get_post_meta($product_id, 'a_product_id', true)
			);
			$arr_types = array('New Window', 'Modal Pop-up window');
			if(!empty($values['variation_id'])){
				if(!isset($_SESSION['pro_' . $cart_item['product_id'] . '_' . $cart_item['variation_id'] . '_' . $cart_item_key])) {
					$_SESSION['pro_' . $cart_item['product_id'] . '_' . $cart_item['variation_id'] . '_' . $cart_item_key] = $_SESSION['sessionkey'];
				}
			}else{
				if(!isset($_SESSION['pro_' . $values['product_id'] . '_' . $cart_item_key])) {
					$_SESSION['pro_' . $cart_item['product_id'] . '_' . $cart_item_key] = $_SESSION['sessionkey'];
				}
			}

			if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
				$artwork_files = false;
				$prod_cart_data = false;
				if ($printproducts) {
					$prod_cart_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_cart_data WHERE cart_item_key = '%s'", $wpdb->prefix, $cart_item_key));
					if ($prod_cart_data) {
						$artwork_files = unserialize($prod_cart_data->artwork_files);
					}
				}
				?>
				<tr class="wp2print-cart-item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">

					<td class="product-remove">
						<?php
							echo apply_filters( 'woocommerce_cart_item_remove_link', sprintf(
								'<a href="%s" class="remove" title="%s" data-product_id="%s" data-product_sku="%s">&times;</a>',
								esc_url( WC()->cart->get_remove_url( $cart_item_key ) ),
								__( 'Remove this item', 'woocommerce' ),
								esc_attr( $product_id ),
								esc_attr( $_product->get_sku() )
							), $cart_item_key );
						?>
					</td>

					<td class="product-thumb">
						<?php
						$designer_thumb = false;
						$dataImageQuery = $wpdb->get_row("SELECT * FROM ".$table_name." WHERE uniqueID = '".$cart_item['unique_key']."'");
						if (count($dataImageQuery) > 0) {
							$printImgUrl = explode(',', $dataImageQuery->printImage);
							if (count($printImgUrl) > 0) {
								foreach($printImgUrl as $pImgUrl) { if (strlen($pImgUrl)) { $designer_thumb = true; } }
								if ($designer_thumb) { ?>
									<div class="print-products-area">
										<ul class="artwork-files-list" style="max-width:220px;">
											<?php foreach($printImgUrl as $pImgUrl) { ?>
												<li><a rel="prettyPhoto" data-rel="prettyPhoto[<?php echo $cart_item_key; ?>]" href="<?php echo $pImgUrl; ?>"><img src="<?php echo $pImgUrl; ?>" style="width:70px;display: block;"></a></li>
											<?php } ?>
										</ul>
									</div>
									<?php
								}
							} ?>
							<?php if ($dataImageQuery->editURL != '') { ?>
								<div class="editimagediv">
									<a href="<?php echo $dataImageQuery->editURL; ?>" data-rel="nofollow" data-product_id="<?php echo $product_id; ?>" data-product_sku="" class="personalize button product_type_simple"><?php _e('Re-edit', 'personalize'); ?></a>
								</div>
							<?php }
							if ($personalize_dpdf_button) {
								$sessionKey = personalize_get_session_key($cart_item_key);
								$arr_return = get_response_from_api($sessionKey);
								$pdflinks = $arr_return['pdf_urls'];
								if (count($pdflinks)) { ?>
									<div class="download-pdf-box">
										<?php foreach($pdflinks as $pdflink) { ?>
											<a href="<?php echo $pdflink; ?>" class="button"><?php _e('Download PDF', 'personalize'); ?></a><br />
										<?php } ?>
									</div>
								<?php
								}
							}
						} ?>
						<?php if (!$designer_thumb) {
							if ($artwork_files && count($artwork_files)) {
								echo '<div class="print-products-area"><ul class="artwork-files-list">';
								foreach($artwork_files as $artwork_file){
									echo '<li>'.print_products_artwork_file_html($artwork_file, $cart_item_key).'</li>';
								}
								echo '</ul></div>';
							} else {
								$thumbnail = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key);
								if (!$_product->is_visible()) {
									echo $thumbnail;
								} else {
									printf('<a href="%s">%s</a>', $_product->get_permalink($cart_item), $thumbnail);
								}
							}
						}  
						?>
					</td>

					<td class="product-name" data-title="<?php esc_attr_e( 'Product', 'woocommerce' ); ?>" style="line-height:19px;">
						<?php
							if (!$_product->is_visible() || (!empty($_product->variation_id) && !$_product->parent_is_visible() )) {
								echo apply_filters('woocommerce_in_cart_product_title', $_product->get_title(), $cart_item, $cart_item_key);
							} else {
								printf('<a href="%s"><strong>%s</strong></a>', esc_url(get_permalink(apply_filters('woocommerce_in_cart_product_id', $cart_item['product_id']))), apply_filters('woocommerce_in_cart_product_title', $_product->get_title(), $cart_item, $cart_item_key));
							}

							$item_sku = $_product->get_sku();
							if ($prod_cart_data) {
								$item_sku = print_products_get_item_sku($prod_cart_data);
							}
							if ($item_sku) { echo ' &ndash; ('.$item_sku.')'; }
							echo '<br/>';

							// attributes
							if ($prod_cart_data) {
								print_products_product_attributes_list_html($prod_cart_data);
							}

							// Meta data
							echo WC()->cart->get_item_data( $cart_item );

							// Backorder notification
							if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $cart_item['quantity'] ) ) {
								echo '<p class="backorder_notification">' . esc_html__( 'Available on backorder', 'woocommerce' ) . '</p>';
							}
						?>
					</td>

					<?php if (!$printproducts) { ?>
					<!-- Product Price -->
					<td class="product-price" data-title="<?php esc_attr_e( 'Price', 'woocommerce' ); ?>">
						<?php
						echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $values, $cart_item_key );
						?>
					</td>
					<?php } ?>

					<td class="product-quantity" data-title="<?php esc_attr_e( 'Quantity', 'woocommerce' ); ?>">
						<?php
						$ptype = print_designer_get_product_type($_product->id);
						if (in_array($ptype, array('fixed', 'book', 'area'))) {
							$product_quantity = sprintf( esc_attr($cart_item['quantity']).' <input type="hidden" name="cart[%s][qty]" value="%s" />', $cart_item_key, $cart_item['quantity'] );
						} else {
							if ( $_product->is_sold_individually() ) {
								$product_quantity = sprintf( '1 <input type="hidden" name="cart[%s][qty]" value="1" />', $cart_item_key );
							} else {
								$product_quantity = woocommerce_quantity_input( array(
									'input_name'  => "cart[{$cart_item_key}][qty]",
									'input_value' => $cart_item['quantity'],
									'max_value'   => $_product->backorders_allowed() ? '' : $_product->get_stock_quantity(),
									'min_value'   => '0'
								), $_product, false );
							}
						}
						echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item );
						?>
					</td>

					<td class="product-subtotal" data-title="<?php esc_attr_e( 'Total', 'woocommerce' ); ?>">
						<?php
						$cart_item_subtotal = $woocommerce->cart->get_product_subtotal($_product, $cart_item['quantity']);
						if ($prod_cart_data) {
							if (in_array($prod_cart_data->product_type, array('fixed', 'book', 'area'))) {
								$cart_item_subtotal = woocommerce_price($prod_cart_data->price);
							} else {
								$cart_item_subtotal = woocommerce_price($prod_cart_data->price * $prod_cart_data->quantity);
							}
						}
						echo apply_filters('woocommerce_cart_item_subtotal', $cart_item_subtotal, $cart_item, $cart_item_key);
						?>
					</td>

				</tr>
				<?php
			}
		}

		do_action( 'woocommerce_cart_contents' );
		?>
		<tr class="actions-tr">
			<td colspan="6" class="actions">

				<?php if ( WC()->cart->coupons_enabled() ) { ?>
					<div class="coupon">

						<label for="coupon_code"><?php _e( 'Coupon', 'woocommerce' ); ?>:</label> <input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e( 'Coupon code', 'woocommerce' ); ?>" /> <input type="submit" class="button" name="apply_coupon" value="<?php esc_attr_e( 'Apply Coupon', 'woocommerce' ); ?>" />

						<?php do_action( 'woocommerce_cart_coupon' ); ?>
					</div>
				<?php } ?>

				<input type="submit" class="button" name="update_cart" value="<?php esc_attr_e( 'Update Cart', 'woocommerce' ); ?>" />

				<?php do_action( 'woocommerce_cart_actions' ); ?>

				<?php wp_nonce_field( 'woocommerce-cart' ); ?>
			</td>
		</tr>

		<?php do_action( 'woocommerce_after_cart_contents' ); ?>
	</tbody>
</table>

<?php if (strpos(home_url(), 'scrubgear.com')) { ?>
<a href="#cart" class="fusion-button button-default button-medium button default medium fusion-update-cart scrubgear-update-cart"><?php esc_attr_e( 'Update Cart', 'woocommerce' ); ?></a>
<?php } ?>

<?php do_action( 'woocommerce_after_cart_table' ); ?>

</form>

<div class="cart-collaterals">

	<?php do_action( 'woocommerce_cart_collaterals' ); ?>

</div>

<?php do_action( 'woocommerce_after_cart' ); ?>
