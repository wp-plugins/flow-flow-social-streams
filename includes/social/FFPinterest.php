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

class FFPinterest extends FFBaseFeed {
    private $url;
    private $pin;
    private $pins = array();
    private $nickname;
    private $additionalUrl;
    private $originalContent;
    private $image;
    private $media;
    private $screenName;

    public function __construct() {
        parent::__construct( 'pinterest' );
    }

    public function deferredInit($options, $stream, $feed) {
        $sp = explode('/', $feed->content);
        if (sizeof($sp) < 2){
            $this->nickname = $feed->content;
            $this->url = "http://pinterest.com/{$feed->content}/feed.rss";
            $this->additionalUrl = "https://api.pinterest.com/v3/pidgets/users/{$feed->content}/pins/";
        }
        else {
            $this->nickname = $sp[0];
            $this->url = "http://pinterest.com/{$feed->content}/rss";
            $this->additionalUrl = "https://api.pinterest.com/v3/pidgets/boards/{$feed->content}/pins/";
        }
    }

    protected function onePagePosts() {
        $this->setAdditionalInfo();
        $result = array();
        $data = FFFeedUtils::getFeedData( $this->url );
        if ( sizeof( $data['errors'] ) > 0 ) {
            $this->errors[] = array(
                'type'    => $this->getType(),
                'message' => $data['errors'],
            );

            return array();
        }
        foreach ( $this->items( $data['response'] ) as $item ) {
            if (is_object( $item )) {
                $post                   = $this->prepare( $item );
                $post->feed             = $this->id();
                $post->id               = (string) $this->getId( $item );
                $post->type             = $this->getType();
                $post->header           = '';
                $post->screenname       = (string) $this->getScreenName( $item );
                $post->system_timestamp = $this->getSystemDate( $item );
                $post->text             = (string) $this->getContent( $item );
                $post->userlink         = (string) $this->getUserlink( $item );
                $post->permalink        = (string) $this->getPermalink( $item );
                $post->nickname = $this->nickname;
                if ( $this->showImage( $item ) ) {
                    $post->img   = $this->image;
                    $post->media = $this->media;
                }
                $result[$post->id] = $post;
            }
        }
        return $result;
    }

    protected function items($request){
        libxml_use_internal_errors(true);
        $pxml = new SimpleXMLElement($request);
        if ($pxml && isset($pxml->channel)) {
            if (!isset($this->screenName) || strlen($this->screenName) == 0) {
                $this->screenName = (string)$pxml->channel->title;
            }
            if (sizeof($pxml->channel->item) > $this->getCount())
                for ($i=0; $i < $this->getCount(); $i++)  $result[] = $pxml->channel->item[$i];
            else
                $result = $pxml->channel->item;
            return $result;
        }
        libxml_clear_errors();
        libxml_use_internal_errors(false);

        return array();
    }

    protected function prepare($item){
        $this->originalContent = (string)$item->description;
        $key = str_replace('https:', 'http:', (string)$item->guid);
        array_key_exists($key, $this->pins) ? $this->pin = $this->pins[$key] : $this->pin = null;
        $this->image = null;
        $this->media = null;
        return new stdClass();
    }

    protected function getId($item){
        return $item->guid;
    }

    protected function getScreenName($item){
        return is_null($this->pin) ? $this->screenName : $this->pin->pinner->full_name;
    }

    protected function getSystemDate($item){
        return strtotime($item->pubDate);
    }

    protected function getContent($item){
        return is_null($this->pin) ? $item->title : $this->pin->description;
    }

    protected function getPermalink($item){
        return (string)$item->guid;
    }

    protected function getUserlink($item){
        return is_null($this->pin) ? 'http://www.pinterest.com/' . $this->nickname : $this->pin->pinner->profile_url;
    }

    protected function customize($post, $item){
        $post->nickname = $this->nickname;
        return $post;
    }

    protected function showImage($item){
        if (!is_null($this->pin) && isset($this->pin->images->{'237x'})){
            $x237 = $this->pin->images->{'237x'};
            $this->image = $this->createImage($x237->url, $x237->width, $x237->height);
            $this->media = (isset($this->pin->embed) && $this->pin->is_video == 'true') ? 'video' : 'image';
        } else {
            $this->image = $this->createImage($this->getUrlFromImg($this->originalContent));
            $this->media = 'image';
        }
        return true;
    }

    private function setAdditionalInfo(){
        $data = FFFeedUtils::getFeedData($this->additionalUrl);
        if (sizeof($data['errors']) > 0){
            @error_log('Pinterest has errors: '.$data['errors']);
        } else {
            $response = json_decode($data['response']);
            foreach ($response->data->pins as $pin){
                $this->pins["http://www.pinterest.com/pin/{$pin->id}/"] = $pin;
            }
        }
    }

    private function getUrlFromImg($tag){
        preg_match("/\<img.+src\=(?:\"|\')(.+?)(?:\"|\')(?:.+?)\>/", $tag, $matches);
        return $matches[1];
    }
}