<?php if ( ! defined( 'WPINC' ) ) die;
/**
 * Flow-Flow.
 *
 * @package   FlowFlow
 * @author    Looks Awesome <email@looks-awesome.com>
 * @link      http://looks-awesome.com
 * @copyright 2014 Looks Awesome
 */
require_once(AS_PLUGIN_DIR . 'includes/settings/FFGeneralSettings.php');

class FFFeedUtils{
	private static $USER_AGENT = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.10; rv:35.0) Gecko/20100101 Firefox/35.0";

    public static function getFeedData($url, $header = false){
	    $ff = FlowFlow::get_instance();
	    $use = $ff->getGeneralSettings()->useCurlFollowLocation();
	    $useIpv4 = $ff->getGeneralSettings()->useIPv4();
        $c = curl_init();
        curl_setopt($c, CURLOPT_USERAGENT, self::$USER_AGENT);
        curl_setopt($c, CURLOPT_URL,$url);
        curl_setopt($c, CURLOPT_POST, 0);
        curl_setopt($c, CURLOPT_FAILONERROR, true);

	    // Enable if you have 'Network is unreachable' error
	    if ($useIpv4) curl_setopt( $c, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        if ($use) curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($c, CURLOPT_AUTOREFERER, true);
        curl_setopt($c, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($c, CURLOPT_VERBOSE, false);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
	    if (is_array($header)) {
		    curl_setopt($c, CURLOPT_HTTPHEADER, $header);
	    }
	    curl_setopt($c, CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($c, CURLOPT_TIMEOUT, 30);
        $page = ($use) ? curl_exec($c) : self::curl_exec_follow($c);
        $error = curl_error($c);
        $errors = array();
        if (strlen($error) > 0){
            $errors[] = $error;
        }
        curl_close($c);
        return array('response' => $page, 'errors' => $errors, 'url' => $url);
    }

	private static function curl_exec_follow($ch, &$maxRedirect = null) {
		$mr = $maxRedirect === null ? 5 : intval($maxRedirect);

		{
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

			if ($mr > 0) {
				$original_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
				$newUrl = $original_url;

				$rch = curl_copy_handle($ch);

				curl_setopt($rch, CURLOPT_HEADER, true);
				curl_setopt($rch, CURLOPT_NOBODY, true);
				curl_setopt($rch, CURLOPT_FORBID_REUSE, false);
				do {
					curl_setopt($rch, CURLOPT_URL, $newUrl);
					$header = curl_exec($rch);
					if (curl_errno($rch)) {
						$code = 0;
					} else {
						$code = curl_getinfo($rch, CURLINFO_HTTP_CODE);
						if ($code == 301 || $code == 302) {
							preg_match('/Location:(.*?)\n/i', $header, $matches);
							$newUrl = trim(array_pop($matches));

							// if no scheme is present then the new url is a
							// relative path and thus needs some extra care
							if(!preg_match("/^https?:/i", $newUrl)){
								$newUrl = $original_url . $newUrl;
							}
						} else {
							$code = 0;
						}
					}
				} while ($code && --$mr);

				curl_close($rch);

				if (!$mr) {
					if ($maxRedirect === null)
						trigger_error('Too many redirects.', E_USER_WARNING);
					else
						$maxRedirect = 0;

					return false;
				}
				curl_setopt($ch, CURLOPT_URL, $newUrl);
			}
		}
		return curl_exec($ch);
	}
}