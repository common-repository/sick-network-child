<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Ruben
 * Date: 24/11/11
 * Time: 20:29
 * To change this template use File | Settings | File Templates.
 */

class SickNetworkSecurity
{
    public static function fixAll()
    {
        SickNetworkSecurity::remove_wp_version();
        SickNetworkSecurity::remove_rsd();
        SickNetworkSecurity::remove_wlw();
//        SickNetworkSecurity::remove_core_update();
//        SickNetworkSecurity::remove_plugin_update();
//        SickNetworkSecurity::remove_theme_update();
        SickNetworkSecurity::remove_php_reporting();
        SickNetworkSecurity::remove_scripts_version();
        SickNetworkSecurity::remove_styles_version();

        add_filter('style_loader_src', array('SickNetworkSecurity', 'remove_script_versions'), 999 );
        add_filter('style_loader_src', array('SickNetworkSecurity', 'remove_theme_versions'), 999 );
        add_filter('script_loader_src', array('SickNetworkSecurity', 'remove_script_versions'), 999 );
        add_filter('script_loader_src', array('SickNetworkSecurity', 'remove_theme_versions'), 999 );
    }

    //Prevent listing wp-content, wp-content/plugins, wp-content/themes, wp-content/uploads
    private static $listingDirectories = null;

    private static function init_listingDirectories()
    {
        if (SickNetworkSecurity::$listingDirectories == null)
        {
            $wp_upload_dir = wp_upload_dir();
            SickNetworkSecurity::$listingDirectories = array(WP_CONTENT_DIR, WP_PLUGIN_DIR, get_theme_root(), $wp_upload_dir['basedir']);
        }
    }

    public static function prevent_listing_ok()
    {
        SickNetworkSecurity::init_listingDirectories();
        foreach (SickNetworkSecurity::$listingDirectories as $directory)
        {
            $file = $directory . DIRECTORY_SEPARATOR . 'index.php';
            if (!file_exists($file))
            {
                return false;
            }
        }
        return true;
    }

    public static function prevent_listing()
    {
        SickNetworkSecurity::init_listingDirectories();
        foreach (SickNetworkSecurity::$listingDirectories as $directory)
        {
            $file = $directory . DIRECTORY_SEPARATOR . 'index.php';
            if (!file_exists($file))
            {
                $h = fopen($file, 'w');
                fwrite($h, '<?php die(); ?>');
                fclose($h);
            }
        }
    }

    //Removed wp-version
    public static function remove_wp_version_ok()
    {
        return !(has_action('wp_head', 'wp_generator') || has_filter('wp_head', 'wp_generator'));
    }

    public static function remove_wp_version()
    {
        if (get_option('_sicknetwork_remove_wp_version') == 'T')
        {
            remove_action('wp_head', 'wp_generator');
            remove_filter('wp_head', 'wp_generator');
        }
    }

    //Removed Really Simple Discovery meta tag
    public static function remove_rsd_ok()
    {
        return (!has_action('wp_head', 'rsd_link'));
    }

    public static function remove_rsd()
    {
        if (get_option('_sicknetwork_remove_rsd') == 'T')
        {
            remove_action('wp_head', 'rsd_link');
        }
    }

    //Removed Windows Live Writer meta tag
    public static function remove_wlw_ok()
    {
        return (!has_action('wp_head', 'wlwmanifest_link'));
    }

    public static function remove_wlw()
    {
        if (get_option('_sicknetwork_remove_wlw') == 'T')
        {
            remove_action('wp_head', 'wlwmanifest_link');
        }
    }

    //Removed core update information for non-admins
//    public static function remove_core_update_ok()
//    {
//        return (get_option('_sicknetwork_remove_core_updates') == 'T');
//    }

//    public static function remove_core_update()
//    {
//        if (get_option('_sicknetwork_remove_core_updates') == 'T')
//        {
//            if (!current_user_can('update_plugins'))
//            {
//                add_action('admin_init', create_function('$a', "remove_action( 'admin_notices', 'maintenance_nag' );"));
//                add_action('admin_init', create_function('$a', "remove_action( 'admin_notices', 'update_nag', 3 );"));
//                add_action('admin_init', create_function('$a', "remove_action( 'admin_init', '_maybe_update_core' );"));
//                add_action('init', create_function('$a', "remove_action( 'init', 'wp_version_check' );"));
//                add_filter('pre_option_update_core', create_function('$a', "return null;"));
//                remove_action('wp_version_check', 'wp_version_check');
//                remove_action('admin_init', '_maybe_update_core');
//                add_filter('pre_transient_update_core', create_function('$a', "return null;"));
//                add_filter('pre_site_transient_update_core', create_function('$a', "return null;"));
//            }
//        }
//    }

    //Removed plugin-update information for non-admins
//    public static function remove_plugin_update_ok()
//    {
//        return (get_option('_sicknetwork_remove_plugin_updates') == 'T');
//    }

//    public static function remove_plugin_update()
//    {
//        if (get_option('_sicknetwork_remove_plugin_updates') == 'T')
//        {
//            if (!current_user_can('update_plugins'))
//            {
//                add_action('admin_init', create_function('$a', "remove_action( 'admin_init', 'wp_plugin_update_rows' );"), 2);
//                add_action('admin_init', create_function('$a', "remove_action( 'admin_init', '_maybe_update_plugins' );"), 2);
//                add_action('admin_menu', create_function('$a', "remove_action( 'load-plugins.php', 'wp_update_plugins' );"));
//                add_action('admin_init', create_function('$a', "remove_action( 'admin_init', 'wp_update_plugins' );"), 2);
//                add_action('init', create_function('$a', "remove_action( 'init', 'wp_update_plugins' );"), 2);
//                add_filter('pre_option_update_plugins', create_function('$a', "return null;"));
//                remove_action('load-plugins.php', 'wp_update_plugins');
//                remove_action('load-update.php', 'wp_update_plugins');
//                remove_action('admin_init', '_maybe_update_plugins');
//                remove_action('wp_update_plugins', 'wp_update_plugins');
//                remove_action('load-update-core.php', 'wp_update_plugins');
//                add_filter('pre_transient_update_plugins', create_function('$a', "return null;"));
//            }
//        }
//    }

    //Removed theme-update information for non-admins
//    public static function remove_theme_update_ok()
//    {
//        return (get_option('_sicknetwork_remove_theme_updates') == 'T');
//    }

//    public static function remove_theme_update()
//    {
//        if (get_option('_sicknetwork_remove_theme_updates') == 'T')
//        {
//            if (!current_user_can('edit_themes'))
//            {
//                remove_action('load-themes.php', 'wp_update_themes');
//                remove_action('load-update.php', 'wp_update_themes');
//                remove_action('admin_init', '_maybe_update_themes');
//                remove_action('wp_update_themes', 'wp_update_themes');
//                remove_action('load-update-core.php', 'wp_update_themes');
//                add_filter('pre_transient_update_themes', create_function('$a', "return null;"));
//            }
//        }
//    }

    //File permissions not secure
    private static $permission_checks = null;

    private static function init_permission_checks()
    {
        if (SickNetworkSecurity::$permission_checks == null)
        {
            SickNetworkSecurity::$permission_checks = array(WP_CONTENT_DIR . DIRECTORY_SEPARATOR . '../' => '0755',
                                                        WP_CONTENT_DIR . DIRECTORY_SEPARATOR . '../wp-includes' => '0755',
                                                        WP_CONTENT_DIR . DIRECTORY_SEPARATOR . '../.htaccess' => '0644',
                                                        WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'index.php' => '0644',
                                                        WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'js/' => '0755',
                                                        WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'themes' => '0755',
                                                        WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'plugins' => '0755',
                                                        WP_CONTENT_DIR . DIRECTORY_SEPARATOR . '../wp-admin' => '0755',
                                                        WP_CONTENT_DIR => '0755');
        }
    }

//    public static function fix_file_permissions_ok()
//    {
//        SickNetworkSecurity::init_permission_checks();
//
//        $perms_issues = 0;
//
//        foreach (SickNetworkSecurity::$permission_checks as $dir => $needed_perms)
//        {
//            if (!file_exists($dir)) continue;
//
//            $perms = substr(sprintf('%o', fileperms($dir)), -4);
//            if ($perms != $needed_perms)
//            {
//                $perms_issues++;
//            }
//        }
//        return ($perms_issues == 0);
//    }

//    public static function fix_file_permissions()
//    {
//        SickNetworkSecurity::init_permission_checks();
//        $success = true;
//        foreach (SickNetworkSecurity::$permission_checks as $dir => $needed_perms)
//        {
//            if (!file_exists($dir)) continue;
//            $success == $success && chmod($dir, $needed_perms);
//        }
//        return $success;
//    }

    //Database error reporting turned on/off
    public static function remove_database_reporting_ok()
    {
        global $wpdb;
        return ($wpdb->show_errors == false);
    }

    public static function remove_database_reporting()
    {
        global $wpdb;

        $wpdb->hide_errors();
        $wpdb->suppress_errors();
    }

    //PHP error reporting turned on/off
    public static function remove_php_reporting_ok()
    {
        return !(ini_get('display_errors') != 'off' || ini_get('display_startup_errors') != 0);
    }

    public static function remove_php_reporting()
    {
        if (get_option('_sicknetwork_remove_php_reporting') == 'T')
        {
            @error_reporting(0);
            @ini_set('display_errors', 'off');
            @ini_set('display_startup_errors', 0);
        }
    }

    //Removed version information for scripts/stylesheets
    public static function remove_scripts_version_ok()
    {
        return (get_option('_sicknetwork_remove_scripts_version') == 'T');

//        global $wp_scripts;
//        if (!is_a($wp_scripts, 'WP_Scripts'))
//        {
//            return true;
//        }
//        foreach ($wp_scripts->registered as $handle => $script)
//        {
//            if ($wp_scripts->registered[$handle]->ver != null)
//            {
//                return false;
//            }
//        }
//        return true;
    }

    public static function remove_script_versions($src)
    {
        if (get_option('_sicknetwork_remove_scripts_version') == 'T')
        {
            if (strpos($src, '?ver='))
                $src = remove_query_arg('ver', $src);

            return $src;
        }
        return $src;
    }

    public static function remove_theme_versions($src)
    {
        if (get_option('_sicknetwork_remove_styles_version') == 'T')
        {
            if (strpos($src, '?ver='))
                $src = remove_query_arg('ver', $src);

            return $src;
        }
        return $src;
    }

    public static function remove_scripts_version()
    {
        if (get_option('_sicknetwork_remove_scripts_version') == 'T')
        {
            global $wp_scripts;
            if (!is_a($wp_scripts, 'WP_Scripts'))
                return;

            foreach ($wp_scripts->registered as $handle => $script)
                $wp_scripts->registered[$handle]->ver = null;
        }
    }

    public static function remove_styles_version_ok()
    {
        return (get_option('_sicknetwork_remove_styles_version') == 'T');

//        global $wp_styles;
//        if (!is_a($wp_styles, 'WP_Styles'))
//        {
//            return true;
//        }
//
//        foreach ($wp_styles->registered as $handle => $style)
//        {
//            if ($wp_styles->registered[$handle]->ver != null)
//            {
//                return false;
//            }
//        }
//        return true;
    }

    public static function remove_styles_version()
    {
        if (get_option('_sicknetwork_remove_styles_version') == 'T')
        {
            global $wp_styles;
            if (!is_a($wp_styles, 'WP_Styles'))
                return;

            foreach ($wp_styles->registered as $handle => $style)
                $wp_styles->registered[$handle]->ver = null;
        }
    }

    //Admin user name is not admin
    public static function admin_user_ok()
    {
        $user = get_user_by('login', 'admin');
        return !($user && ($user->wp_user_level == 10 || (isset($user->user_level) && $user->user_level == 10)));
    }
}

?>