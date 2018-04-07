<?php

defined( 'WPINC' ) || die;

/**
 * Checks for minimum system requirments on plugin activation
 * @version 1.0.0
 */
class GL_Activate
{
	const MIN_PHP_VERSION = '5.6.0';
	const MIN_WORDPRESS_VERSION = '4.7.0';

	/**
	 * @var string
	 */
	protected static $file;

	/**
	 * @var static
	 */
	protected static $instance;

	/**
	 * @var object
	 */
	protected static $versions;

	/**
	 * @return bool
	 */
	public static function isValid( array $args = array() )
	{
		$versions = static::normalize( $args );
		return static::isPhpValid( $versions->php ) && static::isWpValid( $versions->wordpress );
	}

	/**
	 * @param string $version
	 * @return bool
	 */
	public static function isPhpValid( $version = '' )
	{
		$versions = array( 'php' => $version );
		return !version_compare( PHP_VERSION, static::normalize( $versions )->php, '<' );
	}

	/**
	 * @param string $version
	 * @return bool
	 */
	public static function isWpValid( $version = '' )
	{
		global $wp_version;
		$versions = array( 'wordpress' => $version );
		return !version_compare( $wp_version, static::normalize( $versions )->wordpress, '<' );
	}

	/**
	 * @return bool
	 */
	public static function shouldDeactivate( $file, array $args = [] )
	{
		if( empty( static::$instance )) {
			static::$file = realpath( $file );
			static::$instance = new static;
			static::$versions = static::normalize( $args );
		}
		if( !static::isValid() ) {
			add_action( 'activated_plugin', array( static::$instance, 'deactivate' ));
			add_action( 'admin_notices', array( static::$instance, 'deactivate' ));
			return true;
		}
		return false;
	}

	/**
	 * @return void
	 */
	public function deactivate( $plugin )
	{
		if( static::isValid() )return;
		$pluginSlug = plugin_basename( static::$file );
		if( $plugin == $pluginSlug ) {
			$this->redirect(); //exit
		}
		$pluginData = get_file_data( static::$file, array( 'name' => 'Plugin Name' ), 'plugin' );
		deactivate_plugins( $pluginSlug );
		$this->printNotice( $pluginData['name'] );
	}

	/**
	 * @return object
	 */
	protected static function normalize( array $args = [] )
	{
		return (object) wp_parse_args( $args, array(
			'php' => static::MIN_PHP_VERSION,
			'wordpress' => static::MIN_WORDPRESS_VERSION,
		));
	}

	/**
	 * @return void
	 */
	protected function redirect()
	{
		wp_safe_redirect( self_admin_url( sprintf( 'plugins.php?plugin_status=%s&paged=%s&s=%s',
			filter_input( INPUT_GET, 'plugin_status' ),
			filter_input( INPUT_GET, 'paged' ),
			filter_input( INPUT_GET, 's' )
		)));
		exit;
	}

	/**
	 * @param string $pluginName
	 * @return void
	 */
	protected function printNotice( $pluginName )
	{
		$noticeTemplate = '<div id="message" class="notice notice-error error is-dismissible"><p><strong>%s</strong></p><p>%s</p><p>%s</p></div>';
		$messages = array(
			__( 'The %s plugin was deactivated.', 'flarum-bridge' ),
			__( 'Sorry, this plugin requires %s or greater in order to work properly.', 'flarum-bridge' ),
			__( 'Please contact your hosting provider or server administrator to upgrade the version of PHP on your server (your server is running PHP version %s), or try to find an alternative plugin.', 'flarum-bridge' ),
			__( 'PHP version', 'flarum-bridge' ),
			__( 'WordPress version', 'flarum-bridge' ),
			__( 'Update WordPress', 'flarum-bridge' ),
		);
		if( !static::isPhpValid() ) {
			printf( $noticeTemplate,
				sprintf( $messages[0], $pluginName ),
				sprintf( $messages[1], $messages[3].' '.(static::$versions)->php ),
				sprintf( $messages[2], PHP_VERSION )
			);
		}
		else if( !static::isWpValid() ) {
			printf( $noticeTemplate,
				sprintf( $messages[0], $pluginName ),
				sprintf( $messages[1], $messages[4].' '.(static::$versions)->wordpress ),
				sprintf( '<a href="%s">%s</a>', admin_url( 'update-core.php' ), $messages[5] )
			);
		}
	}
}
