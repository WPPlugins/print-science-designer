<?php
function ps_install() {
    global $wpdb;

	$wpdb->query("CREATE TABLE IF NOT EXISTS " . API_INFO_TABLE . " (
		id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
		username VARCHAR( 255 ) NOT NULL,
		api_key VARCHAR( 255 ) NOT NULL,
		version VARCHAR( 255 ) NOT NULL,
		url VARCHAR( 255 ) NOT NULL,
		image_url VARCHAR( 255 ) NOT NULL,
		window_type VARCHAR( 255 ) NOT NULL,
		background_color VARCHAR( 255 ) NOT NULL,
		opacity VARCHAR( 255 ) NOT NULL,
		margin VARCHAR( 255 ) NOT NULL,
		show_pdf TINYINT NULL DEFAULT '0'
	)");
            
    $wpdb->query("CREATE TABLE IF NOT EXISTS " . CART_DATA_TABLE . " (
		id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
		uniqueID VARCHAR( 255 ) NULL,
		printImage TEXT NULL,
		editURL VARCHAR( 255 ) NULL,
		cart_item_key VARCHAR(100) NULL,
		sessionKey VARCHAR(100) NULL
	)");
	$wpdb->query("ALTER TABLE " . API_INFO_TABLE . " ADD show_pdf TINYINT NULL DEFAULT '0'");
	$wpdb->query("ALTER TABLE " . CART_DATA_TABLE . " CHANGE printImage printImage TEXT NULL DEFAULT NULL");
	$wpdb->query("ALTER TABLE " . CART_DATA_TABLE . " ADD cart_item_key VARCHAR(100) NULL");
	$wpdb->query("ALTER TABLE " . CART_DATA_TABLE . " ADD sessionKey VARCHAR(100) NULL");

	// copy translation files
	$plugins_lang_folder = $_SERVER['DOCUMENT_ROOT'].'/wp-content/languages/plugins';
	if (is_dir($plugins_lang_folder)) {
		$langpofiles = glob(dirname(__FILE__).'/language/*.po');
		if (count($langpofiles)) {
			foreach($langpofiles as $langpofile) {
				@copy($langpofile, $plugins_lang_folder.'/'.basename($langpofile));
			}
		}
		$langmofiles = glob(dirname(__FILE__).'/language/*.mo');
		if (count($langmofiles)) {
			foreach($langmofiles as $langmofile) {
				@copy($langmofile, $plugins_lang_folder.'/'.basename($langmofile));
			}
		}
	}
}
?>