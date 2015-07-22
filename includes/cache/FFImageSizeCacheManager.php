<?php if ( ! defined( 'WPINC' ) ) die;
/**
 * Flow-Flow.
 *
 * @package   FlowFlow
 * @author    Looks Awesome <email@looks-awesome.com>

 * @link      http://looks-awesome.com
 * @copyright 2014 Looks Awesome
 */

class FFImageSizeCacheManager {
    private static $postfix = '_img_size_cache';
    private static $instance = null;

    /**
     * @return void
     */
    public static function clean(){
        delete_transient(FlowFlow::$PLUGIN_SLUG_DOWN . self::$postfix);
    }

    /**
     * @return FFImageSizeCacheManager
     */
    public static function get() {
        if ( null == self::$instance ) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private $size;
    private $image_cache;

    function __construct() {
        if (false === ($result = get_transient(FlowFlow::$PLUGIN_SLUG_DOWN . self::$postfix))) {
            $result = array();
        }
        $this->removeOldRecords($result);
        $this->size = sizeof($result);
        $this->image_cache = $result;
    }

    /**
     * @param string $url
     * @return array
     */
    public function size($url){
        $h = hash('md5', $url);
        if (!array_key_exists($h, $this->image_cache)){
            try{
	              if ($url && !empty($url)) {
		              @list($width, $height) = getimagesize($url);
		              $data = array('time' => time(), 'width' => $width, 'height' => $height);
	              } else {
		              $data = array('time' => time(), 'width' => '', 'height' => '');
	              }
	              $this->image_cache[$h] = $data;
                return $data;
            } catch (Exception $e){
                error_log($e->getTraceAsString());
            }
        }
        return $this->image_cache[$h];
    }

    /**
     * @return void
     */
    public function save() {
        if (sizeof($this->image_cache) > $this->size){
            set_transient(FlowFlow::$PLUGIN_SLUG_DOWN . self::$postfix, $this->image_cache);
        }
    }

    /**
     * @param array $result
     * @return void
     */
    private function removeOldRecords( $result ) {
        //TODO
    }
}