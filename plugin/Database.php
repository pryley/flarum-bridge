<?php

namespace GeminiLabs\FlarumBridge;

use GeminiLabs\FlarumBridge\Application;
use WP_Error;
use WP_User;
use wpdb;

class Database
{
	protected $config;
	protected $db;
	protected $prefix;

	/**
	 * @return array
	 * @action admin_menu
	 */
	public function bootstrap()
	{
		$this->config = $this->getFlarumConfig();
		$this->prefix = $this->config['prefix'];
		$this->db = $this->getFlarumDatabase();
	}

	/**
	 * @return object
	 */
	public function getSettings()
	{
		$settings = get_option( Application::ID, [] );
		if( empty( $settings )) {
			update_option( Application::ID, $settings = glfb()->getDefaults() );
		}
		return (object)$settings;
	}

	/**
	 * @return bool
	 */
	public function updateAvatar( WP_User $user )
	{
	}

	/**
	 * @return bool
	 */
	public function updateName( WP_User $user )
	{
	}

	/**
	 * @param string $password
	 * @return bool
	 */
	public function updatePassword( WP_User $user, $password )
	{
	}

	/**
	 * @return bool
	 */
	public function updateRole( WP_User $user )
	{
	}

	/**
	 * @return array
	 */
	protected function getFlarumConfig()
	{
		$rootPath = untrailingslashit( get_home_path() );
		$flarumPath = trailingslashit( $this->getSettings()->flarum_url );
		$configFile = $rootPath.$flarumPath.'config.php';
		$config = file_exists( $configFile )
			? include $configFile
			: [];
		return $this->normalizeFlarumConfig( $config );
	}

	/**
	 * @return array
	 */
	protected function getFlarumDatabase()
	{
		if( !empty( $this->db ))return;
		return new wpdb(
			$this->config['username'],
			$this->config['password'],
			$this->config['database'],
			$this->config['host']
		);
	}

	/**
	 * @return array
	 */
	protected function normalizeFlarumConfig( array $config )
	{
		$databaseConfig = isset( $config['database'] )
			? $config['database']
			: [];
		return wp_parse_args( $databaseConfig, [
			'database' => '',
			'driver' => 'mysql',
			'host' => 'localhost',
			'password' => '',
			'prefix' => '',
			'username' => '',
		]);
	}
}
