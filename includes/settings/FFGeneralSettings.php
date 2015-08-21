<?php if ( ! defined( 'WPINC' ) ) die;
/**
 * Flow-Flow.
 *
 * @package   FlowFlow
 * @author    Looks Awesome <email@looks-awesome.com>

 * @link      http://looks-awesome.com
 * @copyright 2014 Looks Awesome
 */

class FFGeneralSettings {
    private $options;
    private $auth_options;

    public function __construct($options, $auth_options) {
        $this->options = $options;
        $this->auth_options = $auth_options;
    }

    public function getStreamById($id) {
        foreach ($this->getAllStreams() as $stream) {
            if ($stream->id == $id) {
                return $stream;
            }
        }
    }

    public function getAllStreams() {
        if (!isset($this->options['streams'])){
            return array();
        }
        return $this->options['streams'];
    }

    public function original() {
        return $this->options;
    }

    public function originalAuth(){
        return $this->auth_options;
    }

    public function useCurlFollowLocation(){
        $value = $this->options["general-settings-disable-follow-location"];
        return FFSettingsUtils::notYepNope2ClassicStyle($value, true);
    }
    public function useIPv4(){
        $value = $this->options["general-settings-ipv4"];
        return FFSettingsUtils::YepNope2ClassicStyle($value, true);
    }
}