<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package   Flow_Flow
 * @author    Looks Awesome <email@looks-awesome.com>

 * @link      http://looks-awesome.com
 * @copyright 2014 Looks Awesome
 */

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

if ( is_multisite() ) {

//	$blogs = $wpdb->get_results( "SELECT blog_id FROM {$wpdb->blogs}", ARRAY_A );
//	delete_transients();
//	delete_options();
//	delete_custom_file_directory();
	if ( $blogs ) {
	 	foreach ( $blogs as $blog ) {
			//switch_to_blog( $blog['blog_id'] );
			//delete_options();
			//delete_custom_file_directory();
			//clean_db();
			//restore_current_blog();
		}
	}

} else {
	//delete_transients();
	//delete_options();
	//delete_custom_file_directory();
	//clean_db();
}

function delete_options() {
//	$option_name = 'flow_flow_options';
//	$fb_auth_option_name = 'flow_flow_fb_auth_options';
//	delete_option($option_name);
//	delete_option($fb_auth_option_name);
}

function delete_transients() {
	//delete_transient( 'TRANSIENT_NAME' );
}

function delete_custom_file_directory() {
	//info: remove custom file directory for main site
	//	$upload_dir = wp_upload_dir();
	//	$directory = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . "CUSTOM_DIRECTORY_NAME" . DIRECTORY_SEPARATOR;
	//	if (is_dir($directory)) {
	//		foreach(glob($directory.'*.*') as $v){
	//			unlink($v);
	//		}
	//		rmdir($directory);
	//	}
}

function clean_db() {
	global $wpdb;
	$prefix = $wpdb->prefix;
	$wpdb->query("DELETE FROM `{$prefix}options` WHERE `option_name` LIKE '%_flow_flow_%'");
}
