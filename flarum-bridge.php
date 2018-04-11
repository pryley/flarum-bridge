<?php
/**
 * ╔═╗╔═╗╔╦╗╦╔╗╔╦  ╦  ╔═╗╔╗ ╔═╗
 * ║ ╦║╣ ║║║║║║║║  ║  ╠═╣╠╩╗╚═╗
 * ╚═╝╚═╝╩ ╩╩╝╚╝╩  ╩═╝╩ ╩╚═╝╚═╝
 *
 * Plugin Name: Flarum Bridge
 * Description: Allow WordPress users to sign-in to a sub-directory Flarum installation
 * Version:     1.0.1-beta
 * Author:      Paul Ryley
 * Author URI:  https://profiles.wordpress.org/pryley#content-plugins
 * License:     GPL3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: flarum-bridge
 * Domain Path: languages
 */

defined( 'WPINC' ) || die;

if( !class_exists( 'GL_Plugin_Check' )) {
	require_once __DIR__.'/activate.php';
}
require_once __DIR__.'/autoload.php';
if( GL_Activate::shouldDeactivate( __FILE__, ['php' => '7.1'] ))return;
GeminiLabs\FlarumBridge\Application::load()->init();

/**
 * @return GeminiLabs\FlarumBridge\Application
 */
function glfb() {
	$app = GeminiLabs\FlarumBridge\Application::load();
	if( func_get_arg(0) && $concrete = $app->make( func_get_arg(0) )) {
		return $concrete;
	}
	return $app;
}
