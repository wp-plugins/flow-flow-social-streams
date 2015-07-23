<?php if ( ! defined( 'WPINC' ) ) die;
/**
 * Flow-Flow.
 *
 * @package   FlowFlow
 * @author    Looks Awesome <email@looks-awesome.com>

 * @link      http://looks-awesome.com
 * @copyright 2014 Looks Awesome
 */
require_once(AS_PLUGIN_DIR . 'includes/social/FFFeed.php');
require_once(AS_PLUGIN_DIR . 'includes/cache/FFImageSizeCacheManager.php');

abstract class FFBaseFeed implements FFFeed{
    private $id;
    private $cache;
    private $count;
    private $imageWidth;
	private $type;
	protected $options;
	protected $stream;
	protected $feed;
    protected $errors;

	function __construct( $type ) {
		$this->type = $type;
	}

	public function getType(){
		return $this->type;
	}

	public function id(){
        return $this->id;
    }

    public function getCount(){
        return $this->count;
    }

    public function getImageWidth(){
        return $this->imageWidth;
    }

    public function getAllowableWidth(){
        return 200;
    }

    public final function init($options, $stream, $feed){
	    $this->options = $options;
	    $this->stream = $stream;
	    $this->feed = $feed;

        $this->id = $feed->id;
        $this->errors = array();
        $this->count = $stream->getCountOfPosts();
        $this->imageWidth = $stream->getImageWidth();
        $this->cache = FFImageSizeCacheManager::get();
    }

	public final function posts() {
		$this->deferredInit($this->options, $this->stream, $this->feed);
		$this->beforeProcess();
		$result = array();
		do {
			$result += $this->onePagePosts();
		} while ($this->nextPage($result));
		return $this->afterProcess($result);
	}

	protected abstract function deferredInit($options, $stream, $feed);
	protected abstract function onePagePosts( );

    public function errors() {
        return $this->errors;
    }

    protected function createImage($url, $width = null, $height = null, $scale = true){
        if ($width == null || $height == null){
            $size = $this->cache->size($url);
            $width = $size['width'];
            $height = $size['height'];
        }
	    if ($scale){
		    $tWidth = $this->getImageWidth();
		    return array('url' => $url, 'width' => $tWidth, 'height' => $this->getScaleHeight($tWidth, $width, $height));
	    }
	    return array('url' => $url, 'width' => $width, 'height' => $height);
    }

	protected function isSuitablePost($post){
		return $post != null;
	}

	protected function beforeProcess(){
	}

    protected function afterProcess($result){
        $this->cache->save();
        return array_values($result);
    }

    public function useCache(){
        return true;
    }

	protected function nextPage($result){
		return false;
	}

	private function getScaleHeight($templateWidth, $originalWidth, $originalHeight){
		if (isset($originalWidth) && isset($originalHeight) && !empty($originalWidth)){
			$k = $templateWidth / $originalWidth;
			return round( $originalHeight * $k );
		}
		return '';
	}
} 