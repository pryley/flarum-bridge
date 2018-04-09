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

	public function __construct()
	{
		if( !is_admin() )return;
		if( empty( $this->config )) {
			$this->config = $this->getFlarumConfig();
			$this->prefix = $this->config['prefix'];
		}
		if( empty( $this->db )) {
			$this->db = $this->getFlarumDatabase();
		}
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
	 * @param string $newEmail
	 * @return bool
	 */
	public function updateEmail( WP_User $user, $newEmail )
	{
		// $this->db->update(
		// 	$this->prefix.'users',
		// 	['email' => $newEmail],
		// 	['email' => $user->user_email]
		// );
	}

	/**
	 * @param string $newPassword
	 * @return bool
	 */
	public function updatePassword( WP_User $user, $newPassword )
	{
		// $this->db->update(
		// 	$this->prefix.'users',
		// 	['password' => wp_hash_password( $newPassword )],
		// 	['email' => $user->user_email]
		// );
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
	 * @return wpdb
	 */
	protected function getFlarumDatabase()
	{
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
