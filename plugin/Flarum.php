<?php

namespace GeminiLabs\FlarumBridge;

use WP_Error;
use WP_User;

class Flarum
{
	const REMEMBER_ME_KEY = 'flarum_remember';

	protected $settings;

	public function __construct( $settings )
	{
		$this->settings = $settings;
	}

	/**
	 * @param WP_User|null $user
	 * @param string $username
	 * @param string $password
	 * @return null|WP_User
	 * @filter authenticate
	 */
	public function loginUser( $user, $username, $password )
	{
		if( $user instanceof WP_User ) {
			$this->login( $user, $password );
		}
		return $user;
	}

	/**
	 * @return void
	 * @action wp_logout
	 */
	public function logoutUser()
	{
		$this->logout();
	}

	/**
	 * @param string $redirect
	 * @param string $requested_redirect
	 * @param WP_User|WP_Error $user
	 * @return string
	 * @filter login_redirect
	 */
	public function redirectUser( $redirect, $requestedRedirect, $user )
	{
		if( $redirect === 'forum' && $user instanceof WP_User ) {
			$this->redirectToFlarum();
		}
		return $redirect;
	}

	/**
	 * @param int $userId
	 * @return void
	 * @action profile_update
	 */
	public function updateUserDetails( $userId, WP_User $oldUser )
	{
		$user = get_userdata( $userId );
		if( $user->user_email != $oldUser->user_email ) {
			glfb()->db->updateEmail( $oldUser, $user->user_email );
			apply_filters( 'logger', 'changed email' );
		}
		if( !empty( filter_input( INPUT_POST, 'pass1' ))) {
			$this->updateUserPassword( $user, filter_input( INPUT_POST, 'pass2' ));
		}
	}

	/**
	 * @param string $newPassword
	 * @return void
	 * @action after_password_reset
	 */
	public function updateUserPassword( WP_User $user, $newPassword )
	{
		glfb()->db->updatePassword( $user, $newPassword );
		apply_filters( 'logger', 'updated password' );
	}

	/**
	 * @param int $userId
	 * @param string $role
	 * @param array $oldRoles
	 * @return void
	 * @action set_user_role
	 */
	public function updateUserRole( $userId, $role, $oldRoles )
	{
		$user = get_userdata( $userId );
		glfb()->db->updateRole( $user, $role );
		apply_filters( 'logger', 'updated role' );
	}

	/**
	 * @param int $userId
	 * @return int
	 */
	protected function getLifetimeInSeconds( $userId )
	{
		$remember = filter_input( INPUT_POST, 'rememberme' );
		$lifetimeInDays = empty( $remember ) ? 2 : 14;
		return (int)apply_filters( 'auth_cookie_expiration',
			$lifetimeInDays * DAY_IN_SECONDS,
			$userId,
			$remember
		);
	}

	/**
	 * @param string $username
	 * @param string $password
	 * @return string
	 */
	protected function getToken( WP_User $user, $password )
	{
		$data = [
			'identification' => $user->user_login,
			'lifetime' => $this->getLifetimeInSeconds( $user->ID ),
			'password' => $password,
		];
		$response = $this->sendPostRequest( '/api/token', $data );
		apply_filters( 'logger', [$data, $response] );
		return isset( $response['token'] )
			? $response['token']
			: '';
	}

	/**
	 * @param string $password
	 * @return void
	 */
	protected function login( WP_User $user, $password )
	{
		$token = $this->getToken( $user, $password );
		if( empty( $token )) {
			$this->signup( $user->user_login, $password, $user->user_email );
			$token = $this->getToken( $user, $password );
		}
		apply_filters( 'logger', 'logged in to flarum' );
		$this->setRememberMeCookie( $token, $user );
	}

	/**
	 * @return void
	 */
	protected function logout()
	{
		$this->removeRememberMeCookie();
	}

	/**
	 * @return void
	 */
	protected function redirectToFlarum()
	{
		wp_redirect( $this->settings->flarum_url );
		exit;
	}

	/**
	 * @return void
	 */
	protected function removeRememberMeCookie()
	{
		unset( $_COOKIE[static::REMEMBER_ME_KEY] );
		$this->setCookie( static::REMEMBER_ME_KEY, '', time() - 10 );
		apply_filters( 'logger', 'logged out of flarum' );
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @param int $expire
	 * @return void
	 */
	protected function setCookie( $name, $value, $expire, $path = '/' )
	{
		setcookie( $name, $value, $expire, $path, parse_url( home_url(), PHP_URL_HOST ));
	}

	/**
	 * @param string $path
	 * @param array $data
	 * @return array
	 */
	protected function sendPostRequest( $path, $data )
	{
		$dataString = json_encode( $data );
		$url = home_url( $this->settings->flarum_url, is_ssl() ? 'https' : 'http' );
		$ch = curl_init( untrailingslashit( $url ).$path );
		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST' );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $dataString );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false ); // for development
		curl_setopt( $ch, CURLOPT_HTTPHEADER, [
			'Authorization: Token '.$this->settings->flarum_api_key.'; userId=1',
			'Content-Length: '.strlen( $dataString ),
			'Content-Type: application/json',
		]);
		$result = curl_exec( $ch );
		apply_filters( 'logger', [curl_getinfo( $ch ), curl_error( $ch )] );
		return json_decode( $result, true );
	}

	/**
	 * @param string $token
	 * @return void
	 */
	protected function setRememberMeCookie( $token, WP_User $user )
	{
		$expiry = filter_input( INPUT_POST, 'rememberme' )
			? time() + $this->getLifetimeInSeconds( $user->ID )// + ( 12 * HOUR_IN_SECONDS ) //login grace period
			: 0;
		$this->setCookie( static::REMEMBER_ME_KEY, $token, $expiry );
	}

	/**
	 * @param string $username
	 * @param string $password
	 * @param string $email
	 * @return bool
	 */
	protected function signup( $username, $password, $email )
	{
		$data = [
			"data" => [
				"type" => "users",
				"attributes" => [
					"username" => $username,
					"password" => $password,
					"email" => $email,
				]
			]
		];
		apply_filters( 'logger', ['creating flarum user', $data] );
		$response = $this->sendPostRequest( '/api/users', $data );
		return isset( $response['data']['id'] );
	}
}
