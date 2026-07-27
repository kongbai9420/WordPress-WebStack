<?php
/**
 * Safe external redirect endpoint for WebStack.
 *
 * The original implementation decoded arbitrary input and printed it directly
 * into a meta refresh tag, which made the page vulnerable to open redirects and
 * reflected HTML/URL injection. Keep this file server-side only and validate
 * the target URL before redirecting.
 */
if ( ! defined( 'ABSPATH' ) ) {
	require dirname( __FILE__ ) . '/../../../wp-load.php';
}

$target = isset( $_GET['url'] ) ? rawurldecode( wp_unslash( $_GET['url'] ) ) : '';
$target = trim( $target );

// Only allow explicit http/https URLs.
if ( empty( $target ) || ! preg_match( '#^https?://#i', $target ) || ! wp_http_validate_url( $target ) ) {
	wp_die(
		esc_html__( '无效或不安全的跳转地址。', 'i_theme' ),
		esc_html__( '跳转被阻止', 'i_theme' ),
		array( 'response' => 400 )
	);
}

nocache_headers();
wp_redirect( esc_url_raw( $target ), 302, 'WebStack' );
exit;
