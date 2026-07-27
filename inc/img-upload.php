<?php
/**
 * Deprecated direct upload endpoint.
 *
 * This file is intentionally disabled. Image uploads must go through
 * WordPress admin-ajax.php and the hardened io_img_upload() handler.
 */
if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.1 403 Forbidden' );
	header( 'Content-Type: text/plain; charset=UTF-8' );
	echo 'Forbidden';
	exit;
}

wp_die(
	esc_html__( '该上传入口已禁用。', 'i_theme' ),
	esc_html__( 'Forbidden', 'i_theme' ),
	array( 'response' => 403 )
);