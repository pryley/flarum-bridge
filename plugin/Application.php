<?php

namespace GeminiLabs\FlarumBridge;

use GeminiLabs\FlarumBridge\Avatar;
use GeminiLabs\FlarumBridge\Container;
use GeminiLabs\FlarumBridge\Database;
use GeminiLabs\FlarumBridge\Flarum;

final class Application extends Container
{
	const ID = 'flarum-bridge';

	public $api;
	public $avatar;
	public $db;
	public $file;
	public $languages;
	public $name;
	public $version;

	/**
	 * @return void
	 */
	public function __construct()
	{
		$this->bootstrap();
		$this->file = trailingslashit( dirname( __DIR__ )).static::ID.'.php';
		$plugin = get_file_data( $this->file, [
			'languages' => 'Domain Path',
			'name' => 'Plugin Name',
			'version' => 'Version',
		], 'plugin' );
		array_walk( $plugin, function( $value, $key ) {
			$this->$key = $value;
		});
	}

	/**
	 * @return void
	 */
	public function bootstrap()
	{
		$this->singleton( Database::class, Database::class );
		$this->db = $this->make( Database::class );
		$this->bind( Flarum::class, function() {
			return new Flarum( $this->db->getsettings() );
		});
		$this->api = $this->make( Flarum::class );
		$this->avatar = $this->make( Avatar::class );
	}

	/**
	 * @param string $key
	 * @return array|string
	 */
	public function getDefaults( $key = '' )
	{
		$defaults = [
			'api_key' => '',
			'flarum_url' => '/forum',
		];
		return array_key_exists( $key, $defaults )
			? $defaults[$key]
			: $defaults;
	}

	/**
	 * @return void
	 */
	public function init()
	{
		add_action( 'plugins_loaded',             [$this, 'registerLanguages'] );
		add_action( 'admin_menu',                 [$this, 'registerMenu'] );
		add_action( 'admin_menu',                 [$this, 'registerSettings'] );
		add_action( 'user_profile_update_errors', [$this, 'validatePasswordLength'] );
		add_action( 'validate_password_reset',    [$this, 'validatePasswordLength'] );
		add_filter( 'pre_get_avatar_data',        [$this->avatar, 'filterAvatarData'], 10, 2 );
		add_filter( 'authenticate',               [$this->api, 'filterAuthentication'], 999, 3 );
		add_action( 'wp_logout',                  [$this->api, 'logoutUser'] );
		add_filter( 'login_redirect',             [$this->api, 'filterLoginRedirect'], 10, 3 );
		// add_action( 'profile_update',       [$this->api, 'updateUserDetails'], 10, 2 );
		// add_action( 'after_password_reset', [$this->api, 'updateUserPassword'], 10, 2 );
		// add_action( 'set_user_role',        [$this->api, 'updateUserRole'], 10, 3 );

		// add_action( 'admin_init', function() {
		// 	$result = $this->api->editUser( 1, [
		// 		'password' => 'test1234',
		// 	]);
		// 	apply_filters( 'logger', $result );
		// });
	}

	/**
	 * @param string $file
	 * @return string
	 */
	public function path( $file = '' )
	{
		return plugin_dir_path( $this->file ).ltrim( trim( $file ), '/' );
	}

	/**
	 * @return void
	 */
	public function registerLanguages()
	{
		load_plugin_textdomain( static::ID, false,
			trailingslashit( plugin_basename( $this->path() ).'/'.$this->languages )
		);
	}

	/**
	 * @return void
	 * @action admin_menu
	 */
	public function registerMenu()
	{
		add_submenu_page(
			'options-general.php',
			__( 'Flarum Bridge', 'flarum-bridge' ),
			__( 'Flarum Bridge', 'flarum-bridge' ),
			'manage_options',
			static::ID,
			[$this, 'renderSettingsPage']
		);
	}

	/**
	 * @return void
	 * @action admin_menu
	 */
	public function registerSettings()
	{
		register_setting( static::ID, static::ID );
	}

	/**
	 * @param string $view
	 * @return void|null
	 */
	public function render( $view, array $data = [] )
	{
		if( !file_exists( $file = $this->path( 'views/'.$view.'.php' )))return;
		extract( $data );
		include $file;
	}

	/**
	 * @return void
	 * @callback add_submenu_page
	 */
	public function renderSettingsPage()
	{
		$this->render( 'settings', [
			'defaults' => (object)$this->getDefaults(),
			'id' => static::ID,
			'settings' => $this->db->getSettings(),
			'title' => __( 'Flarum Bridge Settings', 'flarum-bridge' ),
		]);
	}

	/**
	 * @param object $errors
	 * @return void
	 */
	public function validatePasswordLength( $errors )
	{
		if( is_wp_error( $errors ) && $errors->get_error_data( 'pass' ))return;
		$password = sanitize_text_field( filter_input( INPUT_POST, 'pass1' ));
		if( empty( $password ) || strlen( $password ) > 7 )return;
		$errors->add( 'pass',
			'<strong>ERROR</strong>: '.__( 'Please make sure the password is at least 8 characters.', 'flarum-bridge' )
		);
	}
}
