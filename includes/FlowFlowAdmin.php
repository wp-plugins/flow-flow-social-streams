<?php if ( ! defined( 'WPINC' ) ) die;
/**
 * Flow-Flow.
 *
 * Plugin class. This class should ideally be used to work with the
 * administrative side of the WordPress site.
 *
 * If you're interested in introducing public-facing
 * functionality, then refer to `FlowFlow.php`
 *
 * @package   FlowFlowAdmin
 * @author    Looks Awesome <email@looks-awesome.com>
 * @link      http://looks-awesome.com
 * @copyright 2014 Looks Awesome
 */
class FlowFlowAdmin {
	protected static $instance = null;
	protected $plugin_screen_hook_suffix = null;

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {
		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		
		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( realpath( dirname( __FILE__ ) ) ) . FlowFlow::$PLUGIN_SLUG . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {
		// for menu icon
		wp_enqueue_style( FlowFlow::$PLUGIN_SLUG .'-admin-icon', FlowFlow::get_plugin_directory() . 'css/admin-icon.css', array(), FlowFlow::VERSION );

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_style(FlowFlow::$PLUGIN_SLUG .'-admin-styles', FlowFlow::get_plugin_directory() . 'css/admin.css', array(), FlowFlow::VERSION );
			wp_enqueue_style(FlowFlow::$PLUGIN_SLUG .'-colorpickersliders', FlowFlow::get_plugin_directory() . 'css/jquery-colorpickersliders.css', array(), FlowFlow::VERSION);

			// Load web font
			wp_register_style('lato-font', '//fonts.googleapis.com/css?family=Lato:300,400' );
			wp_enqueue_style( 'lato-font' );
		}
	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {
		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_script( FlowFlow::$PLUGIN_SLUG . '-admin-script', FlowFlow::get_plugin_directory() . 'js/admin.js', array( 'jquery' ), FlowFlow::VERSION );
			wp_enqueue_script( FlowFlow::$PLUGIN_SLUG . '-zeroclipboard', FlowFlow::get_plugin_directory() . 'js/zeroclipboard/ZeroClipboard.min.js', array( 'jquery' ), FlowFlow::VERSION );
			wp_enqueue_script( FlowFlow::$PLUGIN_SLUG . '-tinycolor', FlowFlow::get_plugin_directory() . 'js/tinycolor.js', array( 'jquery' ), FlowFlow::VERSION );
			wp_enqueue_script( FlowFlow::$PLUGIN_SLUG . '-colorpickersliders', FlowFlow::get_plugin_directory() . 'js/jquery.colorpickersliders.js', array( 'jquery' ), FlowFlow::VERSION );
		}
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		/*
		 * Add a settings page for this plugin to the Settings menu.
		 *
		 * NOTE:  Alternative menu locations are available via WordPress administration menu functions.
		 *
		 *        Administration Menus: http://codex.wordpress.org/Administration_Menus
		 *
		 */
		$this->plugin_screen_hook_suffix = add_menu_page(
			'Flow-Flow — Social Streams Plugin',
			'Flow-Flow',
			'manage_options',
			FlowFlow::$PLUGIN_SLUG,
			array( $this, 'display_plugin_admin_page' ),
			'none'
		);

		//add_submenu_page( FlowFlow::$PLUGIN_SLUG, 'Back up', 'Back up', 'manage_options', FlowFlow::$PLUGIN_SLUG, array( $this, 'display_plugin_backup_page' ));

	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		include_once(AS_PLUGIN_DIR . 'views/admin.php');
	}

	public function display_plugin_backup_page() {
		include_once(AS_PLUGIN_DIR . 'views/backup.php');
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {

		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'options-general.php?page=' . FlowFlow::$PLUGIN_SLUG ) . '">' . 'Settings' . '</a>',
				'docs' => '<a target="_blank" href="http://flow.looks-awesome.com/docs/Getting_Started">' . 'Documentation' . '</a>',
				'upgrade' => '<a target="_blank" href="http://www.social-streams.com/#pricing">' . 'Upgrade to PRO' . '</a>'
			),
			$links
		);
	}

	public function debug_to_console($data) {
		if(is_array($data) || is_object($data))
		{
			echo("<script>console.log('PHP: ".json_encode($data)."');</script>");
		} else {
			echo("<script>console.log('PHP: ".$data."');</script>");
		}
	}
}
