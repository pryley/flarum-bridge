<?php

namespace GeminiLabs\FlarumBridge;

use GeminiLabs\FlarumBridge\Flarum;
use WP_Comment
use WP_Post
use WP_User;

class Avatar
{
	/**
	 * Flarum
	 */
	protected $flarum;

	public function __construct( Flarum $flarum )
	{
		$this->flarum = $flarum;
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
		$flarumUser = $flarum->users()->id( get_user_meta( $user->ID, '_flarum_id', true ));
		if( $flarumUser && !empty( $flarumUser['avatar'] )) {
			return $flarumUser['avatar'];
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
			$possibleUser = get_user_by( 'id', absint( $idOrEmail )) {
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
