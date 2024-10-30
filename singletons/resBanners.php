<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('ResBanners')):

    class ResBanners
{

        /**
         * Initialization function
         */
        public static function init()
    {
            $res_banner = get_option('res_banner', 'yes');
            if ($res_banner == 'yes') {
                add_action('wp_enqueue_scripts', 'ResBanners_enqueue_scripts');
                add_action('wp_head', 'ResBanners_Meta');
            }

        }

    }

    /*
     * Scripts to be enqueued into Wordpress.  Making sure that jquery is added as a dependency
     * for SmartBanner.js
     */

    function ResBanners_enqueue_scripts()
{
        wp_register_style('res-banners-styles', plugins_url('../lib/smart-app-banner.css', __FILE__));
        wp_enqueue_style('res-banners-styles');

        //Script files are placed in Footer
        wp_register_script('res-banners-scripts', plugins_url('../lib/smart-app-banner.js', __FILE__), array('jquery'), null, true);
        wp_enqueue_script('res-banners-scripts');

        wp_register_script('res-banners-custom-scripts', plugins_url('../lib/config.js', __FILE__), array('jquery'), null, true);
        wp_localize_script('res-banners-custom-scripts', 'resBannersConfig', ResBanners_config());

        wp_enqueue_script('res-banners-custom-scripts');

    }

    function ResBanners_config()
{

        $author           = __('with MOBY BLOG', 'moby-blog');
        $price            = __('FREE', 'moby-blog');
        $title            = __('Follow ', 'moby-blog') . strtoupper(get_bloginfo('name'));
        $icon             = plugins_url('../assets/icon.png', __FILE__);
        $button           = __('DOWNLOAD', 'moby-blog'); //htmlspecialchars(get_option('RES_BANNERS_button'), ENT_QUOTES);
        $url              = '';
        $daysHidden       = 0;
        $daysReminder     = 0;
        $speedOut         = 400;
        $speedIn          = 300;
        $iconGloss        = true;
        $inAppStore       = __('on the App Store', 'moby-blog');
        $inGooglePlay     = __('in Google Play', 'moby-blog');
        $appStoreLanguage = __('US', 'moby-blog');
        $appendToSelector = 'body';
        $printViewPort    = true;

        $options = array(
            'title'            => $title,
            'author'           => $author,
            'price'            => $price,
            'appStoreLanguage' => $appStoreLanguage,
            'inAppStore'       => $inAppStore,
            'inGooglePlay'     => $inGooglePlay,
            'inAmazonAppStore' => 'In the Amazon Appstore',
            'inWindowsStore'   => 'In the Windows Store',
            'GooglePlayParams' => null,
            'icon'             => $icon,
            'iconGloss'        => $iconGloss,
            'url'              => $url,
            'button'           => $button,
            'scale'            => 'auto',
            'speedIn'          => $speedIn,
            'speedOut'         => $speedOut,
            'daysHidden'       => $daysHidden,
            'daysReminder'     => $daysReminder,
            'force'            => null,
            'hideOnInstall'    => false,
            'layer'            => false,
            'iOSUniversalApp'  => true,
            'appendToSelector' => $appendToSelector,
            'printViewPort'    => $printViewPort,
        );

        return $options;
    }

    /*
     * Function to inject the default app banner meta tags into the head of the
     * site.  Utilizing wp_head action.
     */
    function ResBanners_Meta()
{
        $res_banner = get_option('res_banner', 'yes');

        if ($res_banner == 'yes' ) {

            $appleID   = '1093869091';
            $androidID = 'com.mobyblogapp.app';
            $author    = __('with MOBY BLOG', 'moby-blog');
            $icon      = plugins_url('../assets/icon.png', __FILE__);
            // $msApplicationID          = get_option('RES_BANNERS_ms_application_id');
            //$msApplicationPackageName = get_option('RES_BANNERS_ms_application_package_name');
            $printViewPort = true;

            if ($appleID) {
                echo '<meta name="moby-apple-itunes-app" content="app-id=' . $appleID . '">' . PHP_EOL;
            }
            if ($androidID) {
                echo '<meta name="moby-google-play-app" content="app-id=' . $androidID . '">' . PHP_EOL;
            }
            if ($msApplicationID) {
                echo '<meta name="msApplication-ID" content="' . $msApplicationID . '"/>' . PHP_EOL;
            }
            if ($msApplicationPackageName) {
                echo '<meta name="msApplication-PackageFamilyName" content="' . $msApplicationPackageName . '"/>' . PHP_EOL;
            }
            //if ($author) {
                //echo '<meta name="author" content=" ' . $author . '">' . PHP_EOL;
            //}
            if ($printViewPort) {
                echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">' . PHP_EOL;
            }
            echo '<link rel="apple-touch-icon" href="' . $icon . '" >' . PHP_EOL;
            echo '<link rel="android-touch-icon" href="' . $icon . '" >' . PHP_EOL;
        }
    }

    ResBanners::init();

endif;
