<?php if (!defined('ABSPATH')) {
	exit;
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1"/>
	<style>
		body{
			margin: 0px!important;
		}
		iframe, object, embed {
			max-width: 100%!important;
			margin: 0 auto;
			display: block;
			overflow-y: hidden !important;
			overflow-x: hidden !important;
		}
		#av-social-share.av-social-type-text-icon {
			display: none;
		}
		.av-social-btn {
			display: none;
		}
		#av_toolbar_iframe, #av_toolbar_regdiv {
			display: none;
		}
		.toolbar{
			position: fixed;
			z-index: 9999999;
			width: 100%;
			height: 56px;
			max-height: 56px;
			top: 0px;
			background-color: white;
			border-bottom: 1px solid #eee;
		}
		.blogtitle{
			height: 56px;
			max-height: 32px;
			font-size: 24px;
			margin-top: 14px;
			overflow: hidden;
			text-overflow: ellipsis;
		}
		.title{
			text-align: center;
			margin-top: 56px;
			padding-top: 16px;
		}
		.toolbar-left{
			width: 15%;
			height: 56px;
			line-height: 56px;
			float: left;
		}
		.toolbar-center{
			position: absolute;
			text-align: center;
			height: 56px;
			left: 15%;
			right: 15%;
		}
		.back{
			font-size: 24px;
			margin-left: 8px;
		}
	</style>
</head>

<body>
	<div class="toolbar">
		<div class="toolbar-left" onclick="window.close();">
			<img src="<?php echo plugin_dir_url(__FILE__); ?>assets/back.png" style="margin-top: 12px;margin-left: 8px;">
		</div>
		<div class="toolbar-center">
			<h2 id="blogtitle" class="blogtitle"></h2>
		</div>
	</div>
	<h2 id="title" class="title"></h2>
	<br>
	<!--<div class="fb-like" data-share="true" data-width="100%" data-show-faces="true">-->
</div>

<div id="fb-root"></div>
<div id="fb-comments" class="fb-comments" data-href="" data-numposts="5" data-width="100%" style="margin: 0px!important;max-width: 100%!important;overflow:hidden!important;"></div>
<script type="text/javascript">
	var params;
	var fb_url;
	var fb_id;
	window.onload = function () {
		var match,
		pattern = /\+/g,
		search = /([^&=]+)=?([^&]*)/g,
		decode = function (s) { return decodeURIComponent(s.replace(pattern, " ")); },
		query = window.location.search.substring(1);
		params = {};
		while (match = search.exec(query))
			params[decode(match[1])] = decode(match[2]);
		if (params["appid"] === undefined || params["url"] === undefined || params["title"] === undefined || params["blogtitle"] === undefined) {
			alert("Required arguments missing");
		}
		else {
			loadComments(params["appid"], params["url"], params["title"], params["blogtitle"]);
		}
	};
	function loadComments(appid, url, title, blogtitle) {
		fb_url = url;
		fb_id = appid;
		document.getElementById("blogtitle").innerHTML += blogtitle;
		document.getElementById("title").innerHTML += title;
		document.getElementById("fb-comments").setAttribute("data-href",fb_url);

		window.fbAsyncInit = function() {
			FB.init({
				appId      : fb_url,
				xfbml      : true,
				version    : 'v2.5'
			});
		};

		(function(d, s, id){
			var js, fjs = d.getElementsByTagName(s)[0];
			if (d.getElementById(id)) {return;}
			js = d.createElement(s); js.id = id;
			js.src = "//connect.facebook.net/en_US/sdk.js";
			fjs.parentNode.insertBefore(js, fjs);
		}(document, 'script', 'facebook-jssdk'));

	}
</script>
</body>

</html>
