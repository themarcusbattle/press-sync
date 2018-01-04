<?php
/**
 * Press Sync
 *
 * @package PressSync
 * @author  Marcus Battle (marcus @webdevstudios .com), Zach Owen (zach @webdevstudios .com), Viacom
 * @license GPL
 *
 * @wordpress-plugin
 * Plugin Name: Press Sync
 * Description: The easiest way to synchronize posts, media and users between two WordPress sites
 * Version: 0.4.5
 * License: GPL
 * Author: Marcus Battle, WebDevStudios, Viacom
 * Author URI: http://webdevstudios.com/
 * Text Domain: press-sync
 */

$autoload = plugin_dir_path(__FILE__) . 'vendor/autoload.php';

if (file_exists($autoload)) {
    require_once $autoload;
}

if (!class_exists('\WDS\PressSync\PressSyncPlugin')) {
    return;
}

add_action( 'plugins_loaded', array( \WDS\PressSync\PressSyncPlugin::init(), 'hooks' ), 10, 1 );
