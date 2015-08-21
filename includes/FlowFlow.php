<?php if ( ! defined( 'WPINC' ) ) die;
/**
 * Flow-Flow
 *
 * Plugin class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 * If you're interested in introducing administrative or dashboard
 * functionality, then refer to `FlowFlowAdmin.php`
 *
 * @package   FlowFlow
 * @author    Looks Awesome <email@looks-awesome.com>

 * @link      http://looks-awesome.com
 * @copyright 2014 Looks Awesome
 */
require_once(AS_PLUGIN_DIR . 'includes/social/FFTwitter.php');
require_once(AS_PLUGIN_DIR . 'includes/social/FFFacebook.php');
require_once(AS_PLUGIN_DIR . 'includes/social/FFInstagram.php');
require_once(AS_PLUGIN_DIR . 'includes/social/FFPinterest.php');

require_once(AS_PLUGIN_DIR . 'includes/cache/FFCacheManager.php');
require_once(AS_PLUGIN_DIR . 'includes/cache/FFFacebookCacheManager.php');
require_once(AS_PLUGIN_DIR . 'includes/cache/FFImageSizeCacheManager.php');

require_once(AS_PLUGIN_DIR . 'includes/settings/FFStreamSettings.php');
require_once(AS_PLUGIN_DIR . 'includes/settings/FFGeneralSettings.php');

class FlowFlow {

    /**
     * Plugin version, used for cache-busting of style and script file references.
     *
     * @since   1.0.0
     *
     * @var     string
     */
    const VERSION = '1.0.4';

    /**
     *
     * Unique identifier for your plugin.
     *
     *
     * The variable name is used as the text domain when internationalizing strings
     * of text. Its value should match the Text Domain file header in the main
     * plugin file.
     *
     * @since    1.0.0
     *
     * @var      string
     */
    public static $PLUGIN_SLUG = 'flow-flow-social-streams';
    public static $PLUGIN_SLUG_DOWN = 'flow_flow_social_streams';

    protected static $instance = null;
    /** @return FlowFlow|null */
    public static function get_instance() {
        if ( null == self::$instance ) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /** @var FFCacheManager */
    private $cache;
    /** @var FFStreamSettings */
    private $settings;
    /** @var FFGeneralSettings */
    private $generalSettings;

    /**
     * Initialize the plugin by setting localization and loading public scripts
     * and styles.
     *
     * @since     1.0.0
     */
    private function __construct() {
        add_action( 'init', array($this, 'register_shortcodes'));
        add_action( 'init', array($this, 'load_plugin_textdomain'));

        // Using Settings API
        add_action( 'admin_init', array($this, 'register_settings') );
        add_action( 'updated_option', array($this, 'afterUpdateOption'));

        // Activate plugin when new blog is added
        add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

        // Load public-facing style sheet and JavaScript.
        // add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        add_action('wp_ajax_fetch_posts', array( $this, 'processAjaxRequest'));
        add_action('wp_ajax_nopriv_fetch_posts', array( $this, 'processAjaxRequest'));

        add_action('wp_ajax_load_cache', array( $this, 'processAjaxRequestBackground'));
        add_action('wp_ajax_nopriv_load_cache', array( $this, 'processAjaxRequestBackground'));

        add_action(self::$PLUGIN_SLUG_DOWN.'_load_cache', array($this, 'refreshCache'));

        self::create_lazy_load_cache_schedule();
    }

    /** $return FFGeneralSettings */
    public function getGeneralSettings(){
        return $this->generalSettings;
    }

    public function register_shortcodes()
    {
        add_shortcode('ff', array($this, 'renderShortCode'));
    }

    public function renderShortCode($attr, $text=null) {
        if ($this->prepareProcess()) {
            $stream = $this->generalSettings->getStreamById($attr['id']);
            if (isset($stream)) {
                $settings = new FFStreamSettings($stream);
                if ($settings->isPossibleToShow()){
                    ob_start();
                    include(AS_PLUGIN_DIR . 'views/public.php');
                    $output = ob_get_contents();
                    ob_get_clean();
                    return $output;
                }
            }
        }
    }

    public function processAjaxRequest() {
        if (isset($_REQUEST['stream-id']) && $this->prepareProcess()) {
            $stream = $this->generalSettings->getStreamById($_REQUEST['stream-id']);
            if (isset($stream)) {
                $disableCache = (bool) isset($_REQUEST['disable-cache']) ? $_REQUEST['disable-cache'] : false;
                echo $this->process(array($stream), $disableCache);
            }
        }
        die();
    }

    public function processAjaxRequestBackground() {
        if (isset($_REQUEST['stream_id']) && $this->prepareProcess(true)) {
            $stream = $this->generalSettings->getStreamById($_REQUEST['stream_id']);
            if (isset($stream)) $this->process(array($stream));
        }
    }

    public function processRequest(){
        if (isset($_REQUEST['stream-id']) && $this->prepareProcess()) {
            $stream = $this->generalSettings->getStreamById($_REQUEST['stream-id']);
            if (isset($stream)) {
                return $this->process(array($stream), isset($_REQUEST['disable-cache']));
            }
        }
        return '';
    }

    public function refreshCache() {
        if ($this->prepareProcess(true)) {
            foreach ( $this->generalSettings->getAllStreams() as $stream ) {
                try{
                    wp_remote_get( admin_url( 'admin-ajax.php' ) . "?action=load_cache&stream_id={$stream->id}", array( 'timeout' => 1 ) );
                }catch (Exception $e){
                    error_log($e->getMessage());
                }
            }
        }
    }

    private function prepareProcess($forceLoadCache = false) {
        $options = $this->get_options();
        $auth_options = $this->get_auth_options();
        if ($options['streams_count'] > 0) {
            $this->cache = new FFCacheManager($forceLoadCache);
            $this->generalSettings = new FFGeneralSettings($options, $auth_options);
            return true;
        }
        return false;
    }

    private function process($streams, $disableCache = false) {
        foreach ($streams as $stream) {
            try {
                $this->settings = new FFStreamSettings($stream);
                $this->cache->setStream($this->settings);
                $feeds = $this->feeds();
                $result = $this->cache->posts($feeds, $disableCache);
                $errors = $this->cache->errors();
                if (!$this->cache->forceLoadCache()){
                    $result = $this->uniformAllocation($result);
                    return $this->prepareResult($result, $errors);
                }
            } catch (Exception $e) {
                error_log($e->getTraceAsString());
            }
        }
    }

    private function feeds() {
        $result = array();
        $feeds = $this->settings->getAllFeeds();
        if (is_array($feeds)) {
            foreach ($this->settings->getAllFeeds() as $feed) {
                $clazz = new ReflectionClass( 'FF' . ucfirst($feed->type) );
                $instance = $clazz->newInstance();
                $instance->init($this->generalSettings, $this->settings, $feed);
                $result[] = $instance;
            }
        }
        return $result;
    }

    private function prepareResult($all, $errors) {
        $result = array();
        {
            $allCount = sizeof( $all );
            if ( $allCount > 0 ) {
                $count = (int) $this->settings->getCountOfPosts();
                $count = $count > $allCount ? $allCount : $count;
                usort( $all, array( $this, 'compareByTime' ) );
                $result = array_slice( $all, 0, $count );
            }
        }
        $response = array('id' => (int)$this->settings->getId(), 'items' => $result, 'errors' => $errors);
        return json_encode($response);
    }

    private function uniformAllocation($result){
        $tmp = array();
        if ($result) {
            $tmp = $result[0];
            for ($i = 1; $i < sizeof($result); $i++){
                $curr = $result[$i];
                for ($j = 0; $j < sizeof($curr); $j++){
                    $tmp[] = $curr[$j];
                }
            }
        }
        return $tmp;
    }

    private function compareByTime($a, $b) {
        $a_system_date = $a->system_timestamp;
        $b_system_date = $b->system_timestamp;
        return ($a_system_date == $b_system_date) ? 0 : ($a_system_date < $b_system_date) ? 1 : -1;
    }

    /**
     * Fired when the plugin is activated.
     *
     * @since    1.0.0
     *
     * @param    boolean    $network_wide    True if WPMU superadmin uses
     *                                       "Network Activate" action, false if
     *                                       WPMU is disabled or plugin is
     *                                       activated on an individual blog.
     */
    public static function activate( $network_wide ) {
        if ( function_exists( 'is_multisite' ) && is_multisite() ) {
            if ( $network_wide  ) {
                // Get all blog ids
                $blog_ids = self::get_blog_ids();
                foreach ( $blog_ids as $blog_id ) {
                    switch_to_blog( $blog_id );
                    self::single_activate();
                    restore_current_blog();
                }
            }
            else self::single_activate();
        }
        else self::single_activate();
    }

    /**
     * Fired when the plugin is deactivated.
     *
     * @since    1.0.0
     *
     * @param    boolean    $network_wide    True if WPMU superadmin uses
     *                                       "Network Deactivate" action, false if
     *                                       WPMU is disabled or plugin is
     *                                       deactivated on an individual blog.
     */
    public static function deactivate( $network_wide ) {
        if ( function_exists( 'is_multisite' ) && is_multisite() ) {
            if ( $network_wide ) {
                // Get all blog ids
                $blog_ids = self::get_blog_ids();
                foreach ( $blog_ids as $blog_id ) {
                    switch_to_blog( $blog_id );
                    self::single_deactivate();
                    restore_current_blog();
                }
            }
            else self::single_deactivate();
        }
        else self::single_deactivate();
    }

    /**
     * Fired when a new site is activated with a WPMU environment.
     *
     * @since    1.0.0
     *
     * @param    int    $blog_id    ID of the new blog.
     */
    public function activate_new_site( $blog_id ) {
        if ( 1 !== did_action( 'wpmu_new_blog' ) )  return;
        switch_to_blog( $blog_id );
        self::single_activate();
        restore_current_blog();
    }

    /**
     * Get all blog ids of blogs in the current network that are:
     * - not archived
     * - not spam
     * - not deleted
     *
     * @since    1.0.0
     *
     * @return   array|false    The blog ids, false if no matches.
     */
    private static function get_blog_ids() {
        global $wpdb;
        $sql = "SELECT blog_id FROM $wpdb->blogs WHERE archived = '0' AND spam = '0' AND deleted = '0'";
        return $wpdb->get_col( $sql );
    }

    /**
     * Fired for each blog when the plugin is activated.
     *
     * @since    1.0.0
     */
    private static function single_activate() {
    }

    private static function create_lazy_load_cache_schedule(){
        $timestamp = wp_next_scheduled( self::$PLUGIN_SLUG_DOWN.'_load_cache' );
        if( $timestamp == false ){
            wp_schedule_event( time(), 'minute', self::$PLUGIN_SLUG_DOWN.'_load_cache' );
        }
    }

    /**
     * Fired for each blog when the plugin is deactivated.
     *
     * @since    1.0.0
     */
    private static function single_deactivate() {
        wp_clear_scheduled_hook( self::$PLUGIN_SLUG_DOWN.'_load_cache' );
        FFCacheManager::clean(array('%'));
        FFImageSizeCacheManager::clean();
    }

    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {
        $domain = self::$PLUGIN_SLUG;
        $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

        load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
        load_plugin_textdomain( $domain, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );
    }

    /**
     * Register and enqueue public-facing style sheet.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
    }

    /**
     * Register and enqueues public-facing JavaScript files.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        $opts =  $this->get_options();
        $js_opts = array(
            'streams' => $opts['streams'],
            'open_in_new' => $opts['general-settings-open-links-in-new-window'],
            'filter_all' => __('All', self::$PLUGIN_SLUG),
            'filter_search' => __('Search', self::$PLUGIN_SLUG),
            'expand_text' => __('Expand', self::$PLUGIN_SLUG),
            'collapse_text' => __('Collapse', self::$PLUGIN_SLUG),
            'posted_on' => __('Posted on', self::$PLUGIN_SLUG),
            'show_more' => __('Show more', self::$PLUGIN_SLUG),
            'dates' => array(
                'Yesterday' => __('Yesterday', self::$PLUGIN_SLUG),
                's' => __('s', self::$PLUGIN_SLUG),
                'm' => __('m', self::$PLUGIN_SLUG),
                'h' => __('h', self::$PLUGIN_SLUG),
                'ago' => __('ago', self::$PLUGIN_SLUG),
                'months' => array(
                    __('Jan', self::$PLUGIN_SLUG), __('Feb', self::$PLUGIN_SLUG), __('March', self::$PLUGIN_SLUG),
                    __('April', self::$PLUGIN_SLUG), __('May', self::$PLUGIN_SLUG), __('June', self::$PLUGIN_SLUG),
                    __('July', self::$PLUGIN_SLUG), __('Aug', self::$PLUGIN_SLUG), __('Sept', self::$PLUGIN_SLUG),
                    __('Oct', self::$PLUGIN_SLUG), __('Nov', self::$PLUGIN_SLUG), __('Dec', self::$PLUGIN_SLUG)
                ),
            ),
            'lightbox_navigate' => __('Navigate with arrow keys', self::$PLUGIN_SLUG),
            'server_time' => time(),
            'isAdmin' => current_user_can( 'manage_options' ),
            'forceHTTPS' => $opts['general-settings-https'],
            'isLog' => isset($_REQUEST['fflog']) && $_REQUEST['fflog'] == 1,
            'plugin_ver' => '1.3.13'
        );

        wp_enqueue_script(self::$PLUGIN_SLUG . '-plugin-script', self::get_plugin_directory() . 'js/require-utils.js', array('jquery'), self::VERSION);
        wp_localize_script(self::$PLUGIN_SLUG . '-plugin-script', 'FlowFlowOpts', $js_opts);
    }

    public function register_settings() {
        $option_name = 'flow_flow_options';
        $fb_auth_option_name = 'flow_flow_fb_auth_options';
        register_setting( 'ff_opts', $option_name, array($this, 'validate_options'));
        register_setting( 'ff_opts', $fb_auth_option_name, array($this, 'validate_options'));

        // Register any setting here before using
        add_settings_field('streams', '', '', $option_name, '');
        add_settings_field('streams_count', '', '', $option_name, '');
        add_settings_field('last_submit', '', '', $option_name, '');
        add_settings_field('feeds_changed', '', '', $option_name, '');
        add_settings_field('oauth_access_token', '', '', $option_name, '');
        add_settings_field('oauth_access_token_secret', '', '', $option_name, '');
        add_settings_field('consumer_key', '', '', $option_name, '');
        add_settings_field('consumer_secret', '', '', $option_name, '');
        add_settings_field('instagram_access_token', '', '', $option_name, '');
        add_settings_field('general-settings-open-links-in-new-window', '', '', $option_name, '');
        add_settings_field('general-settings-disable-follow-location', '', '', $option_name, '');
        add_settings_field('general-settings-ipv4', '', '', $option_name, '');
        add_settings_field('general-settings-https', '', '', $option_name, '');
        add_settings_field('facebook_access_token', '', '', $fb_auth_option_name, '');
        add_settings_field('facebook_app_id', '', '', $fb_auth_option_name, '');
        add_settings_field('facebook_app_secret', '', '', $fb_auth_option_name, '');
    }

    public function get_options() {
        $options = get_option('flow_flow_options');
        if ($options == NULL) $options = array();
        $options = $this->setDefaultValueIfNeeded($options);

        if (!isset($options['streams'])) {
            $options['streams'] = new stdClass();
        } else {
            $options['streams'] = json_decode($options['streams']);
            if ($options['streams'] == NULL) {
                $options['streams'] = new stdClass();
            }
        }
        return $options;
    }

    public function get_auth_options() {
        $options = get_option('flow_flow_fb_auth_options');
        if ($options == NULL) $options = array();
        if (!isset($options['facebook_access_token'])) $options['facebook_access_token'] = '';
        if (!isset($options['facebook_app_id'])) $options['facebook_app_id'] = '';
        if (!isset($options['facebook_app_secret'])) $options['facebook_app_secret'] = '';
        return $options;
    }

    public function validate_options($plugin_options) {
        if (!empty($plugin_options['feeds_changed'])) {
            if ($plugin_options['feeds_changed'] === 'all') {
                FFCacheManager::clean(array('%'));
            } else {
                FFCacheManager::clean(explode(',',$plugin_options['feeds_changed']));
            }

            FFImageSizeCacheManager::clean();
        }

        foreach ($plugin_options as $key => $val) {
            $plugin_options[$key] = trim($val);
        }

        $options['feeds_changed'] = '';
        return $plugin_options;
    }

    public function setDefaultValueIfNeeded($options) {
        if (!isset($options['last_submit'])) $options['last_submit'] = '';
        if (!isset($options['feeds_changed'])) $options['feeds_changed'] = '';
        if (!isset($options['oauth_access_token'])) $options['oauth_access_token'] = '';
        if (!isset($options['oauth_access_token_secret'])) $options['oauth_access_token_secret'] = '';
        if (!isset($options['consumer_secret'])) $options['consumer_secret'] = '';
        if (!isset($options['consumer_key'])) $options['consumer_key'] = '';
        if (!isset($options['instagram_access_token'])) $options['instagram_access_token'] = '';
        if (!isset($options['general-settings-open-links-in-new-window'])) $options['general-settings-open-links-in-new-window'] = 'nope';
        if (!isset($options['general-settings-disable-follow-location'])) $options['general-settings-disable-follow-location'] = 'nope';
        if (!isset($options['general-settings-ipv4'])) $options['general-settings-ipv4'] = 'nope';
        if (!isset($options['general-settings-https'])) $options['general-settings-https'] = 'nope';

        if (!isset($options['streams_count'])) {
            $options['streams_count'] = 0;
        }

        return $options;
    }

    public function afterUpdateOption($option, $old_value = '', $value = ''){
        if ('flow_flow_fb_auth_options' == $option) {
            FFCacheManager::clean(array('%'));
            FFFacebookCacheManager::clean();
        }
    }

    public static function get_plugin_directory(){
        return plugins_url() . '/' . self::$PLUGIN_SLUG . '/';
    }
}

// visible everywhere
function as_debug_to_console($data){
    $msg = (is_array($data) || is_object($data)) ?  json_encode($data) : $data;
    echo("<script>console.log('PHP: ".$msg."');</script>");
}