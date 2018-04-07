<?php
/**
 * ╔═╗╔═╗╔╦╗╦╔╗╔╦  ╦  ╔═╗╔╗ ╔═╗
 * ║ ╦║╣ ║║║║║║║║  ║  ╠═╣╠╩╗╚═╗
 * ╚═╝╚═╝╩ ╩╩╝╚╝╩  ╩═╝╩ ╩╚═╝╚═╝
 *
 * Plugin Name: Flarum Bridge
 * Description: Allow WordPress users to sign-in to a sub-directory Flarum installation
 * Version:     1.0.0
 * Author:      Paul Ryley
 * Author URI:  https://profiles.wordpress.org/pryley#content-plugins
 * License:     GPL3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: flarum-bridge
 * Domain Path: languages
 */

defined( 'WPINC' ) || die;

require_once __DIR__.'/activate.php';
require_once __DIR__.'/autoload.php';

if( GL_Activate::shouldDeactivate( __FILE__ ))return;

$app = (new GeminiLabs\FlarumBridge\Application( realpath( __FILE__ )))->init();
