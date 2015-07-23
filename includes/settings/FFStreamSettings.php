<?php if ( ! defined( 'WPINC' ) ) die;
/**
 * Flow-Flow.
 *
 * @package   FlowFlow
 * @author    Looks Awesome <email@looks-awesome.com>

 * @link      http://looks-awesome.com
 * @copyright 2014 Looks Awesome
 */

class FFStreamSettings
{
    private $useCache;
    private $lifeTimeCache;
    private $stream;

    function __construct($stream) {
        $this->stream = (array)$stream;
        $this->id = $this->stream['id'];
    }

    public function getId() {
        return $this->stream['id'];
    }

    /**
     * @return string
     */
    public function getCountOfPosts() {
        $value = $this->stream["posts"];
        if (isset($value) && $value != '') {
            return $value;
        }
        return '20';
    }

    public function useCache() {
        if (!isset($this->useCache)){
            $this->prepareCacheData();
        }
        return $this->useCache;
    }

    public function getCacheLifeTime() {
        if (!isset($this->lifeTimeCache)){
            $this->prepareCacheData();
        }
        return $this->lifeTimeCache;
    }

    public function getAllFeeds() {
        return json_decode($this->stream['feeds']);
    }

    public function original() {
        return $this->stream;
    }

    public function isPossibleToShow(){
        $mobile = (bool)$this->is_mobile();
        $hideOnMobile = FFSettingsUtils::YepNope2ClassicStyle($this->stream["hide-on-mobile"], false);
        if ($hideOnMobile && $mobile) return false;
        $hideOnDesktop = FFSettingsUtils::YepNope2ClassicStyle($this->stream["hide-on-desktop"], false);
        if ($hideOnDesktop && !$mobile) return false;
        $private = FFSettingsUtils::YepNope2ClassicStyle($this->stream["private"], false);
        if ($private && !is_user_logged_in()) return false;
        return true;
    }

    public function getImageWidth() {
        $value = $this->stream["theme"];
	      $width = intval($this->stream["width"]);
        return ($value == 'classic') ? $width - 30 : $width;
    }

    private function prepareCacheData() {
        $lt = $this->stream["cache-lifetime"];
        $this->lifeTimeCache = 0;
        if (isset($lt)) {
            $this->lifeTimeCache = intval($lt) * 60;
        }
        //$use = $this->stream["cache"];
        $use = true;
        $this->useCache = (isset($use) && $this->lifeTimeCache != 0) ? FFSettingsUtils::YepNope2ClassicStyle($use, false) : false;
    }

    private function is_mobile(){
        return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i",
            $_SERVER["HTTP_USER_AGENT"]);
    }
}