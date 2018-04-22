<?php

namespace GeminiLabs\FlarumBridge;

use GeminiLabs\FlarumBridge\Database;
use GeminiLabs\FlarumBridge\Flarum;
use WP_Comment;
use WP_Post;
use WP_User;

class Avatar
{
	/**
	 * Flarum
	 */
	protected $api;

	/**
	 * Database
	 */
	protected $db;

	/**
	 * string
	 */
	protected $flarumUsername;

	public function __construct( Flarum $api, Database $db )
	{
		$this->api = $api;
		$this->db = $db;
	}

	/**
	 * @param array $args
	 * @param int|string|WP_Comment|WP_Post|WP_User $idOrEmail
	 * @return array
	 * @filter pre_get_avatar_data
	 */
	public function filterAvatarData( $args, $idOrEmail )
	{
		$user = $this->getUserFrom( $idOrEmail );
		if( $user instanceof WP_User ) {
			// $args['found_avatar'] = true;
			$args['url'] = $this->getAvatar( $user );
		}
		return $args;
	}

	/**
	 * @param string $translation
	 * @param string $text
	 * @param string $domain
	 * @return string
	 * @filter gettext
	 */
	public function filterGettext( $translation, $text, $domain )
	{
		if( $domain != 'default' || !is_admin() ) {
			return $translation;
		}
		if( $text == 'You can change your profile picture on <a href="%s">Gravatar</a>.' ) {
			return __( 'You can change your profile picture on the <a href="%s">forum</a>.', 'flarum-bridge' );
		}
		if( $text == 'https://en.gravatar.com/' ) {
			return trailingslashit( $this->db->getSettings()->flarum_url ).'u/'.$this->flarumUsername;
		}
		return $translation;
	}

	/**
	 * @return null|string
	 */
	public function getAvatar( WP_User $user )
	{
		$flarumUserId = intval( get_user_meta( $user->ID, '_flarum_id', true ));
		if( $flarumUserId ) {
			$flarumUser = $this->api->getUser( $flarumUserId );
			if( !empty( $flarumUser->attributes['avatarUrl'] )) {
				$this->flarumUsername = $flarumUser->username;
				add_filter( 'gettext', [$this, 'filterGettext'], 10, 3 );
				return $flarumUser->avatarUrl;
			}
		}
		return null;
	}

	/**
	 * @param int|string|WP_Comment|WP_Post|WP_User $idOrEmail
	 * @return false|WP_User|WP_Comment
	 */
	protected function getUserFrom( $idOrEmail )
	{
		$possibleUser = $idOrEmail;
		if( is_numeric( $idOrEmail )) {
			$possibleUser = get_user_by( 'id', absint( $idOrEmail ));
		}
		else if( is_string( $idOrEmail )) {
			$possibleUser = get_user_by( 'email', $idOrEmail );
		}
		else if( $idOrEmail instanceof WP_Post ) {
			$possibleUser = get_user_by( 'id', absint( $idOrEmail->post_author ));
		}
		return $possibleUser;
	}
}
