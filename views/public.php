<?php if ( ! defined( 'WPINC' ) ) die;
/**
 * Represents the view for the public-facing component of the plugin.
 *
 * This typically includes any information, if any, that is rendered to the
 * frontend of the theme when the plugin is activated.
 *
 * @package   FlowFlow
 * @author    Looks Awesome <email@looks-awesome.com>
 * @link      http://looks-awesome.com
 * @copyright 2014 Looks Awesome
 */

$id = $stream->id;
$disableCache = isset($_REQUEST['disable-cache']);

if ( ! in_array( 'curl', get_loaded_extensions() ) ) {
	echo "<p style='background: indianred;padding: 15px;color: white;'>Flow-Flow admin info: Your server doesn't have cURL module installed. Please ask your hosting to check this.</p>";
	return;
}

?>
<!-- Flow-Flow — Social streams plugin for Wordpress -->
<style type="text/css" id="ff-dynamic-styles-<?php echo $id;?>">
	#ff-stream-<?php echo $id;?> .ff-header h1,#ff-stream-<?php echo $id;?> .ff-controls-wrapper > span:hover { color: <?php echo $stream->headingcolor;?>; }
	#ff-stream-<?php echo $id;?> .ff-controls-wrapper > span:hover { border-color: <?php echo $stream->headingcolor;?> !important; }
	#ff-stream-<?php echo $id;?> .ff-header h2 { color: <?php echo $stream->subheadingcolor;?>; }
	#ff-stream-<?php echo $id;?> .ff-filter-holder .ff-filter,
	#ff-stream-<?php echo $id;?> .ff-filter-holder:before,
	#ff-stream-<?php echo $id;?> .ff-filter:hover,
	#ff-stream-<?php echo $id;?> .ff-loadmore-wrapper .ff-btn,
	#ff-stream-<?php echo $id;?> .ff-square:nth-child(1) {
		background-color: <?php echo $stream->headingcolor;?>;
	}
	#ff-stream-<?php echo $id;?>,
	#ff-stream-<?php echo $id;?> .ff-search input,
	#ff-stream-<?php echo $id;?>.ff-layout-compact .picture-item__inner {
		background-color: <?php echo $stream->bgcolor;?>;
	}
	#ff-stream-<?php echo $id;?> .ff-header h1, #ff-stream-<?php echo $id;?> .ff-header h2 {
		text-align: <?php echo $stream->hhalign;?>;
	}
	#ff-stream-<?php echo $id;?> .ff-item, #ff-stream-<?php echo $id;?> .shuffle__sizer{
		width:  <?php echo $stream->width;?>px;
	}
	#ff-stream-<?php echo $id;?> .ff-item {
		margin-bottom: <?php echo $stream->margin;?>px !important;
	}
	#ff-stream-<?php echo $id;?> .shuffle__sizer {
		margin-left: <?php echo $stream->margin;?>px !important;
	}
	#ff-stream-<?php echo $id;?>  .picture-item__inner {
		background: <?php echo $stream->cardcolor;?>;
		color: <?php echo $stream->textcolor;?>;
		box-shadow: 0 1px 4px 0 <?php echo $stream->shadow;?>;
	}
	#ff-stream-<?php echo $id;?> .ff-mob-link{
		background-color: <?php echo $stream->textcolor;?>;
	}
	#ff-stream-<?php echo $id;?> .ff-mob-link:after{
		color: <?php echo $stream->cardcolor;?>;
	}
	#ff-stream-<?php echo $id;?>,
	#ff-stream-<?php echo $id;?>-slideshow {
		color: <?php echo $stream->textcolor;?>;
	}
	#ff-stream-<?php echo $id;?> li,
	#ff-stream-<?php echo $id;?>-slideshow li,
	#ff-stream-<?php echo $id;?> .ff-square {
		background: <?php echo $stream->cardcolor;?>;
	}
	#ff-stream-<?php echo $id;?> .ff-icon, #ff-stream-<?php echo $id;?>-slideshow .ff-icon {
		border-color: <?php echo $stream->cardcolor;?>;
	}
	#ff-stream-<?php echo $id;?>  a, #ff-stream-<?php echo $id;?>-slideshow  a{
		color: <?php echo $stream->linkscolor;?>;
	}

	#ff-stream-<?php echo $id;?> h4, #ff-stream-<?php echo $id;?>-slideshow h4,
	#ff-stream-<?php echo $id;?> .ff-name, #ff-stream-<?php echo $id;?>-slideshow .ff-name {
		color: <?php echo $stream->namecolor;?> !important;
	}

	#ff-stream-<?php echo $id;?> .ff-mob-link:hover{
		background-color: <?php echo $stream->namecolor;?>;
	}
	#ff-stream-<?php echo $id;?> .ff-nickname,
	#ff-stream-<?php echo $id;?>-slideshow .ff-nickname,
	#ff-stream-<?php echo $id;?> .ff-timestamp,
	#ff-stream-<?php echo $id;?>-slideshow .ff-timestamp {
		color: <?php echo $stream->restcolor;?> !important;
	}
	#ff-stream-<?php echo $id;?> .ff-item {
		text-align: <?php echo $stream->talign;?>;
	}
	#ff-stream-<?php echo $id;?> .ff-item-meta,
	#ff-stream-<?php echo $id;?>-slideshow .ff-item-meta {
		border-color: <?php echo $stream->bcolor;?>;
	}
	<?php
		if(!empty($stream->css)) echo $stream->css;
	?>
</style>
<div class="ff-stream" id="ff-stream-<?php echo $id;?>"><span class="ff-loader"><span class="ff-square" ></span><span class="ff-square"></span><span class="ff-square ff-last"></span><span class="ff-square ff-clear"></span><span class="ff-square"></span><span class="ff-square ff-last"></span><span class="ff-square ff-clear"></span><span class="ff-square"></span><span class="ff-square ff-last"></span></span></div>
<script type="text/javascript">
	var _ajaxurl = '<?php echo admin_url('admin-ajax.php');?>';
	(function ( $ ) {
		"use strict";
		if (/MSIE 8/.test(navigator.userAgent)) return;
		var opts = window.FlowFlowOpts;
		if (!opts) {
			window.console && window.console.log('no Flow-Flow options');
			return;
		}
		if (!window.FF_resource) {
			window.console && window.console.log('no required script');
			return
		}
		var data = {
			'action': 'fetch_posts',
			'stream-id': '<?php echo $stream->id;?>',
			'disable-cache': '<?php echo $disableCache;?>'};
		var isMobile = /android|blackBerry|iphone|ipad|ipod|opera mini|iemobile/i.test(navigator.userAgent);
		var streamOpts = opts['streams']['id' + data['stream-id']];
		var $cont = $("#ff-stream-"+data['stream-id']);
		var ajaxDeferred;
		var script, style;
		if (FF_resource.scriptDeferred.state() === 'pending' && !FF_resource.scriptLoading) {
			script = document.createElement('script');
			script.src = "<?php echo plugins_url();?>/flow-flow-social-streams/js/public.js";
			script.onload = function( script, textStatus ) {
				FF_resource.scriptDeferred.resolve();
			};
			document.body.appendChild(script);
			FF_resource.scriptLoading = true;
		}
		if (FF_resource.styleDeferred.state() === 'pending' && !FF_resource.styleLoading) {
			style = document.createElement('link');
			style.type = "text/css";
			style.rel = "stylesheet";
			style.href = "<?php echo plugins_url();?>/flow-flow-social-streams/css/public.css";
			style.media = "screen";
			style.onload = function( script, textStatus ) {
				FF_resource.styleDeferred.resolve();
			};
			document.getElementsByTagName("head")[0].appendChild(style);
			FF_resource.styleLoading = true;
		}
		$cont.addClass('ff-layout-' + streamOpts.layout)
		if (!isMobile) $cont.css('minHeight', '1000px');
		ajaxDeferred = $.get(_ajaxurl, data);
		$.when( ajaxDeferred, FF_resource.scriptDeferred, FF_resource.styleDeferred ).done(function ( data ) {
			var response, $stream;
			var $errCont, errs, err;
			var num = (streamOpts.mobileslider === 'yep' && isMobile) ? (streamOpts.mobileslider === 'yep' ? 3 : streamOpts['cards-num']) : false;
			var w;

			try {
				response = JSON.parse(data[0]);
			} catch (e) {
				window.console && window.console.log('Flow-Flow gets invalid data from server');
				if (opts.isAdmin || opts.isLog) {
					$errCont = $('<div class="ff-errors" id="ff-errors-invalid-response"><div class="ff-disclaim">If you see this then you\'re administrator and Flow-Flow got invalid data from server. Please provide error message below if you\'re doing support request.</div><div class="ff-err-info"></div></div>')
					$cont.before($errCont);
					$errCont.find('.ff-err-info').html(data[0] == '' ? 'Empty response from server' : data[0])
				}
				return;
			}

			$stream = FlowFlow.buildStreamWith(response);

			$cont.append($stream);
			if (typeof $stream !== 'string') {
				FlowFlow.setupGrid($cont.find('.ff-stream-wrapper'), num, streamOpts.scrolltop === 'yep', streamOpts.gallery === 'yep', streamOpts, $cont);
			}
			setTimeout(function(){
				$cont.find('.ff-header').removeClass('ff-loading').end().find('.ff-loader').addClass('ff-squeezed');
			}, 0);
			if ((opts.isAdmin || opts.isLog ) && response.errors && response.errors.length) {
				$errCont = $('<div class="ff-errors" id="ff-errors-'+response.id+'"><div class="ff-disclaim">If you see this then you\'re administrator and Flow-Flow got next errors from API. Please provide this info if you\'re doing support request.</div><div class="ff-err-info"></div></div>')
				$cont.before($errCont);
				errs = '', err;
				for (var i = 0, len = response.errors.length;i < len; i++) {
					err = response.errors[i];
					if (typeof err === 'string') {
						errs += '<p>' + err + '</p>';
					} else if (typeof err  === 'object') {
						if (err['type'] && err['message']) {
							errs += '<p>From: ' + (err['type'] ? err['type'].toUpperCase() : 'Unknown') + '<br>' + err['message'] + '</p>';
						}
						else if ($.isArray(err)) {
							for (var j = 0, _l = err.length; j < _l; j++ ) {
								errs += '<p>' + err[j] + '</p>';
							}
						}
					}
				}
				$errCont.find('.ff-err-info').html(errs)
			}
		});
		return false;
	}(jQuery));
</script>
<!-- Flow-Flow — Social streams plugin for Wordpress -->