<?php
/*
 * @Author: iowen
 * @Author URI: https://www.iowen.cn/
 * @Date: 2021-02-21 21:26:02
 * @LastEditors: iowen
 * @LastEditTime: 2024-07-30 19:49:22
 * @FilePath: /WebStack/header.php
 * @Description: 
 */ 
if ( ! defined( 'ABSPATH' ) ) { exit; } 

// SEO：首页标题/描述/关键词（主题设置 → SEO设置）
// 描述为空时搜索引擎会自行抓取页面内容作摘要（可能抓到站内某个网址的介绍），建议填写
$io_home_title = trim( (string) io_get_option( 'seo_home_title', '' ) );
$io_home_desc  = trim( (string) io_get_option( 'seo_home_desc', '' ) );
$io_home_kw    = trim( (string) io_get_option( 'seo_home_keywords', '' ) );
$io_is_home    = ( is_home() || is_front_page() );

if ( '' === $io_home_title ) {
	$io_home_title = get_bloginfo( 'name' ) . ' | ' . get_bloginfo( 'description' );
}
if ( '' === $io_home_desc ) {
	$io_home_desc = get_bloginfo( 'description' );
}
$io_title = $io_is_home ? $io_home_title : wp_get_document_title();
$io_url   = $io_is_home ? home_url() : home_url( $GLOBALS['wp']->request );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title><?php echo esc_html( $io_title ); ?></title>
<meta name="theme-color" content="#2C2E2F" />
<meta name="keywords" content="<?php echo esc_attr( $io_home_kw ); ?>">
<meta name="description" content="<?php echo esc_attr( $io_home_desc ); ?>">
<meta property="og:type" content="<?php echo $io_is_home ? 'website' : 'article'; ?>">
<meta property="og:url" content="<?php echo esc_url( $io_url ); ?>">
<meta property="og:title" content="<?php echo esc_attr( $io_title ); ?>">
<meta property="og:description" content="<?php echo esc_attr( $io_home_desc ); ?>">
<meta property="og:image" content="<?php echo get_theme_file_uri( '/screenshot.jpg' ); ?>">
<meta property="og:site_name" content="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
<link rel="shortcut icon" href="<?php echo esc_url( io_get_option( 'favicon', get_theme_file_uri( '/images/favicon.png' ) ) ); ?>">
<link rel="apple-touch-icon" href="<?php echo esc_url( io_get_option( 'apple_icon', get_theme_file_uri( '/images/app-ico.png' ) ) ); ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+SC:wght@400;500;600;700&display=swap" rel="stylesheet">
<?php wp_head(); ?>
</head> 
 <body <?php body_class('page-body apple-ui '.io_get_option('theme_mode')) ?>>
    <div class="apple-aurora" aria-hidden="true">
      <span class="aurora-orb aurora-orb-1"></span>
      <span class="aurora-orb aurora-orb-2"></span>
      <span class="aurora-orb aurora-orb-3"></span>
    </div>
    <div class="page-container">
