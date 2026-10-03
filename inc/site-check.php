<?php
/*
 * @Theme Name:WebStack
 * @Description: 后台「网址」列表页一键测活 + 批量删除（失效站点移入回收站）
 * 测活结果写入 post meta：
 *   _sites_check_status  alive / dead / unknown
 *   _sites_check_code    HTTP 状态码（0 = 网络层失败）
 *   _sites_check_msg     失败原因描述
 *   _sites_check_time    最近检测时间（时间戳）
 * 安全：仅管理员（manage_options），全部 AJAX 带 nonce 校验（check_ajax_referer）
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * 规范化检测用 URL：自动补全协议，返回规范化后的 URL；非法则返回 false
 *
 * @param string $url
 * @return string|false
 */
function io_normalize_check_url( $url ) {
	$url = trim( (string) $url );

	// 无链接（例如仅填了公众号二维码的站点）
	if ( '' === $url ) {
		return false;
	}

	// 兼容历史数据中不带协议的链接（www.example.com 或 //example.com），自动补全 https://
	if ( 0 === strpos( $url, '//' ) ) {
		$url = 'https:' . $url;
	} elseif ( ! preg_match( '#^https?://#i', $url ) ) {
		$url = 'https://' . $url;
	}

	// 补全后仍非法（含空格等）
	if ( ! preg_match( '#^https?://\S+$#i', $url ) ) {
		return false;
	}

	return $url;
}

/**
 * 根据 HTTP 状态码/错误统一判定结果
 *
 * @param int    $code HTTP 状态码（0 = 网络层失败）
 * @param string $err  错误信息（如 curl 错误）
 * @param int    $time 检测时间戳
 * @return array{status:string, code:int, msg:string, time:int}
 */
function io_check_classify( $code, $err, $time ) {
	if ( ! empty( $err ) ) {
		// 网络层失败（超时 / 连接失败 / DNS / SSL）
		// 按主题设置「网址测活 → 网络层失败处理」：默认判失效，可选判未检测（避免直连被墙的国外站被误删）
		if ( 'unknown' === io_get_option( 'io_site_check_net_fail', 'dead' ) ) {
			return array( 'status' => 'unknown', 'code' => 0, 'msg' => $err, 'time' => $time );
		}
		return array( 'status' => 'dead', 'code' => 0, 'msg' => $err, 'time' => $time );
	}

	if ( $code > 0 && $code < 400 ) {
		// 2xx/3xx 视为存活（已跟随重定向）
		return array( 'status' => 'alive', 'code' => $code, 'msg' => '', 'time' => $time );
	}

	if ( $code <= 0 ) {
		return array( 'status' => 'dead', 'code' => 0, 'msg' => __( '网络连接失败', 'i_theme' ), 'time' => $time );
	}

	// 403/429/503：服务器明确响应，多为反爬人机验证/限流/临时不可用（如 Cloudflare 挑战页），网站本身存活，不误判为失效
	if ( 403 === $code || 429 === $code || 503 === $code ) {
		$msg = ( 503 === $code )
			? sprintf( __( 'HTTP %d（服务器暂时不可用，可能被反爬拦截，网站可能正常）', 'i_theme' ), $code )
			: sprintf( __( 'HTTP %d（疑似反爬拦截，网站可能正常）', 'i_theme' ), $code );
		return array( 'status' => 'alive', 'code' => $code, 'msg' => $msg, 'time' => $time );
	}

	// 404/410：页面不存在，视为失效
	if ( 404 === $code || 410 === $code ) {
		return array(
			'status' => 'dead',
			'code'   => $code,
			'msg'    => sprintf( __( 'HTTP %d（页面不存在）', 'i_theme' ), $code ),
			'time'   => $time,
		);
	}

	// 其余 4xx/5xx 视为失效（服务器错误或访问被明确拒绝）
	return array(
		'status' => 'dead',
		'code'   => $code,
		'msg'    => sprintf( __( 'HTTP 状态码 %d', 'i_theme' ), $code ),
		'time'   => $time,
	);
}

/**
 * 测活单个网址（串行，用于不支持 curl_multi 时的兜底）
 *
 * @param string $url 网站链接
 * @return array{status:string, code:int, msg:string, time:int}
 */
function io_check_site_url( $url ) {
	$time = time();
	$url  = io_normalize_check_url( $url );

	if ( false === $url ) {
		return array( 'status' => 'unknown', 'code' => 0, 'msg' => __( '链接为空或格式无效', 'i_theme' ), 'time' => $time );
	}

	$args = array(
		'timeout'     => 10,   // 单站点超时（秒）
		'redirection' => 3,    // 最多跟随 3 次重定向
		'user-agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
		// 默认宽松校验证书：避免因证书过期/自签导致误判，可用 io_site_check_sslverify 过滤器覆盖
		'sslverify'   => apply_filters( 'io_site_check_sslverify', false ),
		'headers'     => array(
			'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
			'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8',
		),
	);

	$response = wp_remote_get( $url, $args );

	if ( is_wp_error( $response ) ) {
		// https 失败时降级 http 重试一次（部分站点 https 握手被拦但 http 可达）
		if ( 0 === strpos( $url, 'https://' ) ) {
			$http_url  = 'http://' . substr( $url, 8 );
			$response  = wp_remote_get( $http_url, $args );
		}
		if ( is_wp_error( $response ) ) {
			return array(
				'status' => 'dead',
				'code'   => 0,
				'msg'    => $response->get_error_message(),
				'time'   => $time,
			);
		}
	}

	return io_check_classify( (int) wp_remote_retrieve_response_code( $response ), '', $time );
}

/**
 * 用 cURL 多句柄并行检测多个网址
 * 一批同时发起请求，总耗时 ≈ 最慢的一个（受单站超时限制），远快于串行逐个检测
 *
 * @param array $items 站点 ID => 原始链接（可含空值/无协议）
 * @return array 站点 ID => 检测结果数组
 */
function io_check_sites_parallel( $items ) {
	$time     = time();
	$fallback = array();
	$first    = array(); // 站点 ID => 规范化 URL
	$alt      = array(); // 站点 ID => 备用协议 URL（https <-> http）

	foreach ( $items as $id => $raw ) {
		$url = io_normalize_check_url( $raw );
		if ( false === $url ) {
			// 无链接/格式非法 → 直接 unknown，不进并行
			$fallback[ $id ] = array( 'status' => 'unknown', 'code' => 0, 'msg' => __( '链接为空或格式无效', 'i_theme' ), 'time' => $time );
			continue;
		}

		$first[ $id ] = $url;
		if ( 0 === strpos( $url, 'https://' ) ) {
			$alt[ $id ] = 'http://' . substr( $url, 8 );
		} elseif ( 0 === strpos( $url, 'http://' ) ) {
			$alt[ $id ] = 'https://' . substr( $url, 7 );
		}
	}

	$results = array();
	if ( ! empty( $first ) ) {
		$results = io_curl_multi_fetch( $first );
	}

	// 第二轮：网络层失败（超时/连接失败）的站点换协议重试一次，
	// 减少误判——部分站点 https 握手被拦/超时但 http 可达（浏览器正常而 curl 失败）
	$retry = array();
	foreach ( $results as $id => $r ) {
		if ( '' !== $r['err'] && isset( $alt[ $id ] ) ) {
			$retry[ $id ] = $alt[ $id ];
		}
	}
	if ( ! empty( $retry ) ) {
		$second = io_curl_multi_fetch( $retry );
		foreach ( $second as $id => $r2 ) {
			$results[ $id ] = $r2;
		}
	}

	foreach ( $results as $id => $r ) {
		$fallback[ $id ] = io_check_classify( $r['code'], $r['err'], $time );
	}

	return $fallback;
}

/**
 * 用 cURL 多句柄并行抓取一批 URL
 *
 * @param array $urls 站点 ID => 规范化 URL
 * @return array 站点 ID => array( 'code'=>int, 'err'=>string )
 */
function io_curl_multi_fetch( $urls ) {
	$mh      = curl_multi_init();
	$handles = array();
	$results = array();

	// 测活代理（主题设置「网址测活 → 测活代理地址」），留空则直连
	$proxy = trim( (string) io_get_option( 'io_site_check_proxy', '' ) );

	foreach ( $urls as $id => $url ) {
		$ch = curl_init();
		$opt = array(
			CURLOPT_URL            => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS      => 3,
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_TIMEOUT        => 10,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => 0,
			// 不强制协议族：curl 自动选择 IPv4/IPv6 中可达的一个（happy eyeballs），
			// 部分网络 IPv6 出口直连国外站更通畅，强制 IPv4 反而导致失败
			CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
			CURLOPT_ENCODING       => '', // 接受 gzip/br 压缩
			CURLOPT_HTTPHEADER     => array(
				'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
				'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
				'Connection: keep-alive',
			),
			CURLOPT_NOSIGNAL       => true,
		);

		// 走代理（HTTP/HTTPS/SOCKS），并对 HTTPS 目标使用 CONNECT 隧道
		if ( '' !== $proxy ) {
			$opt[ CURLOPT_PROXY ] = $proxy;
			$opt[ CURLOPT_HTTPPROXYTUNNEL ] = true;
			// 根据协议前缀显式设置代理类型，兼容不同版本 curl
			if ( 0 === strpos( $proxy, 'socks5h://' ) ) {
				$opt[ CURLOPT_PROXYTYPE ] = CURLPROXY_SOCKS5_HOSTNAME;
			} elseif ( 0 === strpos( $proxy, 'socks5://' ) ) {
				$opt[ CURLOPT_PROXYTYPE ] = CURLPROXY_SOCKS5;
			} elseif ( 0 === strpos( $proxy, 'socks4a://' ) ) {
				$opt[ CURLOPT_PROXYTYPE ] = CURLPROXY_SOCKS4A;
			} elseif ( 0 === strpos( $proxy, 'socks4://' ) ) {
				$opt[ CURLOPT_PROXYTYPE ] = CURLPROXY_SOCKS4;
			} elseif ( 0 === strpos( $proxy, 'https://' ) ) {
				$opt[ CURLOPT_PROXYTYPE ] = CURLPROXY_HTTPS;
			} else {
				$opt[ CURLOPT_PROXYTYPE ] = CURLPROXY_HTTP;
			}
		}

		curl_setopt_array( $ch, $opt );

		curl_multi_add_handle( $mh, $ch );
		$handles[ $id ] = $ch;
	}

	if ( ! empty( $handles ) ) {
		$running = 0;
		// 只以 $running 判断是否完成：某些平台 curl_multi_exec 的返回值不稳定，
		// 若依赖它判断可能提前退出，导致未完成请求拿不到真实错误（只剩笼统的“无法连接”）
		do {
			curl_multi_exec( $mh, $running );
			if ( $running ) {
				$select = curl_multi_select( $mh, 0.1 );
				if ( -1 === $select ) {
					usleep( 10000 ); // 部分平台 curl_multi_select 不可用，防止忙等
				}
			}
		} while ( $running > 0 );

		foreach ( $handles as $id => $ch ) {
			$code = (int) curl_getinfo( $ch, CURLINFO_RESPONSE_CODE );
			$err  = curl_error( $ch );
			if ( '' === $err && $code <= 0 ) {
				$err = __( '连接失败（未收到服务器响应）', 'i_theme' );
			}
			$results[ $id ] = array( 'code' => $code, 'err' => $err );
			curl_multi_remove_handle( $mh, $ch );
			curl_close( $ch );
		}

		curl_multi_close( $mh );
	}

	return $results;
}

/**
 * 统计各检测状态下的站点数量
 *
 * @return array{total:int, alive:int, dead:int, unknown:int, unchecked:int}
 */
function io_site_check_stats() {
	global $wpdb;

	$counts = wp_count_posts( 'sites' );

	$stats = array(
		'total'     => isset( $counts->publish ) ? (int) $counts->publish : 0,
		'alive'     => 0,
		'dead'      => 0,
		'unknown'   => 0,
		'unchecked' => 0,
	);

	// 一次 SQL 统计三种状态，避免三次全量 ID 查询
	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT pm.meta_value AS st, COUNT(*) AS c
		 FROM {$wpdb->postmeta} pm
		 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		 WHERE pm.meta_key = %s AND p.post_type = %s AND p.post_status = %s
		 GROUP BY pm.meta_value",
		'_sites_check_status',
		'sites',
		'publish'
	) );

	if ( is_array( $rows ) ) {
		foreach ( $rows as $row ) {
			if ( isset( $stats[ $row->st ] ) ) {
				$stats[ $row->st ] = (int) $row->c;
			}
		}
	}

	$stats['unchecked'] = max( 0, $stats['total'] - $stats['alive'] - $stats['dead'] - $stats['unknown'] );

	return $stats;
}

// =====================================================================
// 以下全部功能受主题设置「网址测活 → 启用一键测活」控制，关闭后均不注册
// =====================================================================
if ( io_get_option( 'io_site_check_enable', true ) ) :

/**
 * AJAX：仅返回当前检测统计（列表页加载时展示，无副作用）
 */
add_action( 'wp_ajax_io_site_check_stats', 'io_site_check_stats_ajax' );
function io_site_check_stats_ajax() {
	check_ajax_referer( 'io_site_check_nonce', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'msg' => __( '没有权限执行此操作。', 'i_theme' ) ) );
	}

	wp_send_json_success( io_site_check_stats() );
}

/**
 * 读取检测队列（transient）
 */
function io_site_check_queue_get() {
	$queue = get_transient( 'io_site_check_queue' );
	if ( is_array( $queue ) && isset( $queue['ids'] ) && is_array( $queue['ids'] ) ) {
		return $queue;
	}
	return array( 'ids' => array(), 'total' => 0 );
}

/**
 * 写入检测队列
 *
 * @param array $ids 站点 ID 列表
 */
function io_site_check_queue_set( $ids ) {
	$ids = array_values( array_map( 'absint', (array) $ids ) );
	set_transient(
		'io_site_check_queue',
		array(
			'ids'   => $ids,
			'total' => count( $ids ),
		),
		2 * HOUR_IN_SECONDS
	);
}

/**
 * AJAX：初始化/续测检测队列
 * 非强制重来时，若存在未完成的队列则返回续测信息，否则重建全量队列
 */
add_action( 'wp_ajax_io_site_check_init', 'io_site_check_init' );
function io_site_check_init() {
	check_ajax_referer( 'io_site_check_nonce', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'msg' => __( '没有权限执行此操作。', 'i_theme' ) ) );
	}

	$restart = ! empty( $_POST['restart'] );

	if ( ! $restart ) {
		$queue = io_site_check_queue_get();
		if ( ! empty( $queue['ids'] ) ) {
			wp_send_json_success( array(
				'resuming'  => true,
				'total'     => (int) $queue['total'],
				'remaining' => count( $queue['ids'] ),
				'stats'     => io_site_check_stats(),
			) );
		}
	}

	$query = new WP_Query( array(
		'post_type'      => 'sites',
		'post_status'    => 'publish',
		'fields'         => 'ids',
		'posts_per_page' => -1,
		'no_found_rows'  => true,
		'orderby'        => 'ID',
		'order'          => 'ASC',
	) );

	io_site_check_queue_set( $query->posts );

	wp_send_json_success( array(
		'resuming'  => false,
		'total'     => count( $query->posts ),
		'remaining' => count( $query->posts ),
		'stats'     => io_site_check_stats(),
	) );
}

/**
 * AJAX：分批测活（并行）
 * 每次从队列取出至多 batch 个站点，用 curl_multi 同时发起请求（无 curl 时串行兜底），
 * 总耗时 ≈ 最慢的一个站点，远快于逐个串行；结果写入 meta 后返回
 */
add_action( 'wp_ajax_io_site_check', 'io_site_check' );
function io_site_check() {
	check_ajax_referer( 'io_site_check_nonce', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'msg' => __( '没有权限执行此操作。', 'i_theme' ) ) );
	}

	$queue = io_site_check_queue_get();
	$ids   = $queue['ids'];

	if ( empty( $ids ) ) {
		wp_send_json_success( array(
			'done'      => true,
			'checked'   => array(),
			'remaining' => 0,
			'stats'     => io_site_check_stats(),
		) );
	}

	// 并行检测数量以主题设置「网址测活 → 并行检测数量」为准（1~50），前端传入值仅作参考并受其上限约束
	$option_batch = max( 1, min( 50, absint( io_get_option( 'io_site_check_concurrency', 20 ) ) ) );
	$batch        = isset( $_POST['batch'] ) ? absint( $_POST['batch'] ) : 0;
	$batch        = max( 1, min( $option_batch, $batch > 0 ? $batch : $option_batch ) );
	$budget = 45; // 单次请求总耗时上限（秒），预算耗尽后不再发起新请求
	$start  = microtime( true );
	$results = array();

	@set_time_limit( 90 );

	// 先取一批站点 ID 与链接
	$items = array();
	while ( ! empty( $ids ) && count( $items ) < $batch ) {
		// 预算用尽立即停止，避免「已发出请求才检查」导致超时被 PHP 杀掉
		if ( ( microtime( true ) - $start ) >= $budget ) {
			break;
		}
		$id = (int) array_shift( $ids );
		$items[ $id ] = get_post_meta( $id, '_sites_link', true );
	}

	// 并行检测；服务器不支持 curl_multi 时退回串行
	if ( function_exists( 'curl_multi_init' ) ) {
		$checked = io_check_sites_parallel( $items );
	} else {
		$checked = array();
		foreach ( $items as $id => $url ) {
			$checked[ $id ] = io_check_site_url( $url );
		}
	}

	foreach ( $checked as $id => $r ) {
		update_post_meta( $id, '_sites_check_status', $r['status'] );
		update_post_meta( $id, '_sites_check_code', $r['code'] );
		update_post_meta( $id, '_sites_check_msg', $r['msg'] );
		update_post_meta( $id, '_sites_check_time', $r['time'] );

		$results[] = array( 'id' => $id ) + $r;
	}

	$queue['ids'] = $ids;
	set_transient( 'io_site_check_queue', $queue, 2 * HOUR_IN_SECONDS );

	$done = empty( $ids );

	wp_send_json_success( array(
		'done'      => $done,
		'checked'   => $results,
		'remaining' => count( $ids ),
		'stats'     => $done ? io_site_check_stats() : null,
	) );
}

/**
 * AJAX：删除全部失效站点（移入回收站，可恢复）
 */
add_action( 'wp_ajax_io_site_delete_all_dead', 'io_site_delete_all_dead' );
function io_site_delete_all_dead() {
	check_ajax_referer( 'io_site_check_nonce', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'msg' => __( '没有权限执行此操作。', 'i_theme' ) ) );
	}

	$query = new WP_Query( array(
		'post_type'      => 'sites',
		'post_status'    => 'publish',
		'fields'         => 'ids',
		'posts_per_page' => -1,
		'no_found_rows'  => true,
		'meta_key'       => '_sites_check_status',
		'meta_value'     => 'dead',
	) );

	$deleted = array();
	foreach ( $query->posts as $id ) {
		$id = absint( $id );
		// 第二个参数不传 true → 移入回收站，可恢复
		if ( wp_delete_post( $id, false ) ) {
			$deleted[] = $id;
		}
	}

	// 同步清理检测队列中的已删除站点，避免续测时重复检测
	if ( ! empty( $deleted ) ) {
		$queue = io_site_check_queue_get();
		if ( ! empty( $queue['ids'] ) ) {
			$queue['ids'] = array_values( array_diff( $queue['ids'], $deleted ) );
			set_transient( 'io_site_check_queue', $queue, 2 * HOUR_IN_SECONDS );
		}
	}

	wp_send_json_success( array(
		'count' => count( $deleted ),
		'ids'   => $deleted,
		'stats' => io_site_check_stats(),
	) );
}

/**
 * 从回收站恢复站点时清除检测结果，恢复后显示「未检测」
 */
add_action( 'untrashed_post', 'io_site_check_clear_meta_on_restore' );
function io_site_check_clear_meta_on_restore( $post_id ) {
	if ( 'sites' === get_post_type( $post_id ) ) {
		delete_post_meta( $post_id, '_sites_check_status' );
		delete_post_meta( $post_id, '_sites_check_code' );
		delete_post_meta( $post_id, '_sites_check_msg' );
		delete_post_meta( $post_id, '_sites_check_time' );
	}
}

/**
 * 失效站点行高亮（仅后台列表页）
 */
add_filter( 'post_class', 'io_site_check_post_class', 10, 3 );
function io_site_check_post_class( $classes, $class, $post_id ) {
	if ( is_admin() && 'sites' === get_post_type( $post_id ) && 'dead' === get_post_meta( $post_id, '_sites_check_status', true ) ) {
		$classes[] = 'io-row-dead';
	}
	return $classes;
}

/**
 * 仅后台「网址」列表页（edit-sites）加载测活脚本
 */
add_action( 'admin_enqueue_scripts', 'io_site_check_admin_enqueue' );
function io_site_check_admin_enqueue() {
	$screen = get_current_screen();
	if ( ! $screen || 'edit-sites' !== $screen->id ) {
		return;
	}

	wp_enqueue_script(
		'io-site-check',
		get_theme_file_uri( '/js/site-check.js' ),
		array( 'jquery' ),
		'2026.10.3',
		true
	);

	wp_localize_script( 'io-site-check', 'ioSiteCheck', array(
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'io_site_check_nonce' ),
		'batch'   => max( 1, min( 50, absint( io_get_option( 'io_site_check_concurrency', 20 ) ) ) ),
		'i18n'    => array(
			'start'         => __( '一键测活', 'i_theme' ),
			'selectDead'    => __( '勾选本页失效', 'i_theme' ),
			'deleteAllDead' => __( '删除全部失效', 'i_theme' ),
			'checking'      => __( '测活中…', 'i_theme' ),
			'confirmStart'  => __( '将对全部 %d 个站点进行测活，预计需要几分钟，期间请勿关闭页面。是否开始？', 'i_theme' ),
			'confirmResume' => __( '上次检测尚未完成（已检测 %d / %d），是否继续？选择取消则重新开始。', 'i_theme' ),
			'confirmDelete' => __( '确定将全部 %d 个失效站点移入回收站吗？可在回收站中恢复。', 'i_theme' ),
			'noSites'       => __( '没有可检测的站点。', 'i_theme' ),
			'noDead'        => __( '当前没有标记为失效的站点。', 'i_theme' ),
			'done'          => __( '测活完成', 'i_theme' ),
			'deleted'       => __( '已将 %d 个失效站点移入回收站', 'i_theme' ),
			'error'         => __( '操作失败，请刷新页面后重试', 'i_theme' ),
			'checked'       => __( '已检测', 'i_theme' ),
			'of'            => __( '/', 'i_theme' ),
			'normal'        => __( '正常', 'i_theme' ),
			'dead'          => __( '失效', 'i_theme' ),
			'unknown'       => __( '未检测', 'i_theme' ),
			'total'         => __( '共', 'i_theme' ),
			'uncheckedTip'  => __( '个未检测，请先一键测活', 'i_theme' ),
		),
	) );
}

/**
 * 列表页样式（徽章 + 失效行高亮 + 测活面板）
 */
add_action( 'admin_head', 'io_site_check_admin_css' );
function io_site_check_admin_css() {
	$screen = get_current_screen();
	if ( ! $screen || 'edit-sites' !== $screen->id ) {
		return;
	}
	echo '<style>
		.io-check-badge{display:inline-block;min-width:52px;padding:2px 10px;border-radius:10px;font-size:12px;line-height:20px;text-align:center;color:#fff}
		.io-check-badge.io-alive{background:#00a32a}
		.io-check-badge.io-dead{background:#d63638}
		.io-check-badge.io-unknown{background:#a7aaad}
		.io-check-msg{margin-top:2px;font-size:11px;line-height:1.4;color:#8c8f94;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
		tr.type-sites.io-row-dead{background:#fcf0f1}
		tr.type-sites.io-row-dead .row-title{color:#b32d2e}
		/* 测活面板：常驻卡片（苹果风：白底、圆角、浅阴影），位于表格上方，不与原生顶部区争位 */
		#io-site-check-panel{margin:16px 0;padding:10px 14px;background:#fff;border:1px solid rgba(0,0,0,.08);border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,.05)}
		#io-site-check-panel .io-toolbar{display:flex;flex-wrap:wrap;align-items:center;gap:8px}
		#io-site-check-panel .button{border-radius:6px}
		#io-site-check-panel .io-btn-danger{background:#d63638;border-color:#d63638;color:#fff}
		#io-site-check-panel .io-btn-danger:hover,#io-site-check-panel .io-btn-danger:focus{background:#b32d2e;border-color:#b32d2e;color:#fff}
		#io-site-check-panel .io-stats{margin-left:auto;color:#6e6e73;font-size:12px;white-space:nowrap}
		#io-site-check-panel .io-stats b{font-weight:600;color:#3a3a3c}
		#io-site-check-panel .io-stats .io-tip{color:#d63638}
		#io-site-check-panel .io-progress-wrap{display:none;margin-top:12px;padding-top:10px;border-top:1px solid #f2f2f7}
		#io-site-check-panel .io-progress{height:6px;background:#f2f2f7;border-radius:3px;overflow:hidden}
		#io-site-check-panel .io-progress-inner{height:100%;width:0;background:linear-gradient(90deg,#0a84ff,#30d158);transition:width .3s}
		#io-site-check-panel .io-progress-text{margin-top:8px;color:#6e6e73;font-size:12px}
	</style>';
}

/**
 * 列表页顶部添加「状态」筛选下拉（全部状态 / 正常 / 失效 / 未检测）
 */
add_action( 'restrict_manage_posts', 'io_site_check_restrict_manage', 10, 2 );
function io_site_check_restrict_manage( $post_type, $which ) {
	if ( 'sites' !== $post_type || 'top' !== $which ) {
		return;
	}
	$current = isset( $_GET['io_check_status'] ) ? sanitize_key( $_GET['io_check_status'] ) : '';
	$options = array(
		''        => __( '全部状态', 'i_theme' ),
		'alive'   => __( '正常', 'i_theme' ),
		'dead'    => __( '失效', 'i_theme' ),
		'unknown' => __( '未检测', 'i_theme' ),
	);
	echo '<select name="io_check_status">';
	foreach ( $options as $value => $label ) {
		printf( '<option value="%s"%s>%s</option>', esc_attr( $value ), selected( $current, $value, false ), esc_html( $label ) );
	}
	echo '</select>';
}

/**
 * 按「状态」筛选网址列表
 * 用 EXISTS 子查询实现，与 io_site_check_stats() 的统计 SQL 同构，
 * 保证「筛选结果」与「统计数字」永远一致（meta_query 曾出现筛选为空而统计有数的不一致）
 */
add_filter( 'posts_where', 'io_site_check_filter_by_status', 10, 2 );
function io_site_check_filter_by_status( $where, $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return $where;
	}

	// post_type 可能是字符串或数组，统一转数组判断
	$post_types = (array) $query->get( 'post_type' );
	if ( ! in_array( 'sites', $post_types, true ) ) {
		return $where;
	}

	$status = isset( $_GET['io_check_status'] ) ? sanitize_key( $_GET['io_check_status'] ) : '';
	if ( ! in_array( $status, array( 'alive', 'dead', 'unknown' ), true ) ) {
		return $where;
	}

	global $wpdb;

	if ( 'unknown' === $status ) {
		// 未检测 = 从未检测过（无 meta）或标记为 unknown（例如无链接的站点）
		$where .= $wpdb->prepare(
			" AND ( NOT EXISTS ( SELECT 1 FROM {$wpdb->postmeta} io_pm WHERE io_pm.post_id = {$wpdb->posts}.ID AND io_pm.meta_key = %s )
			        OR EXISTS ( SELECT 1 FROM {$wpdb->postmeta} io_pm2 WHERE io_pm2.post_id = {$wpdb->posts}.ID AND io_pm2.meta_key = %s AND io_pm2.meta_value = %s ) )",
			'_sites_check_status',
			'_sites_check_status',
			'unknown'
		);
	} else {
		$where .= $wpdb->prepare(
			" AND EXISTS ( SELECT 1 FROM {$wpdb->postmeta} io_pm WHERE io_pm.post_id = {$wpdb->posts}.ID AND io_pm.meta_key = %s AND io_pm.meta_value = %s )",
			'_sites_check_status',
			$status
		);
	}

	return $where;
}

endif; // io_site_check_enable
