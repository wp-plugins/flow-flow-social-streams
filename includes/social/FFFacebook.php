<?php if ( ! defined( 'WPINC' ) ) die;
/**
 * Flow-Flow.
 *
 * @package   FlowFlow
 * @author    Looks Awesome <email@looks-awesome.com>

 * @link      http://looks-awesome.com
 * @copyright 2014 Looks Awesome
 */
require_once(AS_PLUGIN_DIR . 'includes/social/FFBaseFeed.php');
require_once(AS_PLUGIN_DIR . 'includes/social/FFFeedUtils.php');
require_once(AS_PLUGIN_DIR . 'includes/cache/FFFacebookCacheManager.php');

class FFFacebook extends FFBaseFeed{
	private $url;
    private $accessToken;
	private $image;
	private $media;
	private $images;

	public function __construct() {
		parent::__construct( 'facebook' );
	}

    public function deferredInit($options, $stream, $feed) {
	    $this->images = array();
		$this->accessToken = FFFacebookCacheManager::get()->getAccessToken();
        $locale     = 'locale='.get_locale();
        $fields     = 'fields=likes.summary(true),comments.summary(true),shares,id,created_time,from,link,message,name,object_id,picture,source,status_type,story,type&';
        $userId     = (string) $feed->content;
        $this->url  = "https://graph.facebook.com/v2.3/{$userId}/posts?{$fields}access_token={$this->accessToken}&limit={$this->getCount()}&{$locale}";
    }

    protected function getUrl() {
        return $this->url;
    }

	protected function onePagePosts() {
		$result = array();
		$data = FFFeedUtils::getFeedData( $this->getUrl() );
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
				$post->header           = (string) $this->getHeader( $item );
				$post->screenname       = (string) $this->getScreenName( $item );
				$post->system_timestamp = $this->getSystemDate( $item );
				$post->text             = (string) $this->getContent( $item );
				$post->userlink         = (string) $this->getUserlink( $item );
				$post->permalink        = (string) $this->getPermalink( $item );
				if ( $this->showImage( $item ) ) {
					$post->img   = $this->getImage( $item );
					$post->media = $this->getMedia( $item );
				}
				$post = $this->customize( $post, $item );
				if ( $this->isSuitablePost( $post ) ) {
					$result[$post->id] = $post;
				}
			}
		}
		return $result;
	}

    protected function items( $request ) {
        if (false !== $this->accessToken){
            $pxml = json_decode($request);
            if (isset($pxml->data)) {
                $tmp = array();
                foreach ($pxml->data as $item) {
	                if ($this->filter($item)) {
		                $tmp[] = $item;
	                }
                }
                return $tmp;
            }
        }
        return array();
    }

	protected function prepare( $item ) {
		$this->image = null;
		$this->media = null;
		return new stdClass();
	}

	protected function getHeader($item){
		if (isset($item->name)){
			return $item->name;
		}
        return '';
    }

    protected function showImage($item){
	    if ((isset($item->object_id) && (($item->type == 'photo') || ($item->type == 'event')))){
	        $url = "https://graph.facebook.com/v2.3/{$item->object_id}/picture?width={$this->getImageWidth()}&type=normal";
	        $this->image = $this->createImage($this->getLocation($url));
	        $this->media = 'image';
	        return true;
        }
	    if (($item->type == 'video')
	        && ($item->status_type == 'added_video' || $item->status_type == 'shared_story')){
		    if (!isset($item->object_id) && isset($item->link) && strpos($item->link, 'facebook.com') > 0 && strpos($item->link, '/videos/') > 0){
			    $path = parse_url($item->link, PHP_URL_PATH);
			    $tokens = explode('/', $path);
			    if (empty($tokens[sizeof($tokens)-1])) unset($tokens[sizeof($tokens)-1]);
			    $item->object_id = $tokens[sizeof($tokens)-1];
		    }
		    if (isset($item->object_id) && trim($item->object_id) != ''){
			    $url = "https://graph.facebook.com/v2.3/{$item->object_id}/picture?width={$this->getImageWidth()}&type=normal";
			    $this->image = $this->createImage($this->getLocation($url));
			    $this->media = 'video';
			    return true;
		    }
		    else if (isset($item->source)){
		        if (strpos($item->source, 'giphy.com') > 0) {
			        $arr = parse_url( urldecode( $item->source ) );
			        parse_str( $arr['query'], $output );
			        $this->image = $this->createImage( $output['gif_url'], $output['giphyWidth'], $output['giphy_height'] );
			        $this->media = 'image';
			        return true;
		        }
			    if (isset($item->picture)){
				    $this->image = $this->createImage($item->picture);
				    $this->media = 'video';
				    return true;
			    }
		    }
	    }
	    if ($item->type == 'link' && isset($item->picture)){
		    $image = $item->picture;
		    $parts = parse_url($image);
		    if (isset($parts['query'])){
			    parse_str($parts['query'], $attr);
			    if (isset($attr['url'])) {
				    $image = $attr['url'];
				    $this->image = $this->createImage($image);
				    if (!empty($this->image['height'])) {
					    $this->media = 'image';
					    return true;
				    }
			    }
		    }
		    $this->image = $this->createImage($item->picture);
		    $this->media = 'image';
		    return true;
	    }
	    return false;
    }

    protected function getImage( $item ) {
        return $this->image;
    }

	protected function getMedia( $item ) {
		return $this->media;
	}

	protected function getScreenName($item){
        return $item->from->name;
    }

    protected function getContent($item){
	    if (isset($item->message)) return $this->wrapHashTags($item->message, $item->id);
	    if (isset($item->story)) return (string)$item->story;
    }

    protected function getId( $item ) {
        return $item->id;
    }

    protected function getSystemDate( $item ) {
        return strtotime($item->created_time);
    }

    protected function getUserlink( $item ) {
        return 'https://www.facebook.com/'.$item->from->id;
    }

    protected function getPermalink( $item ) {
        $parts = explode('_', $item->id);
        return 'https://www.facebook.com/'.$parts[0].'/posts/'.$parts[1];
        if (isset($item->link)) return $item->link;
    }

	protected function customize( $post, $item ) {
		if ($item->type == 'link' && $item->type != 'video'){
			$post->source = $item->link;
		}
        return $post;
    }

    private function filter( $item ) {
	    if ($item->type == 'status' && !isset($item->message)) return false;
	    if ($item->type == 'photo' && isset($item->status_type) && $item->status_type == 'tagged_in_photo') return false;
        return true;
    }

	private function wrapHashTags($text, $id){
		$text = $this->wrapLinks($text);
		return preg_replace('/#([\\d\\w]+)/', '<a href="https://www.facebook.com/hashtag/$1?source=feed_text&story_id='.$id.'">$0</a>', $text);
	}

	private function getPCount( $json ){
		$count = sizeof($json->data);
		if (isset($json->paging->next)){
			$data = FFFeedUtils::getFeedData($json->paging->next);
			$count += $this->getPCount(json_decode($data['response']));
		}
		return $count;
	}

	private function getLocation($url) {
		$headers = get_headers($url . "&access_token={$this->accessToken}" , 1);
		if (isset($headers["Location"])) {
			return $headers["Location"];
		} else {
			$location = @$this->getCurlLocation($url . "&access_token={$this->accessToken}");
			if (!empty($location) && $location != 0) {
				return $location;
			}
		}
		return str_replace('/v2.3/', '/', $url);
	}

	private function getCurlLocation($url) {
		$curl = curl_init();
		curl_setopt_array( $curl, array(
			CURLOPT_HEADER => true,
			CURLOPT_NOBODY => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_URL => $url ) );
		$headers = explode( "\n", curl_exec( $curl ) );
		curl_close( $curl );

		$location = '';
		foreach ( $headers as $header ) {
			if (strpos($header, "ocation:")) {
				$location = substr($header, 10);
				break;
			}
		}
		return $location;
	}

	private function wrapLinks($source){
		$pattern = '/(https?:\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?)/i';
		$replacement = '<a href="$1">$1</a>';
		return preg_replace($pattern, $replacement, $source);
		return $source;
	}
}