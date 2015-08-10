<?php if ( ! defined( 'WPINC' ) ) die;
/**
 * Flow-Flow.
 *
 * @package   FlowFlow
 * @author    Looks Awesome <email@looks-awesome.com>

 * @link      http://looks-awesome.com
 * @copyright 2014 Looks Awesome
 */
require_once(AS_PLUGIN_DIR . 'includes/social/FFFeedUtils.php');

class FFFacebookCacheManager {
	private static $postfix_at = '_facebook_access_token';
	private static $postfix_at_expires = '_facebook_access_token_expires';
	/** @var FFFacebookCacheManager */
	private static $instance = null;

	/**
	 * @return void
	 */
	public static function clean(){
		delete_transient(FlowFlow::$PLUGIN_SLUG_DOWN . self::$postfix_at);
		delete_transient(FlowFlow::$PLUGIN_SLUG_DOWN . self::$postfix_at_expires);
	}

	/**
	 * @return FFFacebookCacheManager
	 */
	public static function get() {
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	private $access_token = null;

	private function __construct() {
	}

	/**
	 * @return string|bool
	 */
	public function getAccessToken(){
		if ($this->access_token != null) return $this->access_token;

		if (false !== ($access_token_transient = get_transient(FlowFlow::$PLUGIN_SLUG_DOWN . self::$postfix_at))){
			$access_token = $access_token_transient;
		}
		else{
			$auth = FlowFlow::get_instance()->get_auth_options();
			$access_token = $auth['facebook_access_token'];
			if(!isset($access_token) || empty($access_token)){
				return false;
			}
		}
		if (false === get_transient(FlowFlow::$PLUGIN_SLUG_DOWN . self::$postfix_at_expires)){
			$auth = FlowFlow::get_instance()->get_auth_options();
			$facebookAppId = $auth['facebook_app_id'];
			$facebookAppSecret = $auth['facebook_app_secret'];
			$this->extendAccessToken($access_token, $facebookAppId, $facebookAppSecret);
		}
		$this->access_token = $access_token;
		return $access_token;
	}

	private function extendAccessToken($access_token, $facebookAppId, $facebookAppSecret){
		$token_url="https://graph.facebook.com/oauth/access_token?client_id={$facebookAppId}&client_secret={$facebookAppSecret}&grant_type=fb_exchange_token&fb_exchange_token={$access_token}";
		$response = FFFeedUtils::getFeedData($token_url);
		$response = (string)$response['response'];
		$response = explode ('=',$response);
		if (sizeof($response) > 2) $expires = (int)$response[2];
		$access_token = explode ('&',$response[1]);
		set_transient(FlowFlow::$PLUGIN_SLUG_DOWN . self::$postfix_at, $access_token[0]);
		set_transient(FlowFlow::$PLUGIN_SLUG_DOWN . self::$postfix_at_expires, isset($expires) ? $expires : 2629743, round($expires/2));
	}
}