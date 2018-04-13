<?php

namespace GeminiLabs\FlarumBridge;

use Flagrow\Flarum\Api\Flarum;
use Flagrow\Flarum\Api\Resource\Collection;
use Flagrow\Flarum\Api\Resource\Item;
use WP_Error;
use WP_User;

class Flarum
{
	const FLARUM_COOKIE_NAME = 'flarum_remember';

	protected $api;

	public function __construct( $settings )
	{
		$this->api = new Flarum( home_url( $settings->flarum_url ), [
			'token' => $settings->api_key,
		]);
	}

	/**
	 * @return Item|object
	 */
	public function createUser( array $attributes )
	{
		return $this->api->users()->post([
			'attributes' => $attributes,
			'type' => 'users',
		])->request();
	}

	/**
	 * @param int $userId
	 * @return Item|object
	 */
	public function editUser( $userId, array $attributes )
	{
		return $this->api->users()->id( $userId )->patch([
			'attributes' => $attributes,
			'id' => $userId,
			'type' => 'users',
		])->request();
	}

	/**
	 * @param WP_User|null $user
	 * @param string $username
	 * @param string $password
	 * @return null|WP_User
	 * @filter authenticate
	 */
	public function filterAuthentication( $user, $username, $password )
	{
		if( $user instanceof WP_User ) {
			$this->loginUser( $user, $password );
		}
		return $user;
	}

	/**
	 * @param string $redirect
	 * @param string $requested_redirect
	 * @param WP_User|WP_Error $user
	 * @return string|void
	 * @filter login_redirect
	 */
	public function filterLoginRedirect( $redirect, $requestedRedirect, $user )
	{
		if( $redirect === 'forum' && $user instanceof WP_User ) {
			wp_redirect( $this->settings->flarum_url );
			exit;
		}
		return $redirect;
	}

	/**
	 * @param int $userId
	 * @return Item|object
	 */
	public function getUser( $userId )
	{
		return $this->api->users()->id( $userId )->request();
	}

	/**
	 * @param string $password
	 * @return void
	 */
	public function loginUser( WP_User $user, $password )
	{
		$authorization = $this->getAuthorization( $user, $password );
		if( !isset( $authorization->token )) {
			$flarumUser = $this->createUser([
				'email' => $user->user_email,
				'password' => $password,
				'username' => $user->user_login,
			]);
			$authorization = $this->getAuthorization( $user, $password );
		}
		if( isset( $authorization->token )) {
			update_user_meta( $user->ID, '_flarum_id', $authorization->userId );
			$this->setRememberMeCookie( $authorization->token, $user );
			apply_filters( 'logger', 'logged in to flarum' );
		}
	}

	/**
	 * @return void
	 * @action wp_logout
	 */
	public function logoutUser()
	{
		$this->setCookie( static::FLARUM_COOKIE_NAME, '', time() - 10 );
		unset( $_COOKIE[static::FLARUM_COOKIE_NAME] );
		apply_filters( 'logger', 'logged out of flarum' );
	}

	/**
	 * @param string $password
	 * @return object|false
	 */
	protected function getAuthorization( WP_User $user, $password )
	{
		$authorization = $this->api->authenticate([
			'identification' => $user->user_email,
			'lifetime' => $this->getLifetimeInSeconds( $user->ID ),
			'password' => $password,
		]);
		apply_filters( 'logger', $authorization );
		return isset( $authorization->token )
			? $authorization
			: false;
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
	 * @param string $token
	 * @return void
	 */
	protected function setRememberMeCookie( $token, WP_User $user )
	{
		$expiry = filter_input( INPUT_POST, 'rememberme' )
			? time() + $this->getLifetimeInSeconds( $user->ID )// + ( 12 * HOUR_IN_SECONDS ) //login grace period
			: 0;
		$this->setCookie( static::FLARUM_COOKIE_NAME, $token, $expiry );
	}
}
