<?php
/**
 * Press Sync
 *
 * @package   PressSync
 * @author    Marcus Battle (marcus.battle @viacomcontractor .com), Zach Owen (zach.owen @viacomcontractor .com)
 * @copyright 2017 Viacom
 * @license   proprietary
 *
 * @wordpress-plugin
 * Plugin Name: Press Sync
 * Description: The easiest way to synchronize posts, media and users between two WordPress sites
 * Version: 0.4.4
 * License: GPL
 * Author: Marcus Battle, WebDevStudios
 * Author URI: http://webdevstudios.com/
 * Text Domain: press-sync
 */

$autoload = plugin_dir_path(__FILE__) . 'vendor/autoload.php';

if (file_exists($autoload)) {
    require_once $autoload;
}

if (!class_exists('\VMN\GEG\PressSync\PressSyncPlugin')) {
    return;
}

add_action( 'plugins_loaded', array( \VMN\GEG\PressSync\PressSyncPlugin::init(), 'hooks' ), 10, 1 );
