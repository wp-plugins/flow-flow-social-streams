<?php
/**
 * @package   Flow_Flow
 * @author    Looks Awesome <hello@looks-awesome.com>

 * @link      http://looks-awesome.com
 * @copyright 2014-2015 Looks Awesome
 *
 * @wordpress-plugin
 * Plugin Name:       Flow-Flow Social Streams (Free)
 * Plugin URI:        flow.looks-awesome.com
 * Description:       Awesome social streams on your site
 * Version:           1.0.4
 * Author:            Looks Awesome
 * Author URI:        looks-awesome.com
 * Text Domain:       flow-flow
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
if ( ! defined( 'AS_PLUGIN_DIR' ) ) {
	define( 'AS_PLUGIN_DIR', dirname(__FILE__).'/' );
}

function custom_cron_intervals_register(){
    $intervals['minute'] = array(
        'interval' => MINUTE_IN_SECONDS,
        'display' => 'Once Minute'
    );
    return $intervals;
}

require_once( plugin_dir_path( __FILE__ ) . 'includes/FlowFlow.php' );

add_filter('cron_schedules', 'custom_cron_intervals_register');

register_activation_hook( __FILE__, array( 'FlowFlow', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'FlowFlow', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'FlowFlow', 'get_instance' ) );

if ( is_admin() /*&& ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )*/ ) {
	require_once( plugin_dir_path( __FILE__ ) . 'includes/FlowFlowAdmin.php' );
 	add_action( 'plugins_loaded', array( 'FlowFlowAdmin', 'get_instance' ));
}