<?php

namespace GeminiLabs\FlarumBridge;

use GeminiLabs\FlarumBridge\Container;

final class Application extends Container
{
	const ID = 'flarum-bridge';

	public $db;
	public $file;
	public $flarum;
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
		$this->singleton( Flarum::class, Flarum::class );
		$this->db = $this->make( Database::class );
		$this->flarum = $this->make( Flarum::class );
	}

	/**
	 * @param string $key
	 * @return array|string
	 */
	public function getDefaults( $key = '' )
	{
		$defaults = [
			'flarum_api_key' => '',
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
		add_action( 'admin_menu',           [$this, 'registerMenu'] );
		add_action( 'admin_menu',           [$this, 'registerSettings'] );
		add_action( 'plugins_loaded',       [$this, 'registerLanguages'] );
		add_action( 'admin_menu',           [$this->db, 'bootstrap'] );
		add_filter( 'authenticate',         [$this->flarum, 'loginUser'], 999, 3 );
		add_action( 'wp_logout',            [$this->flarum, 'logoutUser'] );
		add_filter( 'login_redirect',       [$this->flarum, 'redirectUser'], 10, 3 );
		add_action( 'after_password_reset', [$this->flarum, 'updateUserPassword'], 10, 2 );
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
}
