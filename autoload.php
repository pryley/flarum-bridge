<?php

defined( 'WPINC' ) || die;

spl_autoload_register( function( $className ) {
	$namespaces = array(
		'GeminiLabs\\FlarumBridge\\' => __DIR__.'/plugin/',
	);
	foreach( $namespaces as $prefix => $base_dir ) {
		$len = strlen( $prefix );
		if( strncmp( $prefix, $className, $len ) !== 0 )continue;
		$file = $base_dir.str_replace( '\\', '/', substr( $className, $len )).'.php';
		if( !file_exists( $file ))continue;
		require $file;
		break;
	}
});

require_once( ABSPATH.'/wp-admin/includes/file.php' );
