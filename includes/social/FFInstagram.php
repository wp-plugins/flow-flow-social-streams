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

class FFInstagram extends FFBaseFeed {
    private $url;
	private $size = 0;
	private $pagination = true;

	public function __construct() {
		parent::__construct( 'instagram' );
	}

	public function deferredInit($options, $stream, $feed) {
        $original = $options->original();
        $accessToken = $original['instagram_access_token'];
		$userId = $this->getUserId($feed->content, $accessToken);
		$this->url = "https://api.instagram.com/v1/users/{$userId}/media/recent/?access_token={$accessToken}&count={$this->getCount()}";
    }

    public function onePagePosts() {
        $result = array();
        $data = FFFeedUtils::getFeedData($this->url);
        if (sizeof($data['errors']) > 0){
            $this->errors[] = $data['errors'];
            return array();
        }
        if (isset($data['response']) && is_string($data['response'])){
            $page = json_decode(html_entity_decode($data['response']));
	        if (isset($page->pagination) && isset($page->pagination->next_url))
		        $this->url = $page->pagination->next_url;
	        else
		        $this->pagination = false;
            foreach ($page->data as $item) {
	            $post = $this->parsePost($item);
	            if ($this->isSuitablePost($post)) $result[$post->id] = $post;
            }
        } else {
	        $this->errors[] = 'FFInstagram has returned the empty data.';
	        error_log('FFInstagram has returned the empty data.');
        }
        return $result;
    }

    private function parsePost($post) {
        $tc = new stdClass();
	    $tc->feed = $this->id();
        $tc->id = (string)$post->id;
        $tc->type = $this->getType();
        $tc->nickname = (string)$post->user->username;
        $tc->screenname = $this->removeEmoji((string)$post->user->full_name);
        $tc->system_timestamp = $post->created_time;
        $tc->img = $this->createImage($post->images->low_resolution->url,
            $post->images->low_resolution->width, $post->images->low_resolution->height);
        $tc->text = $this->getCaption($post);
        $tc->userlink = 'http://instagram.com/' . $tc->nickname;
        $tc->permalink = (string)$post->link;
	    $tc->media = (isset($post->type) && $post->type == 'video') ? 'video' : 'image';
        return $tc;
    }

    private function getCaption($post){
        return isset($post->caption->text) ? $this->removeEmoji((string)$post->caption->text) : '';
    }

    private function getUserId($content, $accessToken){
	    $url = "https://api.instagram.com/v1/users/search?q={$content}&access_token={$accessToken}";
        $request = wp_remote_get($url);
        $response = wp_remote_retrieve_body( $request );
        $json = json_decode($response);
	    if (!is_object($json) || (is_object($json) && sizeof($json->data) == 0)) {
            $this->errors[] = array('msg' => 'Username not found', 'log' => $response, 'url' => $url);
            return $content;
        } else {
			foreach($json->data as $user){
				if($user->username == $content) return $user->id;
			}
		    $this->errors[] = array('type' => 'Instagram', 'msg' => 'Username not found', 'log' => $response, 'url' => $url);
            return '00000000';
        }
    }

	protected function nextPage( $result ) {
		if ($this->pagination){
			$size = sizeof($result);
			if ($size == $this->size) {
				return false;
			}
			else {
				$this->size = $size;
				return $this->getCount() > $size;
			}
		}
		return false;
	}

	/**
	 * @param string $text
	 * @return mixed
	 */
	public function removeEmoji($text) {
		// Match Emoticons
		$regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
		$clean_text = preg_replace($regexEmoticons, '', $text);

		// Match Miscellaneous Symbols and Pictographs
		$regexSymbols = '/[\x{1F300}-\x{1F5FF}]/u';
		$clean_text = preg_replace($regexSymbols, '', $clean_text);

		// Match Transport And Map Symbols
		$regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
		$clean_text = preg_replace($regexTransport, '', $clean_text);

		// Match Miscellaneous Symbols
		$regexMisc = '/[\x{2600}-\x{26FF}]/u';
		$clean_text = preg_replace($regexMisc, '', $clean_text);

		// Match Dingbats
		$regexDingbats = '/[\x{2700}-\x{27BF}]/u';
		$clean_text = preg_replace($regexDingbats, '', $clean_text);

		return $clean_text;
	}
}