<?php
/*
  Plugin Name: Sick Network Child
  Plugin URI: http://sicknetwork.com/
  Description: Child Plugin for Sick Network. The plugin is used so the installed blog can be securely managed remotely by your Network.  This plugin has no onsite configuration just activate and return to the Main Plugin site.
  Author: sickmarketing
  Author URI: http://sickplugins.com
  Version: 0.1.34
 */
//header('X-Frame-Options: ALLOWALL');
header('X-Frame-Options: GOFORIT');
include_once(ABSPATH . 'wp-includes' . DIRECTORY_SEPARATOR . 'version.php'); //Version information from wordpress

$classDir = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . str_replace(basename(__FILE__), '', plugin_basename(__FILE__)) . 'class' . DIRECTORY_SEPARATOR;
function sicknetwork_child_autoload($class_name) {
    $class_file = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . str_replace(basename(__FILE__), '', plugin_basename(__FILE__)) . 'class' . DIRECTORY_SEPARATOR . $class_name . '.class.php';
    if (file_exists($class_file)) {
        require_once($class_file);
    }
}
if (function_exists('spl_autoload_register'))
{
    spl_autoload_register('sicknetwork_child_autoload');
}
else
{
    function __autoload($class_name) {
        my_autoload($class_name);
    }
}

$sicknetwork_plugin_file = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . plugin_basename(__FILE__);
$sickNetwork = new SickNetwork($sicknetwork_plugin_file);
register_activation_hook(__FILE__, array($sickNetwork, 'activation'));
register_deactivation_hook(__FILE__, array($sickNetwork, 'deactivation'));
?>