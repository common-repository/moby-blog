<?php
if (!defined('ABSPATH')) {
    exit;
}

class RES_JSON_API
{

    const TEXTDOMAIN = 'MobyBlog';
    const MENU_SLUG  = 'MobyBlog';

    public function __construct()
    {
        $this->query        = new RES_JSON_API_Query();
        $this->introspector = new RES_JSON_API_Introspector();
        $this->response     = new RES_JSON_API_Response();
        add_action('template_redirect', array(&$this, 'template_redirect'));
        add_action('admin_menu', array(&$this, 'admin_menu'));
        add_action('update_option_res_json_api_base', array(&$this, 'flush_rewrite_rules'));
        add_action('pre_update_option_res_json_api_controllers', array(&$this, 'update_controllers'));
    }

    public function template_redirect()
    {
        // Check to see if there's an appropriate API controller + method
        $controller            = strtolower($this->query->get_controller());
        $available_controllers = $this->get_controllers();
        $enabled_controllers   = explode(',', get_option('res_json_api_controllers', 'core'));
        $active_controllers    = array_intersect($available_controllers, $enabled_controllers);

        if ($controller) {

            if (empty($this->query->dev)) {
                error_reporting(0);
            }

            if (!in_array($controller, $active_controllers)) {
                $this->error("Unknown controller '$controller'.");
            }

            $controller_path = $this->controller_path($controller);
            if (file_exists($controller_path)) {
                require_once $controller_path;
            }
            $controller_class = $this->controller_class($controller);

            if (!class_exists($controller_class)) {
                $this->error("Unknown controller '$controller_class'.");
            }

            $this->controller = new $controller_class();
            $method           = $this->query->get_method($controller);

            if ($method) {

                $this->response->setup();

                // Run action hooks for method
                do_action("res_json_api", $controller, $method);
                do_action("res_json_api-{$controller}-$method");

                // Error out if nothing is found
                if ($method == '404') {
                    $this->error('Not found');
                }

                // Run the method
                $result = $this->controller->$method();

                // Handle the result
                $this->response->respond($result);

                // Done!
                exit;
            }
        }
    }

    public function admin_menu()
    {

        add_menu_page(
            "Moby Blog", "Moby Blog App",
            'read',
            self::MENU_SLUG,
            array($this, 'admin_options'), plugins_url('moby-blog/singletons/data/mobyblog-20.png'), 64);
        //add_submenu_page(self::MENU_SLUG, "All you need to know about Moby Blog", "About", "read", self::MENU_SLUG, array($this, 'about'));
        //add_submenu_page(self::MENU_SLUG, "Moby Blog Settings", "Settings", "read", self::MENU_SLUG.'-settings', array($this, 'admin_options'));

    }

    private function buildScreen($screen)
    {
        add_screen_option('layout_columns', array('max' => 2, 'default' => 2));
        ?>
    <div class="wrap">
      <h2>Moby Blog</h2>
      <div id="poststuff">
       <?php $actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";?>
       <form action="<?php echo $actual_link ?>" method="post">
        <?php wp_nonce_field('update-options');?>
        <div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">
         <div id="postbox-container-2" class="postbox-container">
          <?php do_meta_boxes($screen, 'normal', $screen);?>
        </div>
        <div id="postbox-container-1" class="postbox-container">
          <?php do_meta_boxes($screen, 'side', $screen);?>
        </div>

      </div>
    </form>
  </div>
</div>
<?php
}

    public function admin_options()
    {

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $screen = WP_Screen::get();

        add_meta_box(
            'mobyblog-notice' . $screen->id,
            __('Register', self::TEXTDOMAIN),
            array($this, 'noticePlugin'),
            $screen, 'normal', 'high'
        );

        add_meta_box(
            'mobyblog-categories' . $screen->id,
            __('Select Allowed Categories', self::TEXTDOMAIN),
            array($this, 'categoriesPlugin'),
            $screen, 'normal', 'high'
        );

        add_meta_box(
            'mobyblog-fbcomments' . $screen->id,
            __('Facebook Comments', self::TEXTDOMAIN),
            array($this, 'facebookCommentsPlugin'),
            $screen, 'normal', 'high'
        );

        add_meta_box(
            'mobyblog-dsqcomments' . $screen->id,
            __('Disqus Comments', self::TEXTDOMAIN),
            array($this, 'disqusCommentsPlugin'),
            $screen, 'normal', 'high'
        );

        add_meta_box(
            'mobyblog-banner' . $screen->id,
            __('App Banner', self::TEXTDOMAIN),
            array($this, 'bannerPlugin'),
            $screen, 'side', 'high'
        );

        add_meta_box(
            'mobyblog-analytics' . $screen->id,
            __('Google Analytics', self::TEXTDOMAIN),
            array($this, 'analyticsPlugin'),
            $screen, 'side', 'high'
        );

        add_meta_box(
            'mobyblog-js' . $screen->id,
            __('Add Custom Javascript', self::TEXTDOMAIN),
            array($this, 'jsPlugin'),
            $screen, 'normal', 'high'
        );

        add_meta_box(
            'mobyblog-save' . $screen->id,
            __('Save Settings', self::TEXTDOMAIN),
            array($this, 'saveSettingsPlugin'),
            $screen, 'side', 'high'
        );
        add_meta_box(
            'mobyblog-save2' . $screen->id,
            __('Save Settings', self::TEXTDOMAIN),
            array($this, 'saveSettingsPlugin'),
            $screen, 'normal', 'low'
        );

        $this->buildScreen($screen);

        ?>

<?php
}

    public function saveSettingsPlugin()
    {

        ?>

  <?php submit_button();?>

  <?php

    }

    public function noticePlugin()
    {

        ?>
  <center><img style="height:48pt" src="<?php echo plugin_dir_url(__FILE__); ?>data/mobyblog-128.png"></center>
  <p>To use Moby Blog App Plugin please register your blog on <a target="_blank" href="http://www.mobyblogapp.com">Moby Blog Website</a></p>

  <?php

    }

    public function categoriesPlugin()
    {
        $blockedCats = get_option('res_disallowed_cats', '');
        if (!empty($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'], "update-options")) {

            if (!empty($_POST)) {
                //var_dump($_POST);
                $res_blocked_cats = isset($_POST['res_blocked_cats']) ? sanitize_text_field($_POST['res_blocked_cats']) : '';
                $res_allowed_cats = isset($_POST['res_allowed_cats']) ? sanitize_text_field($_POST['res_allowed_cats']) : null;
                if ($res_blocked_cats == '' && $res_allowed_cats == null) {

                } else {
                    $this->save_option('res_disallowed_cats', $res_blocked_cats);
                    $blockedCats = $res_blocked_cats; //explode(',', $res_blocked_cats)
                }

            }
        }

        ?>
<style>


.res_categories_buttons_div {
 padding-left: 16px;
 padding-right: 16px;
 text-align: center;
}

@media screen and (max-width: 784px){
  .res_settings_select {
    min-height: 80px;
  }
}


</style>
<input type="hidden" name="res_blocked_cats" id="res_blocked_cats" value="" />
By this settings you can disallow all post of some categories to be shown and searched through the Moby Blog App
<table style="width:100%;">
  <tr>
    <td><h3><strong><center>Allowed Categories:</center></strong></h3></td>
    <td></td>
    <td><h3><strong><center>Disallowed Categories:</center></strong></h3></td>
  </tr>
  <tr>
    <td style="width:40%;">
      <div >

        <select class="res_settings_select" id="allowed_cats" name="res_allowed_cats" multiple="multiple" size="10"  style="width:100%;">
          <?php

        $args = array(
            'orderby' => 'name',
            'parent'  => 0,
            'exclude' => $blockedCats,
        );

        $categories         = get_categories($args);
        $allowed_cats_ids   = array();
        $allowed_cats_names = array();

        foreach ($categories as $category) {
            array_push($allowed_cats_ids, $category->term_id);
            array_push($allowed_cats_names, $category->name);
        }

        foreach ($allowed_cats_names as $key => $v) {
            ?>
           <option value="<?=$allowed_cats_ids[($key)];?>"><?=$v?></option>
           <?php
}
        ?>
       </select>
     </div>
   </td>
   <td style="width:20%;">
    <div class="res_categories_buttons_div">
      <input name="block" type="button" value=">>"  onClick="blockCat()"/><br>
      <input name="unblock" type="button" value="<<"  onClick="unblockCat()"/>
    </div>
  </td >
  <td style="width:40%;">
    <div  >
      <select class="res_settings_select" id="disallowed_cats" name="res_disallowed_cats" multiple="multiple" size="10" style="width:100%;">
        <?php
$args2 = array(
            'orderby' => 'name',
            'parent'  => 0,
            'include' => $blockedCats,
        );
        if ($blockedCats != '' && $blockedCats != ' ') {
            $categories2           = get_categories($args2);
            $disallowed_cats_ids   = array();
            $disallowed_cats_names = array();
            foreach ($categories2 as $category2) {
                array_push($disallowed_cats_ids, $category2->term_id);
                array_push($disallowed_cats_names, $category2->name);
            }
            foreach ($disallowed_cats_names as $key2 => $v2) {
                ?>
         <option value="<?=$disallowed_cats_ids[($key2)];?>"><?=$v2?></option>
         <?php

            }
        }
        ?>
   </select>
 </div>
</td>
</tr>
</table>

<?php
}

    public function facebookCommentsPlugin()
    {
        $res_fb_comments       = get_option('res_fb_comments');
        $res_fb_comments_appid = get_option('res_fb_comments_appid');

        if (!empty($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'], "update-options")) {
            //var_dump($_REQUEST);
            if (!empty($_POST)) {
                $res_fb_comments       = isset($_POST['res_fb_comments']) ? sanitize_text_field($_POST['res_fb_comments']) : 'no';
                $res_fb_comments_appid = isset($_POST['res_fb_comments_appid']) ? sanitize_text_field($_POST['res_fb_comments_appid']) : '';
                $this->save_option('res_fb_comments', $res_fb_comments);
                $this->save_option('res_fb_comments_appid', $res_fb_comments_appid);
            }
        }

        ?>
  <label for="res_fb_comments"><strong><p>Enable: *</p></strong></label>
  <?php if ($res_fb_comments == 'yes') {?>
  <input name="res_fb_comments" type="checkbox" value="yes" checked />
  <?php } else {?>
  <input name="res_fb_comments" type="checkbox" value="yes" />
  <?php }?>
  <label for="res_fb_comments_appid"><strong><p>Facebook App ID: **</p></strong></label>

  <input style="width:100%;" type="text" name="res_fb_comments_appid" value="<?php echo $res_fb_comments_appid; ?>"/></br><br>
  <span>*to enable Facebook Comments you have to create a Facebook Application. Follow this <a target="_blank" href="https://developers.facebook.com/docs/web/tutorials/scrumptious/register-facebook-application">Guide</a></span>
  <br> <span>** after creating please insert FB App ID</span>
  <?php
}

    public function disqusCommentsPlugin()
    {
        $res_dsq_comments           = get_option('res_dsq_comments');
        $res_dsq_comments_shortname = get_option('res_dsq_comments_shortname');

        if (!empty($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'], "update-options")) {
            //var_dump($_REQUEST);
            if (!empty($_POST)) {
                $res_wp_comments            = isset($_POST['res_wp_comments']) ? sanitize_text_field($_POST['res_wp_comments']) : 'no';
                $res_dsq_comments           = isset($_POST['res_dsq_comments']) ? sanitize_text_field($_POST['res_dsq_comments']) : 'no';
                $res_dsq_comments_shortname = isset($_POST['res_dsq_comments_shortname']) ? sanitize_text_field($_POST['res_dsq_comments_shortname']) : '';
                if ($res_wp_comments === 'yes' && $res_dsq_comments === 'yes') {
                    $res_dsq_comments = 'no';
                }
                $this->save_option('res_dsq_comments', $res_dsq_comments);
                $this->save_option('res_dsq_comments_shortname', $res_dsq_comments_shortname);
            }
        }

        ?>
  <label for="res_dsq_comments"><strong><p>Enable: *</p></strong></label>
  <?php if ($res_dsq_comments == 'yes') {?>
  <input name="res_dsq_comments" type="checkbox" value="yes" checked />
  <?php } else {?>
  <input name="res_dsq_comments" type="checkbox" value="yes" />
  <?php }?>
  <label for="res_dsq_comments_shortname"><strong><p>Disqus Short Name: **</p></strong></label>
  <input style="width:100%;" type="text" name="res_dsq_comments_shortname"  value="<?php echo $res_dsq_comments_shortname; ?>"/><br><br>
  <span>*to enable Disqus you have to register your Blog on Disqus Platform. Follow this <a target="_blank" href="https://help.disqus.com/customer/portal/articles/466182-quick-start-guide">Quick Start Guide</a></span>
  <br> <span>** after registering please insert Disqus Short Name</span>
  <?php
}

    public function bannerPlugin()
    {
        $res_banner = get_option('res_banner', 'yes');

        if (!empty($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'], "update-options")) {
            //var_dump($_REQUEST);
            if (!empty($_POST)) {
                $res_banner = isset($_POST['res_banner']) ? sanitize_text_field($_POST['res_banner']) : 'no';
                //echo "BANNER: " . $res_banner;
                $this->save_option('res_banner', $res_banner);
            }
        }

        ?>
  <label for="res_banner"><strong><p>Enable App Banner:</p></strong></label>
  <?php if ($res_banner == 'yes') {?>
  <input name="res_banner" type="checkbox" value="yes" checked />
  <?php } else {?>
  <input name="res_banner" type="checkbox" value="yes" />
  <?php }
    }

    public function analyticsPlugin()
    {
        $res_analytics    = get_option('res_analytics');
        $res_analytics_id = get_option('res_analytics_id');

        if (!empty($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'], "update-options")) {
            //var_dump($_REQUEST);
            if (!empty($_POST)) {
                $res_analytics    = isset($_POST['res_analytics']) ? sanitize_text_field($_POST['res_analytics']) : 'no';
                $res_analytics_id = isset($_POST['res_analytics_id']) ? sanitize_text_field($_POST['res_analytics_id']) : '';
                $this->save_option('res_analytics', $res_analytics);
                $this->save_option('res_analytics_id', $res_analytics_id);
            }
        }

        ?>
  <label for="res_analytics"><strong><p>Enable Google Analytics: *</p></strong></label>
  <?php if ($res_analytics == 'yes') {?>
  <input name="res_analytics" type="checkbox" value="yes" checked />
  <?php } else {?>
  <input name="res_analytics" type="checkbox" value="yes" />
  <?php }?>
  <label for="res_analytics"><strong><p>Google Analytics ID: **</p></strong></label>

  <input style="width:100%;" type="text" name="res_analytics_id" value="<?php echo $res_analytics_id; ?>"/></br><br>
  <span>*to enable Google Analyticsfollow this <a target="_blank" href="https://support.google.com/analytics/answer/1008015">Guide</a></span>
  <br> <span>** after creating please insert Google Analytcs ID (e.g. UA-XXXXXXXX-X)</span>
  <?php
}

    public function mynl2br($text)
    {
        $text = htmlentities($text, ENT_QUOTES, 'UTF-8');
        $text = stripslashes($text);
        return strtr($text, array("\r\n" => '<br />', "\r" => '<br />', "\n" => '<br />'));
    }
    public function jsPlugin()
    {
        $res_js             = get_option('res_js');
        $res_js_code        = get_option('res_js_code');
        $res_js_import      = get_option('res_js_import');
        $res_js_import_code = get_option('res_js_import_code');

        if (!empty($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'], "update-options")) {
            //var_dump($_REQUEST);
            if (!empty($_POST)) {
                $res_js      = isset($_POST['res_js']) ? sanitize_text_field($_POST['res_js']) : 'no';
                $res_js_code = isset($_POST['res_js_code']) ? $this->mynl2br(sanitize_text_field($_POST['res_js_code'])) : '';
                $this->save_option('res_js', $res_js);
                $this->save_option('res_js_code', $res_js_code);

                $res_js_import      = isset($_POST['res_js_import']) ? sanitize_text_field($_POST['res_js_import']) : 'no';
                $res_js_import_code = isset($_POST['res_js_import_code']) ? $this->mynl2br(sanitize_text_field($_POST['res_js_import_code'])) : '';
                $this->save_option('res_js_import', $res_js_import);
                $this->save_option('res_js_import_code', $res_js_import_code);

            }
        }

        ?>

 <label for="res_js"><strong><p>Enable Custom JS:</p></strong></label>
  <?php if ($res_js == 'yes') {?>
  <input name="res_js" type="checkbox" value="yes" checked />
  <?php } else {?>
  <input name="res_js" type="checkbox" value="yes" />
  <?php }?>
  <label for="res_js"><strong><p>Insert Custom Javascript Code: *</p></strong></label>

<textarea style="resize:vertical!important; width:100%!important;" rows="4" name="res_js_code"><?php echo $res_js_code; ?></textarea>
<span><strong>* DO NOT include SCRIPT tags. Code will be included on header</strong></span>
<br>
<hr>
<br>
  <label for="res_js_import"><strong><p>Import JS FILE:</p></strong></label>
  <?php if ($res_js_import == 'yes') {?>
  <input name="res_js_import" type="checkbox" value="yes" checked />
  <?php } else {?>
  <input name="res_js_import" type="checkbox" value="yes" />
  <?php }?>
  <label for="res_js_import"><strong><p>Insert custom JS file to import: *</p></strong></label>
  <input style="width:100%;" type="text" name="res_js_import_code" value="<?php echo $res_js_import_code; ?>"/></br><br>
<span><strong>* DO NOT include SCRIPT TAG. Code will be included on header</strong></span>


  <?php
}

    public function get_method_url($controller, $method, $options = '')
    {
        $url                 = get_bloginfo('url');
        $base                = "res-api"; //get_option('res_json_api_base', 'res-api');
        $permalink_structure = get_option('permalink_structure', '');
        if (!empty($options) && is_array($options)) {
            $args = array();
            foreach ($options as $key => $value) {
                $args[] = urlencode($key) . '=' . urlencode($value);
            }
            $args = implode('&', $args);
        } else {
            $args = $options;
        }
        if ($controller != 'core') {
            $method = "$controller/$method";
        }
        if (!empty($base) && !empty($permalink_structure)) {
            if (!empty($args)) {
                $args = "?$args";
            }
            return "$url/$base/$method/$args";
        } else {
            return "$url?res-json=$method&$args";
        }
    }

    public function save_option($id, $value)
    {
        $option_exists = (get_option($id, null) !== null);
        if ($option_exists) {
            update_option($id, $value);
        } else {
            add_option($id, $value);
        }
    }

    public function get_controllers()
    {
        $controllers = array();
        $dir         = res_json_api_dir();
        $this->check_directory_for_controllers("$dir/controllers", $controllers);
        $this->check_directory_for_controllers(get_stylesheet_directory(), $controllers);
        $controllers = apply_filters('res_json_api_controllers', $controllers);
        return array_map('strtolower', $controllers);
    }

    public function check_directory_for_controllers($dir, &$controllers)
    {
        $dh = opendir($dir);
        while ($file = readdir($dh)) {
            if (preg_match('/(.+)\.php$/i', $file, $matches)) {
                $src = file_get_contents("$dir/$file");
                if (preg_match("/class\s+RES_JSON_API_{$matches[1]}_Controller/i", $src)) {
                    $controllers[] = $matches[1];
                }
            }
        }
    }

    public function controller_is_active($controller)
    {
        if (defined('RES_JSON_API_CONTROLLERS')) {
            $default = RES_JSON_API_CONTROLLERS;
        } else {
            $default = 'core';
        }
        $active_controllers = explode(',', get_option('res_json_api_controllers', $default));
        return (in_array($controller, $active_controllers));
    }

    public function update_controllers($controllers)
    {
        if (is_array($controllers)) {
            return implode(',', $controllers);
        } else {
            return $controllers;
        }
    }

    public function controller_info($controller)
    {
        $path     = $this->controller_path($controller);
        $class    = $this->controller_class($controller);
        $response = array(
            'name'        => $controller,
            'description' => '(No description available)',
            'methods'     => array(),
        );
        if (file_exists($path)) {
            $source = file_get_contents($path);
            if (preg_match('/^\s*Controller name:(.+)$/im', $source, $matches)) {
                $response['name'] = trim($matches[1]);
            }
            if (preg_match('/^\s*Controller description:(.+)$/im', $source, $matches)) {
                $response['description'] = trim($matches[1]);
            }
            if (preg_match('/^\s*Controller URI:(.+)$/im', $source, $matches)) {
                $response['docs'] = trim($matches[1]);
            }
            if (!class_exists($class)) {
                require_once $path;
            }
            $response['methods'] = get_class_methods($class);
            return $response;
        } else if (is_admin()) {
            return "Cannot find controller class '$class' (filtered path: $path).";
        } else {
            $this->error("Unknown controller '$controller'.");
        }
        return $response;
    }

    public function controller_class($controller)
    {
        return "res_json_api_{$controller}_controller";
    }

    public function controller_path($controller)
    {
        $res_json_api_dir  = res_json_api_dir();
        $res_json_api_path = "$res_json_api_dir/controllers/$controller.php";
        $theme_dir         = get_stylesheet_directory();
        $theme_path        = "$theme_dir/$controller.php";
        if (file_exists($theme_path)) {
            $path = $theme_path;
        } else if (file_exists($res_json_api_path)) {
            $path = $res_json_api_path;
        } else {
            $path = null;
        }
        $controller_class = $this->controller_class($controller);
        return apply_filters("{$controller_class}_path", $path);
    }

    public function get_nonce_id($controller, $method)
    {
        $controller = strtolower($controller);
        $method     = strtolower($method);
        return "res_json_api-$controller-$method";
    }

    public function flush_rewrite_rules()
    {
        global $wp_rewrite;
        $wp_rewrite->flush_rules();
    }

    public function error($message = 'Unknown error', $status = 'error')
    {
        $this->response->respond(array(
            'error' => $message,
        ), $status);
    }

    public function include_value($key)
    {
        return $this->response->is_value_included($key);
    }

}

?>
