<?php

namespace GeminiLabs\FlarumBridge;

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

	public function __construct( Flarum $api )
	{
		$this->api = $api;
	}

	/**
	 * @param array $args
	 * @param int|string|WP_Comment|WP_Post|WP_User $idOrEmail
	 * @return array
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
	 * @return null|string
	 */
	public function getAvatar( WP_User $user )
	{
		$flarumUserId = intval( get_user_meta( $user->ID, '_flarum_id', true ));
		if( $flarumUserId ) {
			$flarumUser = $this->api->getUser( $flarumUserId );
			if( $flarumUser->avatarUrl ) {
				return $flarumUser->avatarUrl;
			}
		}
		return null;
	}

	/**
	 * @param int|string|WP_Comment|WP_Post|WP_User $idOrEmail
	 * @return false|WP_User
	 */
	protected function getUserFrom( $idOrEmail )
	{
		$possibleUser = false;
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
