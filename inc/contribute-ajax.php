<?php
/**
 * Deprecated direct contribution endpoint.
 *
 * This file is intentionally disabled. Contributions must go through
 * WordPress admin-ajax.php and the io_contribute() handler.
 */
if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.1 403 Forbidden' );
	header( 'Content-Type: text/plain; charset=UTF-8' );
	echo 'Forbidden';
	exit;
}

wp_die(
	esc_html__( '该投稿入口已禁用。', 'i_theme' ),
	esc_html__( 'Forbidden', 'i_theme' ),
	array( 'response' => 403 )
);