<?php
/*
 *Plugin Name: Moby Blog
 *Plugin URI: http://www.mobyblogapp.com
 *Description: Moby Blog - One APP for All Your Wordpress Blog! FREE! Are you a Blogger? Have a WordPress Blog? Turn it for free into a user friendly app for smartphones and tablets in few minutes and Boost users and mobile visits by 25-60%! Moby Blog is a completely free mobile app that allows you to make your blog optimized for viewing on mobile devices. It allows your users to read the latest news from your blog through a user friendly app available for Android and iOS, at no extra charge. Only few minutes to activate!
 *Version: 1.1.6
 *Author: Restart Labs SRLS
 *Author URI: http://www.restartlabs.org/
 *Text Domain: moby-blog
 *Domain Path: /languages
 */
if (!defined('ABSPATH')) {
    exit;
}

$dir = res_json_api_dir();
@include_once "$dir/singletons/api.php";
@include_once "$dir/singletons/query.php";
@include_once "$dir/singletons/introspector.php";
@include_once "$dir/singletons/response.php";
@include_once "$dir/models/post.php";
//@include_once "$dir/models/comment.php";
@include_once "$dir/models/category.php";
@include_once "$dir/models/tag.php";
@include_once "$dir/models/author.php";
@include_once "$dir/models/attachment.php";
require_once 'res_cache.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
@include_once "$dir/singletons/resBanners.php";

function res_json_api_init()
{
    global $res_json_api;
    global $res_cache;
    global $dir;
    global $res_cache_dir;
    global $res_version;
    $res_version = '1.1.5';

    if (phpversion() < 5) {
        add_action('admin_notices', 'res_json_api_php_version_warning');
        return;
    }
    if (!class_exists('RES_JSON_API')) {
        add_action('admin_notices', 'res_json_api_class_warning');
        return;
    }
    add_filter('rewrite_rules_array', 'res_json_api_rewrites');
    $res_json_api = new RES_JSON_API();

    $res_cache_dir = $dir . '_cache/';

    $res_cache = new RES_Cache(array(
        'name'      => 'MOBY BLOG CACHE',
        'path'      => $res_cache_dir,
        'extension' => '.mobyblogcache',
    ));
//$res_cache -> eraseExpired();
    if (function_exists('add_image_size')) {

        add_image_size('mobyblogmedium', 640, 320, true);
        add_image_size('mobybloglarge', 1280, 720, true);
    }
}

function res_json_api_php_version_warning()
{
    echo "<div id=\"res-json-api-warning\" class=\"updated fade\"><p>Sorry, Moby Blog requires PHP version 5.0 or greater.</p></div>";
}

function res_json_api_class_warning()
{
    echo "<div id=\"res-json-api-warning\" class=\"updated fade\"><p>Oops, RES_JSON_API class not found. If you've defined a RES_JSON_API_DIR constant, double check that the path is correct.</p></div>";
}


function res_json_api_activation()
{
    // Add the rewrite rule on activation
    global $wp_rewrite;
    add_filter('rewrite_rules_array', 'res_json_api_rewrites');
    $wp_rewrite->flush_rules();
}

function res_json_api_deactivation()
{
    // Remove the rewrite rule on deactivation
    global $wp_rewrite;
    global $res_cache;
    global $res_cache_dir;
    $wp_rewrite->flush_rules();
    $res_cache->eraseAll();
}

function res_json_api_rewrites($wp_rules)
{
    $base = "res-api"; //get_option('res_json_api_base', 'res-api');
    if (empty($base)) {
        return $wp_rules;
    }
    $res_json_api_rules = array(
        "$base\$"      => 'index.php?res-json=info',
        "$base/(.+)\$" => 'index.php?res-json=$matches[1]',
    );
    return array_merge($res_json_api_rules, $wp_rules);
}

function res_json_api_dir()
{
    if (defined('RES_JSON_API_DIR') && file_exists(RES_JSON_API_DIR)) {
        return RES_JSON_API_DIR;
    } else {
        return dirname(__FILE__);
    }
}

// Add initialization and activation hooks
add_action('init', 'res_json_api_init');
register_activation_hook("$dir/res-json-api.php", 'res_json_api_activation');
register_deactivation_hook("$dir/res-json-api.php", 'res_json_api_deactivation');

add_action('init', 'wpse9870_init_internal');
function wpse9870_init_internal()
{
    add_rewrite_rule('res-post.php$', 'index.php?res-post=1', 'top');
    add_rewrite_rule('res-comments-dsq.php$', 'index.php?res-comments-dsq=1', 'top');
    add_rewrite_rule('res-comments-fb.php$', 'index.php?res-comments-fb=1', 'top');
}

add_filter('query_vars', 'wpse9870_query_vars');
function wpse9870_query_vars($query_vars)
{
    $query_vars[] = 'res-post';
    return $query_vars;
}

add_filter('query_vars', 'wpse9870_query_vars_2');
function wpse9870_query_vars_2($query_vars)
{
    $query_vars[] = 'res-comments-dsq';
    return $query_vars;
}

add_filter('query_vars', 'wpse9870_query_vars_3');
function wpse9870_query_vars_3($query_vars)
{
    $query_vars[] = 'res-comments-fb';
    return $query_vars;
}

add_action('parse_request', 'wpse9870_parse_request');
function wpse9870_parse_request(&$wp)
{
    if (array_key_exists('res-post', $wp->query_vars)) {
        include 'res-post.php';
        exit();
    }
    if (array_key_exists('res-comments-dsq', $wp->query_vars)) {
        include 'res-comments-dsq.php';
        exit();
    }
    if (array_key_exists('res-comments-fb', $wp->query_vars)) {
        include 'res-comments-fb.php';
        exit();
    }
    return;
}


function my_enqueue($hook)
{
    wp_enqueue_script('resjsfunc', plugin_dir_url(__FILE__) . 'singletons/data/resjsfunc.js');

}
add_action('admin_enqueue_scripts', 'my_enqueue');
