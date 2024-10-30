<?php
require_once 'res_cache.php';

global $res_json_api;

if (!defined('ABSPATH')) {
    exit;
}

$postId = "";
if (isset($_GET["post_id"])) {
    $postId = intval($_GET["post_id"]);
}

if (defined('RES_JSON_API_DIR') && file_exists(RES_JSON_API_DIR)) {
    $dir = RES_JSON_API_DIR;
} else {
    $dir = dirname(__FILE__);
}
$res_cache_dir = $dir . '_cache/';
$res_cache     = new RES_Cache(array(
    'name'      => 'MOBY BLOG CACHE',
    'path'      => $res_cache_dir,
    'extension' => '.mobyblogcache',
));
$res_version = '1.1.5';
$url_md5     = md5('res_post_' . $postId . '_' . $res_version);
$post        = null;
$post        = $res_cache->retrieve($url_md5);
$home        = get_bloginfo('url');
if ($post == null) {
    $post    = get_post($postId);
    $content = $post->post_content;
    if (!empty($content)) {
        try {
            $newDom = new DOMDocument();
            libxml_use_internal_errors(true);
            $newDom->loadHTML($content);
            $aTag = $newDom->getElementsByTagName('a');
            $aUrl = '';
            foreach ($aTag as $aTag1) {
                $t = $aTag1->getAttribute('href');
                if (preg_match('#^' . $home . '#i', $t) == 1) {
                    if (preg_match('/\.(jpg|jpeg|png|gif)(?:[\?\#].*)?$/i', $t, $matches)) {
                        $content = str_replace('href="' . $t . '"', '', $content);
                    } else {
                        $currentId = url_to_postid($t);
                        $aUrl      = 'href="mobyblog://' . $currentId . '|' . $home . '/index.php?res-post=1&post_id=' . $currentId . '"';
                        $content   = str_replace('href="' . $t . '"', $aUrl, $content);
                    }
                }
            }
        } catch (Exception $e) {
            echo '<!-- DOM Catch ' . $e->getMessage() . ' -->';
            $content = $post->post_content;
        }

        try {
            preg_match("/\[embed\].+?\[\/embed\]/", $content, $embeds);
            foreach ($embeds as $embed) {
                $embed     = $embed[0];
                $embed_url = preg_replace("/\[embed\]/", "", $embed);
                $embed_url = preg_replace("/\[\/embed\]/", "", $embed_url);
                $content   = str_replace($embed, $embed_url, $content);
            }
        } catch (Exception $e) {
            echo '<!-- EMBED Catch ' . $e->getMessage() . ' -->';
            $content = $post->post_content;
        }

        try {

            preg_match_all("/(https?|www?|youtube\.com)(?:.+?)?(?:\/v\/|watch\/|\?v=|\&v=|youtu\.be\/|\/v=|^youtu\.be\/)([a-zA-Z0-9_-]{11})(.*)/i", $content, $embeds);

            $videoUrls  = $embeds[0];
            $videoIds   = $embeds[2];
            $videoCount = count($videoIds);

            $youtubeFirst  = '<iframe width="300" src="http://www.youtube.com/embed/';
            $youtubeSecond = '"></iframe>';
            $youtube       = '';
            for ($i = 0; $i <= $videoCount - 1; $i++) {
                if (!startsWith($videoUrls[$i], '[embed]')) {
                    $youtube = $youtubeFirst . $videoIds[$i] . $youtubeSecond;
                    $content = str_replace($videoUrls[$i], $youtube, $content);
                    $youtube = '';
                }
            }

        } catch (Exception $e) {
            echo '<!-- Last Youtube Catch ' . $e->getMessage() . ' -->';
            $content = $post->post_content;
        }

        try {
            $content = do_shortcode($content);
            $content = apply_filters('the_content', $content);
            //$content = wp_strip_all_tags($content);
        } catch (Exception $e) {
            echo '<!-- FILTERS Catch ' . $e->getMessage() . ' -->';
            $content = $post->post_content;
        }

        try {
            $content = preg_replace("/\[(.+?)\]/", "", $content);
        } catch (Exception $e) {
            echo '<!-- Strip Catch ' . $e->getMessage() . ' -->';
            $content = $post->post_content;
        }

        $post->post_content = $content;
        $res_cache->eraseExpired();
        $res_cache->store($url_md5, $post, 300);
    }

} else {
    $content = $post->post_content;
}
?>
<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">
<html>
<head>
<title><?php echo htmlspecialchars($post->post_title, ENT_QUOTES); ?></title>
    <meta name="robots" content="noindex,nofollow"/>
    <meta content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport"/>
    <meta http-equiv="Content-Security-Policy" content="default-src *; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline' 'unsafe-eval' *">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link rel="canonical" href="<?php echo get_permalink($postId); ?>" />
<style>
    .content-body,body{margin:0!important;padding:0!important}.content-body,.fb-comments,div,embed,iframe,img,object{max-width:100%!important}#content{text-align:justify!important}.content-body{right:8px;left:8px;position:absolute;top:0;background-color:#fff;z-index:99}img{height:auto;margin:8px auto;display:block}.aligncenter{margin-left:auto;margin-right:auto;display:block}#av-social-share.av-social-type-text-icon,#av_toolbar_iframe,#av_toolbar_regdiv,.av-social-btn{display:none}embed,iframe,object{margin:0 auto;display:block;overflow-y:hidden!important;overflow-x:hidden!important}body{margin-left:16px!important;margin-right:16px!important}

</style>
<?php
$res_js      = get_option('res_js');
$res_js_code = get_option('res_js_code');
$res_js_import      = get_option('res_js_import');
$res_js_import_code = get_option('res_js_import_code');

if ($res_js == 'yes' && isset($res_js_code)) {
    echo '<script>' . html_entity_decode($res_js_code, ENT_QUOTES, 'UTF-8') . '</script>';
}
if ($res_js_import == 'yes' && isset($res_js_import_code)) {
    echo '<script type="text/javascript" src="' . html_entity_decode($res_js_import_code, ENT_QUOTES, 'UTF-8') . '"></script>';
}
?>
</head>
<body style="font-family: 'Roboto', sans-serif !important;">
    <div id="content" class="content-body" role="main" align="justify">
        <?php

echo "<!-- post -->";
$cover_img = null;
try {
    if (function_exists('get_post_thumbnail_id')) {
        $post_thumbnail_id = get_post_thumbnail_id($postId);
        if (isset($post_thumbnail_id) && $post_thumbnail_id != '') {
            $sizes = array('mobyblogmedium', 'medium', 'full');
            $count = 0;
            foreach ($sizes as $size) {
                if ($count == 0) {
                    $r = wp_get_attachment_image_src($post_thumbnail_id, $size);
                    if (isset($r) && $r != false && $r != 'false') {
                        $count++;
                        $cover_img = $r[0];
                    }
                }
            }
        }
        //echo '<!-- COVER: ' . $cover_img. '-->';
    }

} catch (Exception $e) {
    echo '<!-- Thumbnail Catch ' . $e->getMessage() . ' -->';
}

if (!isset($cover_img)) {
    //echo "<!-- INSIDE COVER IMG -->";
    try {
        preg_match("/https?:\/\/\S+(?:png|jpg|jpeg)/", $content, $cover_img);
        if (isset($cover_img)) {
            $cover_img = $cover_img[0];
        } else {
            $cover_img = 'undefined';
        }
    } catch (Exception $e) {
        echo '<!-- Cover Catch ' . $e->getMessage() . ' -->';
    }
}
echo $content;
$opt_md5 = md5('res_post_settings_' . $res_version);
$opts    = $res_cache->retrieve($opt_md5);
if ($opts == null) {
    // echo "NO CACHE";
    $res_wp_comments            = get_option('res_wp_comments');
    $res_fb_comments            = get_option('res_fb_comments');
    $res_fb_comments_appid      = get_option('res_fb_comments_appid');
    $res_dsq_comments           = get_option('res_dsq_comments');
    $res_dsq_comments_shortname = get_option('res_dsq_comments_shortname');
    $opts_array                 = array(
        'res_wp_comments'            => $res_wp_comments,
        'res_fb_comments'            => $res_fb_comments,
        'res_fb_comments_appid'      => $res_fb_comments_appid,
        'res_dsq_comments'           => $res_dsq_comments,
        'res_dsq_comments_shortname' => $res_dsq_comments_shortname,
    );
    $res_cache->eraseExpired();
    $res_cache->store($opt_md5, $opts_array, 600);
} else {
    // echo "CACHE";
    $res_wp_comments            = $opts['res_wp_comments'];
    $res_fb_comments            = $opts['res_fb_comments'];
    $res_fb_comments_appid      = $opts['res_fb_comments_appid'];
    $res_dsq_comments           = $opts['res_dsq_comments'];
    $res_dsq_comments_shortname = $opts['res_dsq_comments_shortname'];
}

$fbString     = '';
$dsqString    = '';
$author       = new stdClass();
$author->id   = (int) $post->post_author;
$author->name = htmlspecialchars(get_the_author_meta('display_name', $author->id), ENT_QUOTES);
//$content = stristr($content, '<html>');

?>
</div>
<script>
    var x=document.getElementsByTagName("a"),i;for(i=0;i<x.length;i++)x[i].addEventListener("click",function(a){a.preventDefault();var b=a.srcElement.href;if(b&&b.startsWith("mobyblog://"))var c="link-in|"+b.replace("mobyblog://","");else var c="link-out|<?php echo $postId; ?>|"+a.srcElement.href;parent.postMessage(c,"*")});var x=[],x=document.getElementsByTagName("img"),i;for(i=0;i<x.length;i++)x[i].addEventListener("click",function(a){a.preventDefault();var b="img|<?php echo $postId; ?>|"+a.srcElement.src;parent.postMessage(b,"*")});
</script>
<?php
$blog_title = get_bloginfo('name');
if ($res_fb_comments == 'yes' && $res_fb_comments_appid != '' && $res_fb_comments_appid != ' ') {
    $fbString = $home . '/index.php?res-comments-fb=1&url=' . urlencode(get_permalink($postId)) . '&appid=' . $res_fb_comments_appid . '&title=' . urlencode($post->post_title) . '&blogtitle=' . urlencode($blog_title);
}
if ($res_dsq_comments == 'yes' && $res_dsq_comments_shortname != '' && $res_dsq_comments_shortname != ' ') {
    $dsqString = $home . '/index.php?res-comments-dsq=1&url=' . urlencode(get_permalink($postId)) . '&identifier=' . (int) $postId . '&shortname=' . urlencode($res_dsq_comments_shortname) . '&title=' . urlencode($post->post_title) . '&blogtitle=' . urlencode($blog_title);
}
$tags       = wp_get_post_tags($postId);
$categories = wp_get_post_categories($postId);
//var_dump($tags);
function startsWith($haystack, $needle)
{
    // search backwards starting from haystack length characters from the end
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
}

?>
<script>
    parent.postMessage("id|<?php echo $postId; ?>|<?php echo $postId; ?>" ,'*');
    parent.postMessage("title|<?php echo $postId; ?>|<?php echo htmlspecialchars($post->post_title, ENT_QUOTES); ?>" ,'*');
    parent.postMessage("cover_img|<?php echo $postId; ?>|<?php echo $cover_img; ?>" ,'*');
    parent.postMessage("permalink|<?php echo $postId; ?>|<?php echo get_permalink($postId); ?>" ,'*');
    parent.postMessage("time|<?php echo $postId; ?>|<?php echo get_the_time('U', $postId); ?>" ,'*');
    parent.postMessage("author_name|<?php echo $postId; ?>|<?php echo $author->name; ?>" ,'*');
    parent.postMessage("author_description|<?php echo $postId; ?>|<?php echo $author->description; ?>" ,'*');
    parent.postMessage("author_url|<?php echo $postId; ?>|<?php echo $author->url; ?>" ,'*');
    parent.postMessage("tags|<?php echo $postId; ?>|<?php echo htmlspecialchars(json_encode($tags), ENT_QUOTES); ?>" ,'*');
    parent.postMessage("cats|<?php echo $postId; ?>|<?php echo htmlspecialchars(json_encode($categories), ENT_QUOTES); ?>" ,'*');
    parent.postMessage("fbString|<?php echo $postId; ?>|<?php echo $fbString; ?>" ,'*');
    parent.postMessage("dsqString|<?php echo $postId; ?>|<?php echo $dsqString; ?>" ,'*');
</script>

</div><!-- #content -->
</body>
<?php

$res_analytics    = get_option('res_analytics');
$res_analytics_id = get_option('res_analytics_id');

if ($res_analytics == 'yes' && isset($res_analytics_id)) {
    include_once "library/analytics.php";
    ?>
<script>
  ga('set', 'location', '<?php echo get_permalink($postId); ?>');
  ga('send', 'pageview');
  console.log("After GA");
</script>
<?php
}
?>
</html>