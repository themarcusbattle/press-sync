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
 * Version: 0.7.3.1-no-release
 * License: GPL
 * Author: Marcus Battle, WebDevStudios, Viacom
 * Author URI: http://webdevstudios.com/
 * Text Domain: press-sync
 */

$autoload = plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

if ( file_exists( $autoload) ) {
    require_once $autoload;
} else {
    spl_autoload_register( 'press_sync_autoload_classes' );
}

if ( !class_exists( '\Press_Sync\Press_Sync' ) ) {
    return;
}

add_action( 'plugins_loaded', array( \Press_Sync\Press_Sync::init(), 'hooks' ), 10, 1 );

/**
 * @TODO will need to parse additional namespace paths, such as \Press_Sync\SOMETHING\Class at some point maybe.
 */
function press_sync_autoload_classes( $class_name ) {
    $file_parts = explode( '\\', $class_name );

	// If our class doesn't have our prefix, don't load it.
	if ( 'Press_Sync' !== $file_parts[0] ) {
		return;
	}

	// Set up our filename.
	$filename = strtolower( str_replace( '_', '-', $file_parts[1] ) );

	// Include our file.
	include( "includes/class-{$filename}.php" );
}
