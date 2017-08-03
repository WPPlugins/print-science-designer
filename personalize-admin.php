<?php
if (isset($_POST) && $_POST['personalize_settings'] == 'submit') {
	$locale = apply_filters( 'plugin_locale', get_locale(), 'personalize' );
	$localLang = explode("_", $locale);
	if (!$api_info) {
		$insert = array();
		$insert['id'] = 1;
		$insert['username'] = $_POST['username'];
		$insert['api_key'] = $_POST['api_key'];
		$insert['version'] = $_POST['version'];
		$insert['url'] = $_POST['url'];
		$insert['image_url'] = $_POST['image_url'];
		$insert['window_type'] = $_POST['window_type'];
		$insert['background_color'] = $_POST['background_color'];
		$insert['opacity'] = $_POST['opacity'];
		$insert['margin'] = $_POST['margin'];
		$insert['show_pdf'] = (int)$_POST['show_pdf'];
		$wpdb->insert(API_INFO_TABLE, $insert);
	} else {
		$update = array();
		$update['username'] = $_POST['username'];
		$update['api_key'] = $_POST['api_key'];
		$update['version'] = $_POST['version'];
		$update['url'] = $_POST['url'];
		$update['image_url'] = $_POST['image_url'];
		$update['window_type'] = $_POST['window_type'];
		$update['background_color'] = $_POST['background_color'];
		$update['opacity'] = $_POST['opacity'];
		$update['margin'] = $_POST['margin'];
		$update['show_pdf'] = (int)$_POST['show_pdf'];
		$wpdb->update(API_INFO_TABLE, $update, array('id' => 1));
	}

	// CHECK FOR API CONNECTION
	if (isset($_POST['test_connection'])) {
		$url = $_POST['url'];
		$username = $_POST['username'];
		$api_key = $_POST['api_key'];
		$client = new xmlrpc_client($url);
		$function = new xmlrpcmsg('beginPersonalization', array(
			php_xmlrpc_encode($username),
			php_xmlrpc_encode($api_key),
			php_xmlrpc_encode(''),
			php_xmlrpc_encode(''),
			php_xmlrpc_encode(''),
			php_xmlrpc_encode(''),
			php_xmlrpc_encode(''),
			php_xmlrpc_encode($localLang[0])
		));
		$response = $client->send($function);
		$APIErrorCode = $response->errno;
		$AuthenticationError = false;
		$Authenticationsucc = false;
		if ($APIErrorCode == '3' || $APIErrorCode == '1') {
			$AuthenticationError = true;
		} else {
			$Authenticationsucc = true;
		}
	}
}
$arr_api_info = $wpdb->get_row('SELECT * FROM ' . API_INFO_TABLE . ' where id = 1');
$username = $arr_api_info->username;
$api_key = $arr_api_info->api_key;
$version = $arr_api_info->version;
$url = $arr_api_info->url;
$image_url = $arr_api_info->image_url;
$window_type = $arr_api_info->window_type;
$background_color = $arr_api_info->background_color;
$opacity = $arr_api_info->opacity;
$margin = $arr_api_info->margin;
$show_pdf = $arr_api_info->show_pdf;
?>
<style>
	.api_form{
		width:100%;
	}
	.row{
		width:100%;
		float:left;
		margin: 5px 0;
	}
	.row .tit{
		font-size:14px;
		margin: 0 0 0 18%;
	}
	.row label{
		float: left;
		width: 18%;
		line-height:15px;
	}
	.row input,.row select{
		width:35%;
		margin:0px;
		padding:2px 3px;
	}	
	.row .saveb{
		margin: 0 0 0 18%;
		width: 10%;		  
	}
	.row .testc{
		margin: 0 0 0 18%;
		width: 12%;		  
	}
	.row .jsonf{
		background:#FFF;
		padding:2px 4px;
		border:1px solid #C1C1C1;
		float:left;
		margin-top:3px;
	}
	.row-sep{
		width:100%;
		float:left;
		padding:10px 0 5px;
	}
	.row-sep hr{
		width:35%;
		border-top:1px solid #C1C1C1;
		margin: 0 0 0 18%;
	}
	.auth_error {color:red; font-size:13px; float:left; background: #FFFFCC; padding: 3px 6px; margin:5px 0 5px 18%;}
	.auth_succ {color:green; font-size:13px; float:left; background: #FFFFCC; padding: 3px 6px; margin:5px 0 5px 18%;}
</style>
<link rel="stylesheet" media="screen" type="text/css" href="<?php echo plugins_url(); ?>/print-science-designer/css/colorpicker.css" />
<script type="text/javascript" src="<?php echo plugins_url(); ?>/print-science-designer/js/colorpicker.js"></script>
<script type="text/javascript" src="<?php echo plugins_url(); ?>/print-science-designer/js/eye.js"></script>
<script type="text/javascript" src="<?php echo plugins_url(); ?>/print-science-designer/js/layout.js?ver=1.0.2"></script>
<div class="api_form">
	<h2><?php echo __('Product Personalization Settings', 'personalize'); ?></h2>
	<form method="POST" enctype="multipart/form-data">
		<div class="row"><strong class="tit"><?php echo __('Designer API Settings', 'personalize'); ?></strong></div>
		<?php
		if ($AuthenticationError) {
			echo '<span class="auth_error">'.__('Your API credentials are not correct. Please enter the correct API username and key.', 'personalize').'</span>';
		}
		if ($Authenticationsucc) {
			echo '<span class="auth_succ">'.__('Your API credentials are working fine.', 'personalize').'</span>';
		}
		?>
		<div class="row"><label><?php echo __('API Username', 'personalize'); ?></label><input name="username" id="username" value="<?php echo $username; ?>"/></div>
		<div class="row"><label><?php echo __('API Key', 'personalize'); ?></label><input name="api_key" id="api_key" value="<?php echo $api_key; ?>"/></div>
		<div class="row"><label><?php _e('API URL', 'personalize'); ?> </label><input name="url" id="url" value="<?php echo $url; ?>"/></div>
		<div class="row"><label><?php echo __('API Image URL', 'personalize'); ?></label><input name="image_url" id="image_url" value="<?php echo $image_url; ?>"/></div>
		<div class="row"><input class="testc" type="submit" name="test_connection" id="test_connection" value="<?php echo __('Test Connection', 'personalize'); ?>"/></div>

		<div class="row-sep"><hr /></div>

		<div class="row"><strong class="tit"><?php echo __('Style Settings', 'personalize'); ?></strong></div>
		<div class="row"><label><?php echo __('Window type for launch of Designer', 'personalize'); ?></label>
			<select name="window_type" id="window_type">
				<?php
				$arr_types = array('New Window', 'Modal Pop-up window');
				foreach ($arr_types as $type) {
					$selected = '';
					if ($type == $window_type) {
						$selected = "selected";
					}
					?>  
					<option value="<?php echo $type; ?>" <?php echo $selected; ?>><?php echo $type; ?></option>
				<?php } ?>
			</select>	  
		</div>
		<div class="row"><label><?php echo __('Background color of margin surrounding the modal window', 'personalize'); ?></label><input name="background_color" id="colorpickerField1" value="<?php echo $background_color; ?>"/><span><?php echo __('* Specify the background color', 'personalize'); ?> </span></div>
		<div class="row"><label><?php echo __('Opacity of modal window', 'personalize'); ?> </label><input name="opacity" id="opacity" value="<?php echo $opacity; ?>"/><span>*(%)</span></div>
		<div class="row"><label> <?php echo __('Width of margin surrounding the modal window', 'personalize'); ?></label><input name="margin" id="margin" value="<?php echo $margin; ?>"/><span> *(px)</span></div>

		<div class="row-sep"><hr /></div>

		<div class="row"><label><?php echo __('Show PDF link in order email', 'personalize'); ?></label><input type="checkbox" name="show_pdf" value="1" style="width:auto;"<?php if ($show_pdf){ echo ' CHECKED'; } ?>></div>

		<div class="row-sep"><hr /></div>

		<div class="row"><input class="saveb" type="submit" name="save" id="save" value="<?php echo __('Save Settings', 'personalize'); ?>"/></div>
		<input type="hidden" name="version" value="4.0.0">
		<input type="hidden" name="personalize_settings" value="submit">
	</form>
</div>
