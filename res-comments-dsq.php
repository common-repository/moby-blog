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
	<h2 id="title" class="title" ></h2>
	<br>
	<div id="disqus_thread" style="margin: 8px;">
	</div>
	<script type="text/javascript">
		var params;
		var disqus_url;
		var disqus_title;
		var disqus_shortname;
		var disqus_identifier;
		window.onload = function () {
			var match,
			pattern = /\+/g,
			search = /([^&=]+)=?([^&]*)/g,
			decode = function (s) { return decodeURIComponent(s.replace(pattern, " ")); },
			query = window.location.search.substring(1);
			params = {};
			while (match = search.exec(query))
				params[decode(match[1])] = decode(match[2]);
			if (params["shortname"] === undefined || params["url"] === undefined || params["title"] === undefined || params["identifier"] === undefined || params["blogtitle"] === undefined) {
				alert("Required arguments missing");
			}
			else {
				loadComments(params["shortname"], params["url"], params["title"], params["identifier"], params["blogtitle"]);
			}
		};
		function loadComments(shortname, url, title, identifier, blogtitle) {
			disqus_url = url;
			disqus_title = title;
			disqus_shortname = shortname;
			document.getElementById("blogtitle").innerHTML += blogtitle;
			document.getElementById("title").innerHTML += title;
			if (identifier !== undefined)
				disqus_identifier = identifier;
			else
				disqus_identifier = "";
			(function() {
				var dsq = document.createElement('script'); dsq.type = 'text/javascript'; dsq.async = false;
				dsq.src = 'http://' + disqus_shortname + '.disqus.com/embed.js';
				(document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(dsq);
			})();
		}
	</script>
	<noscript>
		Please enable JavaScript to view the
		<a href="http://disqus.com/?ref_noscript">
			comments powered by Disqus.
		</a>
	</noscript>
	<a  style="text-align: center; margin: 0 auto;" class="dsq-brlink">
		Wait until comments loading
		<span class="logo-disqus">

		</span>
	</a>
</body>

</html>
