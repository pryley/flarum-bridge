<?php

namespace GeminiLabs\FlarumBridge;

class Application
{
	public $file;
	public $id;
	public $languages;
	public $name;
	public $version;

	protected $flarum;

	/**
	 * @param string $file
	 * @return void
	 */
	public function __construct( $file )
	{
		$plugin = get_file_data( $file, [
			'id' => 'Text Domain',
			'languages' => 'Domain Path',
			'name' => 'Plugin Name',
			'version' => 'Version',
		], 'plugin' );
		array_walk( $plugin, function( $value, $key ) {
			$this->$key = $value;
		});
		$this->file = $file;
		$this->flarum = new Flarum( $this->getSettings() );
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
	 * @return object
	 */
	public function getSettings()
	{
		$settings = get_option( $this->id, [] );
		if( empty( $settings )) {
			update_option( $this->id, $settings = $this->getDefaults() );
		}
		return (object)$settings;
	}

	/**
	 * @return void
	 */
	public function init()
	{
		add_action( 'admin_menu',     [$this, 'registerMenu'] );
		add_action( 'admin_menu',     [$this, 'registerSettings'] );
		add_action( 'plugins_loaded', [$this, 'registerLanguages'] );
		add_action( 'wp_logout',      [$this->flarum, 'logoutUser'] );
		add_filter( 'authenticate',   [$this->flarum, 'loginUser'], 999, 3 );
		add_filter( 'login_redirect', [$this->flarum, 'redirectUser'], 10, 3 );
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
		load_plugin_textdomain( $this->id, false,
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
			$this->id,
			[$this, 'renderSettingsPage']
		);
	}

	/**
	 * @return void
	 * @action admin_menu
	 */
	public function registerSettings()
	{
		register_setting( $this->id, $this->id );
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
			'id' => $this->id,
			'settings' => $this->getSettings(),
			'title' => __( 'Flarum Bridge Settings', 'flarum-bridge' ),
		]);
	}
}
