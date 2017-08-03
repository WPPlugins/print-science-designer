<?php
/* 
Plugin Name: Print Science Designer
Plugin URI: http://printscience.com/designer/
Description: Link between WooCommerce and the Print Science Designer to allow for product personalization and online design
Version: 1.3.42
Author: Print Science
Author URI: http://printscience.com
Text Domain: personalize
License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

include_once ABSPATH . 'wp-admin/includes/plugin.php';
if (!class_exists('xmlrpc_client')) {
	include('includes/xmlrpc.inc');
}

session_start();

global $wpdb;
define('API_INFO_TABLE', $wpdb->prefix . 'api_info');
define('CART_DATA_TABLE', $wpdb->prefix . 'cart_data');

include(__DIR__ . '/personalize-install.php');
include(__DIR__ . '/personalize-functions.php');
include(__DIR__ . '/personalize-woo.php');
include(__DIR__ . '/includes/external-db-class.php');

$api_info = false;
$personalize_settings = false;

add_action('wp_loaded', 'personalize_wp_loaded');
function personalize_wp_loaded() {
	if (isset($_REQUEST['add-to-cart']) && $_REQUEST['add-to-cart'] && $_REQUEST['personalize']) {
		$_POST = $_REQUEST;
	}
}

add_action('init', 'personalize_init');
function personalize_init() {
	global $wpdb, $personalize_settings, $api_info;

	$personalize_data = get_plugin_data(__FILE__);
	// check plugin version
	$personalize_version = get_option('personalize_version');
	if ($personalize_version != $personalize_data['Version']) {
		deactivate_plugins(plugin_basename(__FILE__));
		activate_plugin(__FILE__);
		update_option('personalize_version', $personalize_data['Version']);
	}

	$api_info = $wpdb->get_row(sprintf("SELECT * FROM %s WHERE id = 1", API_INFO_TABLE));
	if ($api_info) {
		if (strlen($api_info->username) && strlen($api_info->api_key)) {
			$personalize_settings = true;
		}
	}
	personalize_actions();
}

add_action( 'plugins_loaded', 'personalize_load_textdomain' );
function personalize_load_textdomain() {
	load_plugin_textdomain( 'personalize', false, trailingslashit( WP_LANG_DIR ) . 'plugins/' );
	load_plugin_textdomain( 'personalize', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' ); 
}

register_activation_hook(__FILE__, 'ps_install');

add_action('admin_menu', 'personalize_admin_menu');
function personalize_admin_menu() {
    add_options_page('Personalization API', 'Personalization API', 'manage_options', 'personalize.php', 'personalize_admin_settings');
}

// admin config view 
function personalize_admin_settings() {
    global $wpdb, $api_info;
	include(__DIR__ . '/personalize-admin.php');
}

add_action('admin_enqueue_scripts', 'personalize_admin_enqueue_scripts');
function personalize_admin_enqueue_scripts($hook) {
	global $pagenow;
    wp_enqueue_script('function', plugins_url() . '/print-science-designer/js/admin.js', array(), '1.0.0', true);
}
/** frontend
 *
 * add script 	   
 */
add_action('wp_enqueue_scripts', 'personalize_wp_enqueue_scripts'); 
function personalize_wp_enqueue_scripts() {
    global $woocommerce;
    wp_enqueue_style('print_science', plugins_url() . '/print-science-designer/css/style.css');
    wp_enqueue_style('modalPopLite', plugins_url() . '/print-science-designer/css/modalPopLite.css');
    wp_enqueue_script('modalPopLite.min', plugins_url() . '/print-science-designer/js/modalPopLite.min.js', array(), '1.0.0', true);
    wp_enqueue_script('function', plugins_url() . '/print-science-designer/js/function.js', array(), '1.0.0', true);
	wp_enqueue_script('function-cycle', plugins_url() . '/print-science-designer/js/jquery.cycle.lite.min.js', array(), '1.0.0', true);
	wp_enqueue_script('print-addon', plugins_url() . '/print-science-designer/js/print-addon.js', array(),'1.0.0', true);
	wp_enqueue_script('prettyPhoto', $woocommerce->plugin_url() . '/assets/js/prettyPhoto/jquery.prettyPhoto.min.js', array('jquery'), $woocommerce->version, true);
	wp_enqueue_script('prettyPhoto-init', $woocommerce->plugin_url() . '/assets/js/prettyPhoto/jquery.prettyPhoto.init.min.js', array('jquery'), $woocommerce->version, true);
	wp_enqueue_style('woocommerce_prettyPhoto_css', $woocommerce->plugin_url() . '/assets/css/prettyPhoto.css');
	if (strpos(home_url(), 'scrubgear.com')) {
	    wp_enqueue_style('scrubgear_styles', plugins_url() . '/print-science-designer/css/scrubgear.css');
	}
}

/**
 * 
 * Ajax actions
 *
 */
function personalize_actions() {
	if (isset($_POST['PersonalizeAjaxAction'])) {
		if ($_POST['PersonalizeAjaxAction'] == 'check-db-link-key') {
			$db_link_key = $_POST['db_link_key'];
			$mysql_db = $_POST['mysql_db'];
			if ($mysql_db == 'wp2print-Houses') {
				$hdb = new ExternalDB('wp2print-Houses');
				if ($hdb->is_sucess()) {
					$house_info = $hdb->get_row(sprintf("SELECT * FROM wp_rps_property WHERE DdfListingID = '%s'", $db_link_key));
					if (!$house_info) {
						$house_info = $hdb->get_row(sprintf("SELECT * FROM wp_rps_property WHERE ListingID = %s", $db_link_key));
					}
					if ($house_info) {
						echo 'success';
					} else {
						echo 'error';
					}
				}
			}
		}
		exit;
	}
}

function personalize_get_return_url() {
	$current_url = $_SERVER['HTTP_REFERER'];
	if (strlen($_SERVER['QUERY_STRING'])) {
		$current_url .= '?' . $_SERVER['QUERY_STRING'];
	}
	return $current_url;
}

/**
 * 
 * Change text of add to cart into personalization
 *
 */
// add_filter('woocommerce_add_to_cart_message', 'on_add_cart');	
add_action('init', 'on_add_cart', 10);
function on_add_cart() {
    global $wpdb, $wp_query;

	if (isset($_REQUEST['add-to-cart']) && strlen($_REQUEST['add-to-cart']) && strlen($_REQUEST['personalize'])) {
		$_POST = $_REQUEST;
	}

    $window_type = personalize_get_window_type();
    if (isset($_REQUEST['re_edit']) && $_REQUEST['re_edit'] != '') {
        if (isset($_REQUEST['variation_id']) && $_REQUEST['variation_id'] != '') {
            do_action('revise_api_content', $_REQUEST['re_edit'], $_REQUEST['variation_id'], $_REQUEST['cart_item_key']);
        } else {
            do_action('revise_api_content', $_REQUEST['re_edit'], 0, $_REQUEST['cart_item_key']);
        }
    }
    //replace class of button on product detail page
    if ($window_type != 'New Window') {
        add_filter('wp_head', 'personalize_script');
    }
    if (!isset($_REQUEST['a'])) {
		$custom_tab_options = array('personalize' => '');
		if (isset($_REQUEST['add-to-cart'])) {
			$product_id = $_REQUEST['add-to-cart'];
			$custom_tab_options = array(
				'personalize' => get_post_meta($product_id, 'personalize', true),
				'a_product_id' => get_post_meta($product_id, 'a_product_id', true),
			);
		}
		if (isset($_REQUEST['add-to-cart'])) {
			if (!strlen($custom_tab_options['personalize'])) {
				$custom_tab_options['personalize'] = 'n';
			}
		}
        if ($custom_tab_options['personalize'] == 'n' || (isset($_REQUEST['atcaction']) && ($_REQUEST['atcaction'] == 'artwork' || $_REQUEST['atcaction'] == 'designnochange'))) {
            if (isset($_REQUEST['add-to-cart'])) {
                if (isset($_SESSION['pro_' . $_REQUEST['add-to-cart'] . '_' . $_REQUEST['variation_id']])) {
                    unset($_SESSION['pro_' . $_REQUEST['add-to-cart'] . '_' . $_REQUEST['variation_id']]);
                } else {
                    if (isset($_SESSION['pro_' . $_REQUEST['add-to-cart']])) {
                        unset($_SESSION['pro_' . $_REQUEST['add-to-cart']]);
                    }
                }
            }
        } else {
            if ((isset($_REQUEST['add-to-cart']) || isset($_REQUEST['added-to-cart']) ) && !isset($_REQUEST['add']) && !isset($_REQUEST['q']) && (!isset($_REQUEST['personalize']))) {
				$serverURL = personalize_get_return_url();
                if (isset($_REQUEST['add-to-cart'])) {
                    $successUrl = remove_query_arg(array('add-to-cart'), $serverURL);
                    $product_id = $_REQUEST['add-to-cart'];
                }
                if (isset($_REQUEST['added-to-cart'])) {
                    $successUrl = remove_query_arg(array('added-to-cart'), $serverURL);
                    $product_id = $_REQUEST['added-to-cart'];
                }
                $successUrl = add_query_arg(array('add' => $product_id), $successUrl);
                if (isset($_REQUEST['quantity']) && $_REQUEST['quantity'] != '') {
                    $successUrl = add_query_arg(array('q' => $_REQUEST['quantity']), $successUrl);
                }
                foreach ($_REQUEST as $valuea => $valueV) {
                    if ($valuea != 'product' && $valuea != 'quantity' && $valuea != 'add-to-cart' && $valuea != 'product_id') {
						if (!is_array($valueV)) { $valueV = urlencode($valueV); }
                        $successUrl = add_query_arg(array($valuea => $valueV), $successUrl);
                    }
                }
                wp_redirect($successUrl);
                exit;
            }
            if (isset($_REQUEST['add']) && $_REQUEST['add'] != '' && (!isset($_REQUEST['add-to-cart']) || !isset($_REQUEST['added-to-cart'])) && (!isset($_REQUEST['personalize']))) {
				$serverURL = personalize_get_return_url();
                $successUrl = remove_query_arg(array('add'), $serverURL);
                $product_id = $_REQUEST['add'];
                $custom_tab_options = array(
                    'personalize' => get_post_meta($product_id, 'personalize', true),
                    'a_product_id' => get_post_meta($product_id, 'a_product_id', true),
                );
                if ($custom_tab_options['personalize'] == 'y') {
					$serverURL = personalize_get_return_url();
					$successUrl = add_query_arg(array('ps_product_id' => $product_id, 'a' => 'w'), $serverURL);
					foreach ($_REQUEST as $valuea => $valueV) {
						if ($valuea != 'product' && $valuea != 'quantity' && $valuea != 'add-to-cart' && $valuea != 'product_id') {
							$successUrl = add_query_arg(array($valuea => $valueV), $successUrl);
						}
					}
					wp_redirect($successUrl);
					exit;
                }
            }
            add_filter('wc_add_to_cart_message', 'custom_add_to_cart_message');
            if ($window_type == 'Modal Pop-up window') {
                $custom_redirect = '';
				if (isset($_GET['custom'])) {
	                $custom_redirect = $_GET['custom'];
				}
                $request_url = $_SERVER['REQUEST_URI'];
                if ($custom_redirect != '1' && isset($_GET['add']) && $_GET['add'] > 0) {
                    ?>
                    <script>
                        window.parent.location = '<?php echo $request_url; ?>&custom=1';
                    </script>
                    <?php
                    exit;
                }
            }
        }
    }
}
function personalize_script() {
	?>
    <script>
		jQuery(document).ready(function($){
			jQuery.each(jQuery('.product_type_simple'), function() {
				if(jQuery(this).html()=='<?php _e('Design online', 'personalize'); ?>'){
					jQuery(this).removeClass('add_to_cart_button').addClass('personalizep');
				}
			});
			if(jQuery('.cart .button').html()=='<?php _e('Design online', 'personalize'); ?>'){
				jQuery('.cart .single_add_to_cart_button').removeClass('single_add_to_cart_button').addClass('personalizep');
			}else if(jQuery('.cart .button').html()=='<?php _e('Add to cart', 'woocommerce'); ?>'){
				jQuery('.cart .personalizep').removeClass('personalizep').addClass('single_add_to_cart_button');
			}
		});
	</script>
	<?php
}

add_action('init', 'personalize_open_on_ini');
function personalize_open_on_ini() {
    global $wpdb, $personalize_settings, $api_info;

	if ($personalize_settings) {
		if (isset($_REQUEST['add-to-cart'])) {
			$product_id = $_REQUEST['add-to-cart'];
			$personalize = get_post_meta($product_id, 'personalize', true);
			$locale = apply_filters( 'plugin_locale', get_locale(), 'personalize' );
			$localLang = explode("_", $locale);
		}
		// code to initiate request for api
		if (isset($_REQUEST['a'])) {
			$TemplatexML = new xmlrpcval(null, 'null');
			$product_id = $_REQUEST['ps_product_id'];
			$templateId = get_post_meta($product_id, 'a_product_id', true);
			$Template_ID = get_post_meta($product_id, 'a_template_id', true);

			if (function_exists('print_products_load_textdomain')) {
				$print_products_settings = get_option('print_products_settings');
				$size_attribute = $print_products_settings['size_attribute'];
				$colour_attribute = $print_products_settings['colour_attribute'];
				$personalize_sc_product_id = get_post_meta($product_id, '_personalize_sc_product_id', true);
				$personalize_sc_template_id = get_post_meta($product_id, '_personalize_sc_template_id', true);

				$size_val = false;
				$colour_val = false;
				$smparams = explode('|', $_REQUEST['smparams']);
				if (count($smparams)) {
					$aparams = explode('-', $smparams[1]);
					foreach($aparams as $aparam) {
						$akeyval = explode(':', $aparam);
						if ($akeyval[0] == $size_attribute) {
							$size_val = $akeyval[1];
						}
						if ($akeyval[0] == $colour_attribute) {
							$colour_val = $akeyval[1];
						}
					}
					if ($size_val && $colour_val) {
						if ($personalize_sc_product_id) {
							if ($personalize_sc_product_id[$size_val][$colour_val]) {
								$templateId = $personalize_sc_product_id[$size_val][$colour_val];
							}
						}
						if ($personalize_sc_template_id) {
							if ($personalize_sc_template_id[$size_val][$colour_val]) {
								$Template_ID = $personalize_sc_template_id[$size_val][$colour_val];
							}
						}
					}
				}
			}

			if ($Template_ID != '') {
				$TemplatexML = php_xmlrpc_encode($Template_ID);
			}
			$username = $api_info->username;
			$api_key = $api_info->api_key;
			$apiUrl = $api_info->url;
			$image_url = $api_info->image_url;
			$window_type = $api_info->window_type;
			$background_color = $api_info->background_color;
			$opacity = $api_info->opacity;
			$margin = $api_info->margin;
			$serverURL = personalize_get_return_url();
			$successUrl = remove_query_arg(array('ps_product_id', 'a'), $serverURL);
			$successUrl = add_query_arg(array('add-to-cart' => $product_id, 'personalize' => $product_id, 'add' => $product_id), $successUrl);
			if (!isset($_REQUEST['q']) && $_REQUEST['q'] == '') {
				$successUrl = add_query_arg(array('q' => 1), $successUrl);
				$successUrl = add_query_arg(array('quantity' => 1), $successUrl);
			} else {
				$successUrl = add_query_arg(array('quantity' => $_REQUEST['q']), $successUrl);
			}
			//var_dump($successUrl); exit;
			$failUrl = remove_query_arg(array('add', 'q', 'ps_product_id', 'a'), $serverURL . $_SERVER['REQUEST_URI']);
			$failUrl = add_query_arg(array('fail' => '1'), $failUrl);
			$cancelUrl = remove_query_arg(array('add', 'q', 'ps_product_id', 'a'), $serverURL . $_SERVER['REQUEST_URI']);
			$cancelUrl = add_query_arg(array('cancel' => '1'), $cancelUrl);
			$client = new xmlrpc_client($apiUrl);
			$function = null;
			$user_id = 0;
			$user_id = get_current_user_id();
			$comment = personalize_get_comment_param($product_id);
			if ($user_id > '0') {
				$comment = '"User: "' . $user_id;
			}
			if ($_REQUEST['redesign'] == 'true') {
				$reorder_item_id = $_REQUEST['reorder_item_id'];
				$sessionKey = personalize_get_order_item_session_key($reorder_item_id);
				$_SESSION['sessionkey'] = $sessionKey;
				$function = new xmlrpcmsg('resumePersonalization', array(
					php_xmlrpc_encode($username),
					php_xmlrpc_encode($api_key),
					php_xmlrpc_encode($sessionKey),
					php_xmlrpc_encode($templateId),
					php_xmlrpc_encode($successUrl),
					php_xmlrpc_encode($failUrl),
					php_xmlrpc_encode($cancelUrl),
					php_xmlrpc_encode($comment),
					$TemplatexML,
				));
			} else {
				$gsheet_param = personalize_get_gsheet_param($product_id);
				if ($gsheet_param) {
					$function = new xmlrpcmsg('beginPersonalizationWithExternalSource', array(
						php_xmlrpc_encode($username),
						php_xmlrpc_encode($api_key),
						php_xmlrpc_encode($templateId),
						php_xmlrpc_encode($successUrl),
						php_xmlrpc_encode($failUrl),
						php_xmlrpc_encode($cancelUrl),
						php_xmlrpc_encode($comment),
						php_xmlrpc_encode($localLang[0]),
						$TemplatexML,
						php_xmlrpc_encode($gsheet_param)
					));
				} else {
					$function = new xmlrpcmsg('beginPersonalization', array(
						php_xmlrpc_encode($username),
						php_xmlrpc_encode($api_key),
						php_xmlrpc_encode($templateId),
						php_xmlrpc_encode($successUrl),
						php_xmlrpc_encode($failUrl),
						php_xmlrpc_encode($cancelUrl),
						php_xmlrpc_encode($comment),
						php_xmlrpc_encode($localLang[0]),
						$TemplatexML
					));
				}
				//echo '<pre>'; var_dump($function); echo '</pre>'; exit;
			}
			$response = $client->send($function);
			if (!$response->errno) {
				$sessionkey = $response->value()->arrayMem(0)->scalarval();
				$preview_url = $response->value()->arrayMem(1)->scalarval();
				$_SESSION['product_id'] = $_REQUEST['ps_product_id'];
				$_SESSION['sessionkey'] = $sessionkey;
				wp_redirect($preview_url);
				exit;
			} else {
				$error = str_replace(array('<','>'), '', $response->errstr);
				wp_die($error);
			}
		}
		if (isset($_REQUEST['r']) && $_REQUEST['r'] == 's') {
			$product_id = $_SESSION['product_id'];
			$session_key = $_SESSION['sessionkey'];
			unset($_SESSION['product_id']);
			unset($_SESSION['sessionkey']);
			unset($_SESSION['pro_']);
			add_filter('wp_head', 'close_div');
		}
		if (isset($_REQUEST['r']) && $_REQUEST['r'] == 'e') {
			personalize_update_design_image($_REQUEST['cart_item_key']);
			add_filter('wp_head', 'close_div');
		}
		if (isset($_REQUEST['cancel']) && $_REQUEST['cancel'] == '1') {
			add_filter('wp_head', 'close_div');
		}
		if (isset($_REQUEST['fail'])) {
			add_filter('wp_head', 'close_div');
		}
		$window_type = personalize_get_window_type();
		if ( is_plugin_active('woocommerce-product-addons/woocommerce-product-addons.php') && ($personalize == 'y') && ($window_type != '')) {  
			remove_all_filters( 'woocommerce_add_to_cart_validation' );
			add_filter( 'woocommerce_add_to_cart_validation', 'validate_add_cart_item_personal', 10, 3);
		}
	}
}

function personalize_get_comment_param($product_id) {
	global $current_user;
	$product_name = get_the_title($product_id);
	if (is_user_logged_in()) {
		$comment_param = __('User is logged in', 'personalize') . ':' . chr(10) . chr(13);
		$comment_param .= $current_user->user_login . ' + ';
	} else {
		$comment_param = __('User not logged in', 'personalize') . ':' . chr(10) . chr(13);
	}
	$comment_param .= $product_name;
	return urlencode($comment_param);
}

function personalize_get_gsheet_param($product_id) {
	global $current_user, $wpdb;
	$gsheet_param = false;
	$db_link_key = $_REQUEST['db_link_key'];
	$personalize_db_links = get_post_meta($product_id, '_personalize_db_links', true);
	if (is_array($personalize_db_links)) {
		foreach($personalize_db_links as $lkey => $personalize_db_link) {
			if ($personalize_db_link['active']) {
				// DIRECT MYSQL ACCESS
				if ($personalize_db_link['access'] == 'direct' && strlen($personalize_db_link['mysql']) && personalize_has_google_api()) {
					$mysql = $personalize_db_link['mysql'];
					switch ($mysql) {
						case 'wp2print-Houses':
							if (strlen($db_link_key)) {
								$spreadsheet_id = personalize_db_link_house_data($db_link_key, $personalize_db_link['photourl']);
								if ($spreadsheet_id) {
									$data = array();
									$data['name'] = 'house';
									$data['type'] = 'gsheet';
									$data['sheet_id'] = $spreadsheet_id;
									$data['key'] = $db_link_key;
									$gsheet_param[] = $data;
								}
							}
						break;
						case 'wp2print-Agents':
							if (strlen($personalize_db_link['field'])) {
								$user_agent_id = $wpdb->get_var(sprintf("SELECT meta_value FROM %usermeta WHERE meta_key = '%s' AND user_id = %s", $wpdb->prefix, $key_field, $current_user->ID));
								if ($user_agent_id) {
									$spreadsheet_id = personalize_db_link_agent_data($user_agent_id);
									if ($spreadsheet_id) {
										$data = array();
										$data['name'] = 'agent';
										$data['type'] = 'gsheet';
										$data['sheet_id'] = $spreadsheet_id;
										$data['key'] = $user_agent_id;
										$gsheet_param[] = $data;
									}
								}
						}
						break;
						default:
							if (strlen($db_link_key)) {
								$spreadsheet_id = personalize_db_link_external_data($personalize_db_link['mysql']);
								if ($spreadsheet_id) {
									$data = array();
									$data['name'] = $personalize_db_link['namespace'];
									$data['type'] = 'gsheet';
									$data['sheet_id'] = $spreadsheet_id;
									$data['key'] = $db_link_key;
									$gsheet_param[] = $data;
								}
							}
						break;
					}
				// REGISTERED DATABASE ACCESS
				} else if ($personalize_db_link['access'] == 'registered' && strlen($personalize_db_link['lookup'])) {
					$key = $db_link_key;
					if ($personalize_db_link['source'] == 'account') {
						$key = $wpdb->get_var(sprintf("SELECT meta_value FROM %susermeta WHERE meta_key = '%s' AND user_id = %s", $wpdb->prefix, $personalize_db_link['field'], $current_user->ID));
					}
					if (strlen($key)) {
						$data = array();
						$data['type'] = 'gsheet_registration';
						$data['lookup_id'] = $personalize_db_link['lookup'];
						$data['key'] = $key;
						$gsheet_param[] = $data;
					}
				}
			}
		}
	}
	return $gsheet_param;
}

function personalize_has_google_api() {
	if (class_exists('GoogleApiClient')) {
		return true;
	}
	return false;
}

function personalize_gsheet_clear_val($val) {
	$val = strip_tags($val);
	$val = trim(str_replace(array(chr(10),chr(13)), ' ', $val));
	return (string)$val;
}

function personalize_db_link_house_data($db_link_key, $houses_photos_url) {
	$hdb = new ExternalDB('wp2print-Houses');
	if ($hdb->is_sucess()) {
		$house_info = $hdb->get_row(sprintf("SELECT * FROM wp_rps_property WHERE DdfListingID = '%s'", $db_link_key));
		if (!$house_info) {
			$house_info = $hdb->get_row(sprintf("SELECT * FROM wp_rps_property WHERE ListingID = %s", $db_link_key));
		}
		if ($house_info) {
			$ListingID = $house_info->ListingID;
			$house_info = get_object_vars($house_info);
			$house_fields = array('ListingID');
			foreach($house_info as $fkey => $fval) {
				if ($fkey != 'ListingID' && $fkey != 'Rooms') {
					$house_fields[] = $fkey;
				}
			}
			$house_fields[] = 'Rooms';
			$house_photos = $hdb->get_results(sprintf("SELECT Photos FROM wp_rps_property_photos WHERE ListingID = %s", $ListingID));
			$house_rooms = $hdb->get_results(sprintf("SELECT * FROM wp_rps_property_rooms WHERE ListingID = %s", $ListingID));

			$googleapi = new GoogleApiClient();
			if ($googleapi->is_sucess()) {
				$attr = array(
					'name' => 'House Sheet - '.$db_link_key,
					'sheets' => array(
						1001 => array('name' => 'Data', 'count' => count($house_fields)),
						1002 => array('name' => 'Photos', 'count' => 3)
					)
				);
				$spreadsheet_id = $googleapi->create_spreadsheet($attr);
				if ($spreadsheet_id) {
					$house_data_fields = array($db_link_key);
					foreach($house_fields as $house_field) {
						if ($house_field != 'ListingID' && $house_field != 'Rooms') {
							$house_data_fields[] = personalize_gsheet_clear_val($house_info[$house_field]);
						}
					}
					if ($house_rooms) {
						$rooms = array();
						foreach($house_rooms as $house_room) {
							$rooms[] = $house_room->Type.'	'.$house_room->Level.'	'.$house_room->Dimension;
						}
						$house_data_fields[] = implode("\r\n", $rooms);
					} else {
						$house_data_fields[] = '';
					}

					$sheet_data_fields = array();
					$sheet_data_fields[] = $house_fields;
					$sheet_data_fields[] = $house_data_fields;
					$googleapi->add_spreadsheet_rows($sheet_data_fields, 1001);

					if ($house_photos) {
						$houses_photos_url = str_replace('[listing_id]', $ListingID, $houses_photos_url);
						$sheet_photos_fields = array();
						$sheet_photos_fields[] = array('ListingID', 'LowResolution', 'HighResolution');
						foreach($house_photos as $house_photo) {
							$photos = json_decode($house_photo->Photos);
							$low_photo = $houses_photos_url . $photos->Photo->filename;
							$high_photo = $houses_photos_url . $photos->LargePhoto->filename;
							$sheet_photos_fields[] = array($db_link_key, $low_photo, $high_photo);
						}
						$googleapi->add_spreadsheet_rows($sheet_photos_fields, 1002);
					}

					return $spreadsheet_id;
				}
			}
		}
	}
	return false;
}

function personalize_db_link_agent_data($user_agent_id) {
	$adb = new ExternalDB('wp2print-Agents');
	if ($adb->is_sucess()) {
		$agent_info = $adb->get_row(sprintf("SELECT * FROM agents WHERE AgentID = %s", $user_agent_id));
		if ($agent_info) {
			$googleapi = new GoogleApiClient();
			if ($googleapi->is_sucess()) {
				$attr = array(
					'name' => 'Agent Sheet - '.$user_agent_id,
					'sheets' => array(
						1001 => array('name' => 'Data', 'count' => 5),
						1002 => array('name' => 'Photos', 'count' => 3)
					)
				);
				$spreadsheet_id = $googleapi->create_spreadsheet($attr);
				if ($spreadsheet_id) {
					$sheet_text_fields = array();
					$sheet_text_fields[] = array('ID', 'Firstname', 'Lastname', 'Phone', 'Email');
					$sheet_text_fields[] = array($agent_info->ID, $agent_info->Firstname, $agent_info->Lastname, $agent_info->Phone, $agent_info->Email);

					$sheet_photos_fields = array();
					$sheet_photos_fields[] = array('ID', 'LowResolution', 'HighResolution');
					$sheet_photos_fields[] = array($agent_info->ID, $agent_info->LowResolution, $agent_info->HighResolution);

					$googleapi->add_spreadsheet_rows($sheet_text_fields, 1001);
					$googleapi->add_spreadsheet_rows($sheet_photos_fields, 1002);

					return $spreadsheet_id;
				}
			}
		}
	}
	return false;
}

function personalize_db_link_external_data($mysql) {
	global $EXTERNAL_MYSQL_DATABASES;
	if (strlen($EXTERNAL_MYSQL_DATABASES[$mysql]['sql'])) {
		$edb = new ExternalDB($mysql);
		if ($edb->is_sucess()) {
			$edata_rows = $edb->get_results($EXTERNAL_MYSQL_DATABASES[$mysql]['sql']);
			if ($edata_rows) {
				$sql_fields = array();
				$edata_array = get_object_vars($edata_rows[0]);
				foreach($edata_array as $fkey => $fval) {
					$sql_fields[] = $fkey;
				}

				$googleapi = new GoogleApiClient();
				if ($googleapi->is_sucess()) {
					$attr = array(
						'name' => 'Data Sheet - '.date('YmdHis'),
						'sheets' => array(
							1001 => array('name' => 'Data', 'count' => count($sql_fields))
						)
					);
					$spreadsheet_id = $googleapi->create_spreadsheet($attr);
					if ($spreadsheet_id) {
						
						$sheet_data_fields = array();
						$sheet_data_fields[] = $sql_fields;
						foreach($edata_rows as $edata_row) {
							$sql_values = array();
							foreach($sql_fields as $sql_field) {
								$sql_values[] = $edata_row->$sql_field;
							}
							$sheet_data_fields[] = $sql_values;
						}
						$googleapi->add_spreadsheet_rows($sheet_data_fields, 1001);

						return $spreadsheet_id;
					}
				}
			}
		}
	}
	return false;
}

function personalize_db_link_has_field($personalize_db_link) {
	if (($personalize_db_link['access'] == 'direct' && $personalize_db_link['mysql'] != '' && $personalize_db_link['mysql'] != 'wp2print-Agents') || ($personalize_db_link['access'] == 'registered' && $personalize_db_link['source'] == 'collect')) {
		return true;
	}
	return false;
}

add_action('get_response_from_api', 'get_response_from_api', 1, 2);
function get_response_from_api($sessionkey) {
	global $wpdb, $personalize_settings, $api_info;
	$pdf_urls = array();
	$img_urls = array();
	if ($personalize_settings && strlen($sessionkey)) {
		$apiUrl = $api_info->url;
		$client = new xmlrpc_client($apiUrl);
		$function = new xmlrpcmsg('getPreviewMulti', array(
			php_xmlrpc_encode($sessionkey)
		));
		$response = $client->send($function);
		if ($response->value()) {
			for ($v = 0; $v < $response->value()->arraySize(); $v++) {
				$response_value = $response->value()->arrayMem($v);

				$preview_urls = $response_value->structMem('preview_url');
				for ($i = 0; $i < $preview_urls->arraySize(); $i++) {
					$itemp = $preview_urls->arrayMem($i)->scalarval();
					$img_urls[] = $itemp[1]->scalarval();
				}
				$pdf_urls[] = $response_value->structMem('pdf_url')->scalarval();
			}
		}
	}
	return array('pdf_urls' => $pdf_urls, 'img_urls' => $img_urls);
}

add_action('revise_api_content', 'revise_api_content', 1, 3);
function revise_api_content($product_id, $variation_id, $cart_item_key) {
    global $wpdb, $personalize_settings, $api_info;
	if ($personalize_settings) {
		$sessionKey = personalize_get_session_key($cart_item_key);
		$apiUrl = $api_info->url;
		$username = $api_info->username;
		$api_key = $api_info->api_key;
		$client = new xmlrpc_client($apiUrl);
		$serverURL = personalize_get_return_url();
		$successUrl1 = remove_query_arg(array('re_edit'), $serverURL);
		$successUrl = add_query_arg(array('r' => 'e'), $successUrl1);
		$failUrl = add_query_arg(array('fail' => '1'), $successUrl1);
		$cancelUrl = add_query_arg(array('cancel' => '1'), $successUrl1);
		$user_id = 0;
		$user_id = get_current_user_id();
		$comment = '';
		if ($user_id > '0') {
			$comment = '"User: "' . $user_id;
		}
		$templateId = get_post_meta($product_id, 'a_product_id', true);
		$function = new xmlrpcmsg('resumePersonalization', array(
			php_xmlrpc_encode($username),
			php_xmlrpc_encode($api_key),
			php_xmlrpc_encode($sessionKey),
			php_xmlrpc_encode($templateId),
			php_xmlrpc_encode($successUrl),
			php_xmlrpc_encode($failUrl),
			php_xmlrpc_encode($cancelUrl),
			php_xmlrpc_encode($comment),
			$TemplatexML,
		));
		$response = $client->send($function);
		$sessionkey = $response->value()->arrayMem(0)->scalarval();
		$preview_url = $response->value()->arrayMem(1)->scalarval();
		if (isset($_REQUEST['variation_id']) && $_REQUEST['variation_id'] != '') {
			$_SESSION['pro_' . $product_id . '_' . $_REQUEST['variation_id'] . '_' . $_REQUEST['cart_item_key']] = $sessionkey;
		} else {
			$_SESSION['pro_' . $product_id . '_' . $_REQUEST['cart_item_key']] = $sessionkey;
		}
		$_SESSION['sessionkey'] = $sessionkey;
		wp_redirect($preview_url);
		exit;
	}
}

/* If productID or TemplateID does not exist on the Designer server, trap error. Display error message and write error message to log. */
//add_action('save_post', 'CheckTemplateID');
function CheckTemplateID() {
    global $wpdb, $personalize_settings, $api_info;
    if ($_POST['post_type'] == 'product') {
		if ($personalize_settings) {
			$postID = $_POST['post_ID'];
			$locale = apply_filters( 'plugin_locale', get_locale(), 'personalize' );
			$localLang = explode("_", $locale);
			$apiUrl = $api_info->url;
			$username = $api_info->username;
			$api_key = $api_info->api_key;
			$ProductID = get_post_meta($postID, 'a_product_id', true);
			$TemplateID = get_post_meta($postID, 'a_template_id', true);
			if ($TemplateID != '') {
				$TemplatexML = php_xmlrpc_encode($TemplateID);
			}
			$IsPersonalize = get_post_meta($postID, 'personalize', true);
			$client = new xmlrpc_client($apiUrl);
			$serverURL = serverURL();
			$function = null;
			if ($IsPersonalize == 'y') {
				$function = new xmlrpcmsg('beginPersonalization', array(
					php_xmlrpc_encode($username),
					php_xmlrpc_encode($api_key),
					php_xmlrpc_encode($ProductID),
					php_xmlrpc_encode(''),
					php_xmlrpc_encode(''),
					php_xmlrpc_encode(''),
					php_xmlrpc_encode(''),
					php_xmlrpc_encode($localLang[0]),
					$TemplatexML
				));
				$response = $client->send($function);
				$Error = '';
				if ($response->errno == '1000') {
					$Error = 'Product ID is invalid!';
				}
				if ($response->errno == '4') {
					$Error = 'Invalid Product ID or Template ID';
				}
				if ($response->errno == '4' || $response->errno == '1000') {
					update_option('my_admin_errors', $Error);
					error_log($Error, 0);
				}
			}
		}
	}
}

add_action('init', 'personalize_download_init');
function personalize_download_init() {
	if (isset($_GET['personalize_download']) && $_GET['personalize_download'] == 'true') {
		$dfile = $_GET['dfile'];
		$dfile = str_replace('https:', 'http:', $dfile);
		$file_content = file_get_contents($dfile);
		header("Content-Type: application/zip");
		header("Content-Disposition: attachment; filename=".basename($dfile));
		header("Content-Length: ".strlen($file_content));
		echo($file_content);
		exit;
	}
}
