<?php

namespace GeminiLabs\FlarumBridge;

use ReflectionClass;

class Log
{
	const EMERGENCY = 'emergency';
	const ALERT = 'alert';
	const CRITICAL  = 'critical';
	const ERROR = 'error';
	const WARNING = 'warning';
	const NOTICE = 'notice';
	const INFO = 'info';
	const DEBUG = 'debug';

	protected $file;
	protected $log;

	public function __construct( $filename )
	{
		$this->file = $filename;
		$this->log = file_exists( $filename )
			? file_get_contents( $filename )
			: '';
	}

	public function __toString()
	{
		return $this->log;
	}

	/**
	 * Action must be taken immediately.
	 * Example: Entire website down, database unavailable, etc. This should
	 * trigger the SMS alerts and wake you up.
	 *
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function alert( $message, array $context = [] )
	{
		$this->log( static::ALERT, $message, $context );
	}

	/**
	 * @return void
	 */
	public function clear()
	{
		$this->log = '';
		file_put_contents( $this->file, $this->log );
	}

	/**
	 * Critical conditions.
	 * Example: Application component unavailable, unexpected exception.
	 *
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function critical( $message, array $context = [] )
	{
		$this->log( static::CRITICAL, $message, $context );
	}

	/**
	 * Detailed debug information.
	 *
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function debug( $message, array $context = [] )
	{
		$this->log( static::DEBUG, $message, $context );
	}

	/**
	 * System is unusable.
	 *
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function emergency( $message, array $context = [] )
	{
		$this->log( static::EMERGENCY, $message, $context );
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function error( $message, array $context = [] )
	{
		$this->log( static::ERROR, $message, $context );
	}

	/**
	 * Interesting events.
	 * Example: User logs in, SQL logs.
	 *
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function info( $message, array $context = [] )
	{
		$this->log( static::INFO, $message, $context );
	}

	/**
	 * Normal but significant events.
	 *
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function notice( $message, array $context = [] )
	{
		$this->log( static::NOTICE, $message, $context );
	}

	/**
	 * Exceptional occurrences that are not errors.
	 * Example: Use of deprecated APIs, poor use of an API, undesirable things
	 * that are not necessarily wrong.
	 *
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function warning( $message, array $context = [] )
	{
		$this->log( static::WARNING, $message, $context );
	}

	/**
	 * @param string $message
	 * @param array $context
	 * @return array|string
	 */
	protected function interpolate( $message, array $context = [] )
	{
		if( is_array( $message )) {
			// return htmlspecialchars( print_r( $message, true ), ENT_QUOTES, 'UTF-8' );
			return print_r( $message, true );
		}
		$replace = [];
		foreach( $context as $key => $val ) {
			if( is_object( $val ) && get_class( $val ) === 'DateTime' ) {
				$val = $val->format( 'Y-m-d H:i:s' );
			}
			else if( is_object( $val ) || is_array( $val )) {
				$val = json_encode( $val );
			}
			else if( is_resource( $val )) {
				$val = (string) $val;
			}
			$replace['{'.$key.'}'] = $val;
		}
		return strtr( $message, $replace );
	}

	/**
	 * @param mixed $level
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	protected function log( $level, $message, array $context = [] )
	{
		$constants = (new ReflectionClass( __NAMESPACE__.'\Log' ))->getConstants();
		$constants = (array)apply_filters( 'flarum-bridge/log-levels', $constants );
		if( !in_array( $level, $constants, true ))return;
		$date = get_date_from_gmt( gmdate('Y-m-d H:i:s') );
		$level = strtoupper( $level );
		$message = $this->interpolate( $message, $context );
		$entry = "[$date] $level: $message" . PHP_EOL;
		file_put_contents( $this->file, $entry, FILE_APPEND|LOCK_EX );
	}
}
