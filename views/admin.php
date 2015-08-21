<?php if ( ! defined( 'WPINC' ) ) die;
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 *
 * @package   FlowFlow
 * @author    Looks Awesome <email@looks-awesome.com>
 * @link      http://looks-awesome.com
 * @copyright 2014 Looks Awesome
 */

screen_icon();
?>



<div id="fade-overlay" class="loading">
    <i class="flaticon-settings"></i>
</div>


<!-- @TODO: Provide markup for your options page here. -->
<form id="flow_flow_form" method="post" action="options.php" enctype="multipart/form-data">

    <!--Register settings-->
    <?php
    settings_fields('ff_opts');
    $options = FlowFlow::get_instance()->get_options();
    $auth = FlowFlow::get_instance()->get_auth_options();
    $arr = (array)$options['streams'];
    $count = count($arr);
    $feedsChanged = $options['feeds_changed'];
    $options['feeds_changed'] = ''; // init clear

    //		FlowFlowAdmin::debug_to_console('OPTIONS');
    //		FlowFlowAdmin::debug_to_console($options);

    ?>

    <script>
        var STREAMS = <?php echo json_encode($options["streams"])?>;
        var _ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

    </script>
    <input type="hidden" id="lastSubmit" name="flow_flow_options[last_submit]" value="<?php echo $options['last_submit'] ?>"/>
    <input type="hidden" id="feedsChanged" name="flow_flow_options[feeds_changed]" value="<?php echo $options['feeds_changed'] ?>"/>
    <div class="wrapper">
        <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

        <ul class="section-tabs">
            <li id="streams-tab"><i class="flaticon-flow"></i> <span>Streams</span></li><li id="general-tab"><i class="flaticon-settings"></i> <span>Settings</span></li><li id="auth-tab"><i class="flaticon-user"></i> <span>Auth</span></li>
        </ul>
        <div class="section-contents">
            <div class="section-content" id="streams-cont" data-tab="streams-tab">
                <div class="section-stream" id="streams-list" data-view-mode="streams-list">

                    <input type="hidden" id="streams" name="flow_flow_options[streams]" value=''/>
                    <input type="hidden" id="streams_count" name="flow_flow_options[streams_count]" value = "<?php echo $count ?>"/>

                    <div class="section" id="streams-list">
                        <h1 class="desc-following"><span>List of your streams</span> <span class="admin-button green-button button-add">Add stream</span></h1>
                        <p class="desc">Here is a list of your streams. Edit them to change styling or to add/remove your social feeds.</p>
                        <table>
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Stream</th>
                                    <th>Layout</th>
                                    <th>Feeds</th>
                                    <th>Shortcode</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php

                                foreach ($options['streams'] as $key => $stream) {

                                    if (!isset($stream->id)) continue;

                                    $info = '';

                                    $shortcodeStr = '[ff id="' . $stream->id . '"]';

                                    if (isset($stream->feeds)) {
                                        $feeds = json_decode($stream->feeds);
                                        $length = count($feeds);

                                        for ($i = 0; $i < $length; $i++) {
                                            $feed = $feeds[$i];
                                            $info = $info . '<i class="flaticon-' . $feed->type . '"></i>';
                                        }
                                    }


                                    echo
                                        '<tr data-stream-id="' . $stream->id . '">
							      <td class="controls"><i class="flaticon-pen"></i> <i class="flaticon-copy"></i> <i class="flaticon-trash"></i></td>
							      <td class="td-name">' . (!empty($stream->name) ? $stream->name : 'Unnamed') . '</td>
							      <td class="td-type"><span class="icon-grid"></span></td>
							      <td class="td-feed">' . (empty($info) ? '-' : $info) . '</td>
							      <td><span class="shortcode">' . $shortcodeStr . '</span><span class="shortcode-copy">Copy to clipboard</span></td>
						      </tr>';
                                }

                                //$arr = (array)$options['streams'];
                                if (empty($arr)) {
                                    echo '<tr><td class="empty-cell" colspan="5">Please add at least one stream</td></tr>';
                                }

                                ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="section">
                        <h1 class="desc-following"><span>Upgrade to PRO</span> <a target="_blank" href="http://codecanyon.net/item/flowflow-social-streams-for-wordpress/9319434?ref=looks_awesome" class="admin-button green-button button-upgrade">Upgrade</a></h1>
                        <p class="desc">Upgrade to remove this ad and use the benefits of Flow-Flow <strong>premium features</strong>.</p>
                        <a href="http://social-streams.com" target="_blank" class="features"></a>
                    </div>
                </div>
                <?php
                //

                $templates = array(
                    'view' => '
						<div class="section-stream" id="streams-update-%id%" data-view-mode="streams-update">
							<input type="hidden" name="stream-%id%-id" class="stream-id-value" value="%id%"/>
							<div class="section" id="stream-name-%id%">
								<h1>Edit stream name <span class="admin-button grey-button button-go-back">Go back to list</span></h1>
								<p><input type="text" name="stream-%id%-name" placeholder="Type name of stream."/></p>
								<span id="stream-name-sbmt-%id%" class="admin-button green-button submit-button">Save Changes</span>
							</div>
							<div class="section" id="stream-feeds-%id%">
								<input type="hidden" name="stream-%id%-feeds"/>
								<h1>Feeds in your stream <span class="admin-button green-button button-add">Add social feed</span></h1>
								<table>
									<thead>
									<tr>
										<th></th>
										<th>Feed</th>
										<th>Settings</th>
									</tr>
									</thead>
									<tbody>
									  %LIST%
									</tbody>
								</table>
								<div class="popup" id="feeds-settings-%id%">
									<i class="popupclose flaticon-close-4"></i>
									<div class="section">
										<div class="networks-choice add-feed-step">
											<h1>Add feed to your stream</h1>
											<p class="desc">Choose one social network and then set up what content to show.</p>
											<ul class="networks-list">
												<li class="network-twitter" data-network="twitter" data-network-name="Twitter"><i class="flaticon-twitter"></i></li>
												<li class="network-facebook" data-network="facebook" data-network-name="Facebook"><i class="flaticon-facebook"></i></li>
												<li class="network-instagram" data-network="instagram" data-network-name="Instagram"><i class="flaticon-instagram"></i></li>
												<li class="network-pinterest" data-network="pinterest" data-network-name="Pinterest"><i class="flaticon-pinterest"></i></li>
											</ul>
										</div>
										<div class="networks-content  add-feed-step">
											%FEEDS%
											<p class="feed-popup-controls add">
												<span id="networks-sbmt-%id%" class="admin-button green-button submit-button">Add feed</span><span class="space"></span><span class="admin-button grey-button button-go-back">Back to first step</span>
											</p>
											<p class="feed-popup-controls edit">
												<span id="networks-sbmt-%id%" class="admin-button green-button submit-button">Save changes</span>
											</p>
										</div>
									</div>
								</div>
							</div>
							<div class="section" id="stream-settings-%id%">
								<h1>Stream general settings</h1>
								<dl class="section-settings section-compact">
									<dt>Load last
										<p class="desc">Number of items that is pulled and cached from each connected feed. Be aware that some APIs can ignore this setting.</p>
									</dt>
									<dd>
										<input type="text"  name="stream-%id%-posts" value="40" class="short clearcache"/> posts
									</dd>
									<dt class="multiline" style="display:none">Cache
									<p class="desc">Caching stream data to reduce loading time</p></dt>
									<dd style="display:none">
										<label for="stream-%id%-cache"><input id="stream-%id%-cache" class="switcher clearcache" type="checkbox" name="stream-%id%-cache" checked value="yep"/><div><div></div></div></label>
									</dd>
									<dt class="multiline">Cache lifetime
									<p class="desc">Make it longer if feeds are rarely updated or shorter if you need frequent updates.</p></dt>
									<dd>
										<label for="stream-%id%-cache-lifetime"><input id="stream-%id%-cache-lifetime" class="short clearcache" type="text" name="stream-%id%-cache-lifetime" value="10"/> minutes</label>
									</dd>

									<dt class="multiline">Private stream<p class="desc">Show only for logged in users.</p></dt>
									<dd>
										<label for="stream-%id%-private"><input id="stream-%id%-private" class="switcher" type="checkbox" name="stream-%id%-private" value="yep"/><div><div></div></div></label>
									</dd>
									<dt>Hide stream on a desktop</dt>
									<dd>
										<label for="stream-%id%-hide-on-desktop"><input id="stream-%id%-hide-on-desktop" class="switcher" type="checkbox" name="stream-%id%-hide-on-desktop" value="yep"/><div><div></div></div></label>
									</dd>
									<dt>Hide stream on a mobile device</dt>
									<dd>
										<label for="stream-%id%-hide-on-mobile"><input id="stream-%id%-hide-on-mobile" class="switcher" type="checkbox" name="stream-%id%-hide-on-mobile" value="yep"/><div><div></div></div></label>
									</dd>
								</dl>
								<span id="stream-settings-sbmt-%id%" class="admin-button green-button submit-button">Save Changes</span>
							</div>

							<div class="section" id="cont-settings-%id%">
								<h1>Stream container settings</h1>
								<dl class="section-settings section-compact">
									<dt class="multiline">Stream heading
									<p class="desc">Leave empty to not show</p></dt>
									<dd>
										<input id="stream-%id%-heading" type="text" name="stream-%id%-heading" placeholder="Enter heading"/>
									</dd>
									<dt class="multiline">Heading color and filter buttons hover color
												<p class="desc">Click on field to open colorpicker</p>
									</dt>
									<dd>
										<input id="heading-color-%id%" data-color-format="rgba" name="stream-%id%-headingcolor" type="text" value="rgb(154, 78, 141)" tabindex="-1">
									</dd>
									<dt>Stream subheading</dt>
									<dd>
										<input id="stream-%id%-subheading" type="text" name="stream-%id%-subheading" placeholder="Enter subheading"/>
									</dd>
									<dt class="multiline">Subheading color
											<p class="desc">You can also paste color in input</p>
									</dt>
									<dd>
										<input id="subheading-color-%id%" data-color-format="rgba" name="stream-%id%-subheadingcolor" type="text" value="rgb(114, 112, 114)" tabindex="-1">
									</dd>
									<dt><span class="valign">Heading and subheading alignment</span></dt>
									<dd class="">
										<div class="select-wrapper">
											<select name="stream-%id%-hhalign" id="hhalign-%id%">
												<option value="center" selected>Centered</option>
												<option value="left">Left</option>
												<option value="right">Right</option>
	                    					</select>
										</div>
									</dd>
									<dt class="multiline">Container background color
										<p class="desc">You can see it in live preview below</p>
									</dt>
									<dd>
										<input data-prop="backgroundColor" id="bg-color-%id%" data-color-format="rgba" name="stream-%id%-bgcolor" type="text" value="rgb(229, 229, 229)" tabindex="-1">
									</dd>
									<dt class="multiline">Slider on mobiles <p class="desc">On mobiles grid will turn into slider with 3 items per slide</p></dt>
									<dd>
										<label for="stream-%id%-mobileslider"><input id="stream-%id%-mobileslider" class="switcher" type="checkbox" name="stream-%id%-mobileslider" value="yep"/><div><div></div></div></label>
									</dd>
									<dt class="multiline">Animate grid items <p class="desc">When they appear in viewport (otherwise all items are visible immediately)</p></dt>
									<dd>
										<label for="stream-%id%-viewportin"><input id="stream-%id%-viewportin" class="switcher" type="checkbox" name="stream-%id%-viewportin" checked value="yep"/><div><div></div></div></label>
									</dd>

								</dl>
								<span id="stream-cont-sbmt-%id%" class="admin-button green-button submit-button">Save Changes</span>
							</div>
							<div class="section grid-layout-chosen" id="stream-stylings-%id%">
								<input name="stream-%id%-layout" class="clearcache" id="stream-layout-grid-%id%" type="hidden" value="grid"/>
								<div class="design-step-2 layout-grid">
									<input name="stream-%id%-theme"    id="theme-classic-%id%" type="hidden" value="classic" class="clearcache"/>
									<input name="stream-%id%-gc-style" id="gc-style-%id%"      type="hidden" value="style-4"/>
									<h1>Grid stylings</h1>
									<dl class="classic-style style-choice section-settings section-compact">
										<dt><span class="valign">Card dimensions</span></dt>
										<dd>Width: <input type="text" data-prop="width" id="width-%id%" name="stream-%id%-width" value="260" class="short clearcache"/> px <span class="space"></span> Margin: <input type="text" value="20" class="short" name="stream-%id%-margin"/> px</dd>

										<dt class="multiline">Card background color
													<p class="desc">Click on field to open colorpicker</p>
										</dt>
										<dd>
											<input data-prop="backgroundColor" id="card-color-%id%" data-color-format="rgba" name="stream-%id%-cardcolor" type="text" value="rgb(255,255,255)" tabindex="-1">
										</dd>
										<dt class="multiline">Color for heading & name
												<p class="desc">Also for social button hover</p>
										</dt>
										<dd>
											<input data-prop="color" id="name-color-%id%" data-color-format="rgba" name="stream-%id%-namecolor" type="text" value="rgb(154, 78, 141)" tabindex="-1">
										</dd>
										<dt>Regular text color
										</dt>
										<dd>
											<input data-prop="color" id="text-color-%id%" data-color-format="rgba" name="stream-%id%-textcolor" type="text" value="rgb(85,85,85)" tabindex="-1">
										</dd>
										<dt>Links color</dt>
										<dd>
											<input data-prop="color" id="links-color-%id%" data-color-format="rgba" name="stream-%id%-linkscolor" type="text" value="rgb(94, 159, 202)" tabindex="-1">
										</dd>
										<dt class="multiline">Other text color
										<p class="desc">Nicknames, timestamps</p></dt>
										<dd>
											<input data-prop="color" id="other-color-%id%" data-color-format="rgba" name="stream-%id%-restcolor" type="text" value="rgb(132, 118, 129)" tabindex="-1">
										</dd>
										<dt>Card shadow</dt>
										<dd>
											<input data-prop="box-shadow" id="shadow-color-%id%" data-color-format="rgba" name="stream-%id%-shadow" type="text" value="rgba(0, 0, 0, 0.22)" tabindex="-1">
										</dd>
										<dt>Separator line color</dt>
										<dd>
											<input data-prop="border-color" id="bcolor-%id%" data-color-format="rgba" name="stream-%id%-bcolor" type="text" value="rgba(240, 237, 231, 0.4)" tabindex="-1">
										</dd>
										<dt><span class="valign">Text alignment</span></dt>
										<dd class="">
											<div class="select-wrapper">
												<select name="stream-%id%-talign" id="talign-%id%">
													<option value="left" selected>Left</option>
													<option value="center">Centered</option>
													<option value="right">Right</option>
		                    					</select>
											</div>
										</dd>
										<dt class="hide">Preview</dt>
										<dd class="preview">
										  <h1>Live preview</h1>
											<div data-preview="bg-color" class="ff-stream-wrapper ff-layout-grid ff-theme-classic ff-style-4 shuffle">
												<div data-preview="card-color,shadow-color,width" class="ff-item ff-twitter shuffle-item filtered" style="visibility: visible; opacity:1;">
												  <h4 data-preview="name-color">Header example</h4>
												  <p data-preview="text-color">This is regular text paragraph, can be tweet, facebook post etc. This is example of <a href="#" data-preview="links-color">link in text</a>.</p>
												  <span class="ff-img-holder" style="max-height: 171px"><img src="' . FlowFlow::get_plugin_directory() . '/assets/67.png" style="width:100%;"></span>
												  <div class="ff-item-meta">
												    <span class="separator" data-preview="bcolor"></span>
												  	<span class="ff-userpic" style="background:url(' . FlowFlow::get_plugin_directory() . '/assets/chevy.jpeg)"><i class="ff-icon" data-overrideProp="border-color" data-preview="card-color"><i class="ff-icon-inner"></i></i></span><a data-preview="name-color" target="_blank" rel="nofollow" href="#" class="ff-name">Looks Awesome</a><a data-preview="other-color" target="_blank" rel="nofollow" href="#" class="ff-nickname">@looks_awesome</a><a data-preview="other-color" target="_blank" rel="nofollow" href="#" class="ff-timestamp">21m ago </a>
											    </div>
										    </div>
											</div>
										</dd>
									</dl>

									<span id="stream-stylings-sbmt-%id%" class="admin-button green-button submit-button">Save Changes</span>
								</div>
							</div>
							<div class="section" id="css-%id%">
								<h1 class="desc-following">Stream custom CSS</h1>
								<p class="desc" style="margin-bottom:10px">
								  Prefix your selectors with <strong>#ff-stream-%id%</strong> to target this specific stream
								</p>
								<textarea  name="stream-%id%-css" cols="100" rows="10" id="stream-%id%-css"/> </textarea>
								<p style="margin-top:10px"><span id="stream-css-sbmt-%id%" class="admin-button green-button submit-button">Save Changes</span><p>
							</div>

							<div class="section">
		<h1 class="desc-following"><span>Upgrade to PRO</span> <a target="_blank" href="http://codecanyon.net/item/flowflow-social-streams-for-wordpress/9319434?ref=looks_awesome" class="admin-button green-button button-upgrade">Upgrade</a></h1>
		<p class="desc">Upgrade to remove this ad and use the benefits of Flow-Flow <strong>premium features</strong>.</p>
		<a href="http://social-streams.com" target="_blank" class="features"></a>
		</table>
	</div>

						</div>
					',
                    'twitterView' => '
						<div class="feed-view" data-feed-type="twitter" data-uid="%uid%">
							<h1>Content settings for Twitter feed</h1>
							<dl class="section-settings">
								<dt>User</dt>
								<dd>
									<input type="text" name="%uid%-content" placeholder="What content to stream"/>
									<p class="desc">Enter nickname (without @) of any public Twitter</p>
								</dd>
								<dt>Include retweets (if present)</dt>
								<dd>
									<label for="%uid%-retweets"><input id="%uid%-retweets" class="switcher" type="checkbox" name="%uid%-retweets" value="yep"/><div><div></div></div></label>
								</dd>
								<dt>Include replies (if present)</dt>
								<dd>
									<label for="%uid%-replies"><input id="%uid%-replies" class="switcher" type="checkbox" name="%uid%-replies" value="yep"/><div><div></div></div></label>
								</dd>
							</dl>
						</div>
					',
                    'facebookView' => '
						<div class="feed-view"  data-feed-type="facebook" data-uid="%uid%">
							<h1>Content settings for Facebook feed</h1>
							<dl class="section-settings">
								<dt>Facebook public page</dt>
								<dd>
									<input type="text" name="%uid%-content" placeholder="What content to stream"/>
								 	<p class="desc">Enter nickname of any public page (or ID if it is in page address)</p>
								</dd>
							</dl>
						</div>
					',
                    'instagramView' => '
						<div class="feed-view" data-feed-type="instagram" data-uid="%uid%">
							<h1>Content settings for Instagram feed</h1>
							<dl class="section-settings">
								<dt>User</dt>
								<dd>
									<input type="text" name="%uid%-content" placeholder="What content to stream"/>
									<p class="desc">Enter nickname of any public Instagram account</p>
								</dd>
							</dl>
						</div>
					',
                    'pinterestView' => '
						<div class="feed-view" data-feed-type="pinterest" data-uid="%uid%">
							<h1>Content settings for Pinterest feed</h1>
							<dl class="section-settings">
								<dt class="">Content to show</dt>
								<dd class="">
									<input type="text" name="%uid%-content" placeholder="What content to stream"/>
									<p class="desc">e.g. <strong>elainen</strong> (for user feed) or <strong>elainen/cute-animals</strong> (for user board).</p>
								</dd>
							</dl>
						</div>
					'
                );
                ?>
                <script>
                    var ff_templates = {
                        view: '<?php echo trim(preg_replace('/\s+/', ' ', $templates['view'])); ?>',
                        twitterView : '<?php echo trim(preg_replace('/\s+/', ' ', $templates['twitterView'])); ?>',
                        facebookView : '<?php echo trim(preg_replace('/\s+/', ' ', $templates['facebookView'])); ?>',
                        instagramView : '<?php echo trim(preg_replace('/\s+/', ' ', $templates['instagramView'])); ?>',
                        pinterestView : '<?php echo trim(preg_replace('/\s+/', ' ', $templates['pinterestView'])); ?>'
                    }
                </script>
                <?php

                ?>
            </div>
            <div class="section-content" data-tab="general-tab">
                <div class="section" id="general-settings">
                    <h1>General Settings</h1>
                    <dl class="section-settings">
                        <dt>Open links in new window</dt>
                        <dd>
                            <label for="general-settings-open-links-in-new-window">
                                <input id="general-settings-open-links-in-new-window" class="switcher clearcache" type="checkbox"
                                       name="flow_flow_options[general-settings-open-links-in-new-window]"
                                    <?php if (!isset($options['general-settings-open-links-in-new-window']) || $options['general-settings-open-links-in-new-window'] == 'yep') echo "checked"; ?>
                                       value="yep"/><div><div></div></div>
                            </label>
                        </dd>
                        <dt class="multiline">Disable curl "follow location"
                        <p class="desc">Can help if your server uses deprecated security setting 'safe_mode' and streams don't load.</p></dt>
                        <dd>
                            <label for="general-settings-disable-follow-location">
                                <input id="general-settings-disable-follow-location" class="clearcache switcher" type="checkbox"
                                       name="flow_flow_options[general-settings-disable-follow-location]"
                                    <?php if (!isset($options['general-settings-disable-follow-location']) || $options['general-settings-disable-follow-location'] == 'yep') echo "checked"; ?>
                                       value="yep"/><div><div></div></div>
                        </dd>
                        <dt class="multiline">Use IPv4 protocol
                        <p class="desc">Sometimes servers use older version of Internet protocol. Use setting when you see "Network is unreachable" error.</p></dt>
                        <dd>
                            <label for="general-settings-ipv4">
                                <input id="general-settings-ipv4" class="clearcache switcher" type="checkbox"
                                       name="flow_flow_options[general-settings-ipv4]"
                                    <?php if (isset($options['general-settings-ipv4']) && $options['general-settings-ipv4'] == 'yep') echo "checked"; ?>
                                       value="yep"/><div><div></div></div>
                        </dd>

                        <dt class="multiline">Force HTTPS for all resources
                        <p class="desc">For images and videos to load via HTTPS. Use this settings if you have HTTPS site and see browser security warnings. Keep in mind that this is forcing and not all resources can be available via HTTPS</p></dt>
                        <dd>
                            <label for="general-settings-https">
                                <input id="general-settings-https" class="clearcache switcher" type="checkbox"
                                       name="flow_flow_options[general-settings-https]"
                                    <?php if (isset($options['general-settings-https']) && $options['general-settings-https'] == 'yep') echo "checked"; ?>
                                       value="yep"/><div><div></div></div>
                        </dd>
                    </dl>
                    <span id="general-settings-sbmt" class='admin-button green-button submit-button'>Save Changes</span>
                </div>
                <div class="section">
                    <h1 class="desc-following"><span>Upgrade to PRO</span> <a target="_blank" href="http://codecanyon.net/item/flowflow-social-streams-for-wordpress/9319434?ref=looks_awesome" class="admin-button green-button button-upgrade">Upgrade</a></h1>
                    <p class="desc">Upgrade to remove this ad and use the benefits of Flow-Flow <strong>premium features</strong>.</p>
                    <a href="http://social-streams.com" target="_blank" class="features"></a>
                    </table>
                </div>
            </div>
            <div class="section-content" data-tab="auth-tab">
                <div class="section" id="auth-settings">
                    <h1 class="desc-following">Twitter auth settings</h1>
                    <p class="desc">Valid for all (public) twitter accounts. You need to authenticate one (and any) twitter account here. <a target="_blank" href="http://flow.looks-awesome.com/docs/Setup/Authenticate_with_Twitter">Follow setup guide</a></p>
                    <dl class="section-settings">
                        <dt class="vert-aligned">Consumer Key (API Key)</dt>
                        <dd>
                            <input class="clearcache" type="text" name="flow_flow_options[consumer_key]" placeholder="Copy and paste from Twitter" value="<?php echo $options['consumer_key']?>"/>
                        </dd>
                        <dt class="vert-aligned">Consumer Secret (API Secret)</dt>
                        <dd>
                            <input class="clearcache" type="text" name="flow_flow_options[consumer_secret]" placeholder="Copy and paste from Twitter" value="<?php echo $options['consumer_secret']?>"/>
                        </dd>
                        <dt class="vert-aligned">Access Token</dt>
                        <dd>
                            <input class="clearcache" type="text" name="flow_flow_options[oauth_access_token]" placeholder="Copy and paste from Twitter" value="<?php echo $options['oauth_access_token']?>"/>
                        </dd>
                        <dt class="vert-aligned">Access Token Secret</dt>
                        <dd>
                            <input class="clearcache" type="text" name="flow_flow_options[oauth_access_token_secret]" placeholder="Copy and paste from Twitter" value="<?php echo $options['oauth_access_token_secret']?>"/>						</dd>

                    </dl>
                    <p class="button-wrapper"><span id="tw-auth-settings-sbmt" class='admin-button green-button submit-button'>Save Changes</span></p>

                    <h1  class="desc-following">Facebook auth settings</h1>
                    <p class="desc">Valid to pull any public FB page. <a target="_blank" href="http://flow.looks-awesome.com/docs/Setup/Authenticate_with_Facebook">Follow setup guide</a></p>
                    <dl class="section-settings">
                        <dt class="vert-aligned">Access Token</dt>
                        <dd>
                            <input class="clearcache" type="text" name="flow_flow_fb_auth_options[facebook_access_token]" placeholder="Copy and paste from Facebook" value="<?php echo $auth['facebook_access_token']?>"/>
                            <?php
                            $extended = get_transient(FlowFlow::$PLUGIN_SLUG_DOWN . '_facebook_access_token');
                            if(!empty($auth['facebook_access_token']) && !empty($extended) ) {
                                echo '<p class="desc" style="margin: 10px 0 5px">Generated long-life token, it should be different from that you entered above then FB auth is OK</p><textarea disabled rows=3>' .get_transient(FlowFlow::$PLUGIN_SLUG_DOWN . '_facebook_access_token') . '</textarea>';
                            }
                            ?>
                        </dd>
                        <dt class="vert-aligned">APP ID</dt>
                        <dd>
                            <input class="clearcache" type="text" name="flow_flow_fb_auth_options[facebook_app_id]" placeholder="Copy and paste from Facebook" value="<?php echo $auth['facebook_app_id']?>"/>
                        </dd>
                        <dt class="vert-aligned">APP Secret</dt>
                        <dd>
                            <input class="clearcache" type="text" name="flow_flow_fb_auth_options[facebook_app_secret]" placeholder="Copy and paste from Facebook" value="<?php echo $auth['facebook_app_secret']?>"/>
                        </dd>
                    </dl>
                    <p class="button-wrapper"><span id="fb-auth-settings-sbmt" class='admin-button green-button submit-button'>Save Changes</span></p>


                    <h1 class="desc-following">Instagram auth settings</h1>
                    <p class="desc">Valid to pull any public Instagram account feed or valid search term. <a target="_blank" href="http://flow.looks-awesome.com/docs/Setup/Authenticate_with_Instagram">Follow setup guide</a></p>
                    <dl class="section-settings">
                        <dt class="vert-aligned">Access Token</dt>
                        <dd>
                            <input class="clearcache" type="text" name="flow_flow_options[instagram_access_token]" placeholder="Copy and paste from Instagram" value="<?php echo $options['instagram_access_token']?>"/>
                        </dd>
                    </dl>
                    <p class="button-wrapper"><span id="inst-auth-settings-sbmt" class='admin-button green-button submit-button'>Save Changes</span></p>
                </div>
                <div class="section">
                    <h1 class="desc-following"><span>Upgrade to PRO</span> <a target="_blank" href="http://codecanyon.net/item/flowflow-social-streams-for-wordpress/9319434?ref=looks_awesome" class="admin-button green-button button-upgrade">Upgrade</a></h1>
                    <p class="desc">Upgrade to remove this ad and use the benefits of Flow-Flow <strong>premium features</strong>.</p>
                    <a href="http://social-streams.com" target="_blank" class="features"></a>
                    </table>
                </div>
            </div>
        </div>
        <div id="la">Made with love by <a  target="_blank" href="http://looks-awesome.com">Looks Awesome</a></div>
        <div id="social-links"><a target="_blank" class="sl-tw flaticon-twitter" href="https://twitter.com/looks_awesooome"></a><a  target="_blank" class="sl-fb flaticon-facebook" href="https://www.facebook.com/looksawesooome"></a><a  target="_blank" class="sl-gp flaticon-google" href="https://plus.google.com/+Looksawesomeee/posts"></a></div>
    </div>

</form>
<script>jQuery(document).trigger('html_ready')</script>