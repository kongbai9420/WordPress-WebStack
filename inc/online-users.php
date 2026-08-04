<?php
/**
 * WebStack 访客统计模块
 *
 * 功能：
 *  - 实时在线人数（最近 N 分钟内有心跳的独立访客）
 *  - 今日访问量 / 今日独立访客数（按天累计，站点时区）
 *  - 总访问量（所有访问累计，汇总每日数据）
 *
 * 安全策略：
 *  - 不保存明文 IP，仅保存带密钥的哈希（用于防止接口被无限刷）
 *  - 所有 AJAX 请求校验 WordPress nonce
 *  - 心跳频率限制（同一访客最短间隔，默认 60 秒）
 *  - 页面访问 PV 短时间去重（可在主题设置中开关和调整时间，默认 30 秒）
 *  - 过期的在线记录通过 wp-cron 自动清理
 *  - 数据写入使用 $wpdb->prepare()，避免 SQL 注入
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! defined( 'NAV_STATS_DB_VERSION' ) ) {
	define( 'NAV_STATS_DB_VERSION', '1.0.0' );
}

/**
 * 创建统计专用数据表（仅在不存在时创建，不修改原有数据）
 */
function nav_stats_create_tables() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$charset_collate = $wpdb->get_charset_collate();

	// 在线访客表：只保留短期在线状态
	$online_table = $wpdb->prefix . 'nav_online_users';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $online_table ) ) !== $online_table ) {
		$online_sql = "CREATE TABLE {$online_table} (
			visitor_id varchar(64) NOT NULL,
			last_seen datetime NOT NULL,
			ip_hash char(64) NOT NULL DEFAULT '',
			PRIMARY KEY  (visitor_id),
			KEY last_seen (last_seen)
		) {$charset_collate};";
		dbDelta( $online_sql );
	}

	// 每日访问统计表：按天汇总
	$stats_table = $wpdb->prefix . 'nav_visit_stats';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $stats_table ) ) !== $stats_table ) {
		$stats_sql = "CREATE TABLE {$stats_table} (
			stat_date date NOT NULL,
			page_views bigint(20) NOT NULL DEFAULT 0,
			unique_visitors bigint(20) NOT NULL DEFAULT 0,
			PRIMARY KEY  (stat_date)
		) {$charset_collate};";
		dbDelta( $stats_sql );
	}

	$online_ready = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $online_table ) ) === $online_table );
	$stats_ready  = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $stats_table ) ) === $stats_table );
	if ( $online_ready && $stats_ready ) {
		update_option( 'nav_stats_db_version', NAV_STATS_DB_VERSION );
	}
}
function nav_stats_maybe_create_tables() {
	// 总开关关闭时不创建统计表，避免用户未启用功能却产生数据库对象。
	if ( ! nav_stats_is_enabled() ) {
		return;
	}
	if ( get_option( 'nav_stats_db_version' ) !== NAV_STATS_DB_VERSION ) {
		nav_stats_create_tables();
	}
}
add_action( 'init', 'nav_stats_maybe_create_tables', 5 );

/**
 * 获取用于防刷的 IP 哈希（不保存明文 IP）
 */
function nav_stats_get_ip_hash() {
	$ip     = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$secret = get_option( 'nav_stats_ip_secret' );
	if ( ! $secret ) {
		$secret = wp_generate_password( 48, true, true );
		update_option( 'nav_stats_ip_secret', $secret );
	}
	return hash( 'sha256', $ip . '|' . $secret );
}

/**
 * 获取当前日期（站点时区）
 */
function nav_stats_today() {
	return current_time( 'Y-m-d' );
}

/**
 * 页面访问计数去重窗口（秒）。默认 30 秒，防止刷新/脚本刷量快速抬高 PV。
 */
function nav_stats_page_view_window() {
	$seconds = (int) io_get_option( 'nav_stats_pv_throttle_seconds', 30 );
	$seconds = (int) apply_filters( 'nav_stats_page_view_window', $seconds );
	return max( 5, min( 300, $seconds ) );
}

/**
 * 是否启用页面访问量短时间去重。
 */
function nav_stats_page_view_throttle_enabled() {
	return nav_stats_is_truthy( io_get_option( 'nav_stats_pv_throttle_enable', true ) );
}

/**
 * 生成短期访问去重键。优先使用已有访客 Cookie，首次访问或无 Cookie 时退回到 IP+UA 哈希。
 */
function nav_stats_page_view_key( $cookie_name ) {
	if ( isset( $_COOKIE[ $cookie_name ] ) ) {
		$cookie = sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) );
		$parts  = explode( '|', $cookie );
		if ( ! empty( $parts[0] ) && preg_match( '/^[a-f0-9]{40}$/', $parts[0] ) ) {
			return 'visitor_' . $parts[0];
		}
	}

	$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
	return 'client_' . hash( 'sha256', nav_stats_get_ip_hash() . '|' . $user_agent );
}

/**
 * 读取/写入每日统计行（原子自增，避免并发覆盖）
 */
function nav_stats_update_daily( $column, $amount ) {
	global $wpdb;
	$allowed_columns = array( 'page_views', 'unique_visitors' );
	if ( ! in_array( $column, $allowed_columns, true ) ) {
		return;
	}

	$table = $wpdb->prefix . 'nav_visit_stats';
	$today = nav_stats_today();

	$wpdb->query(
		$wpdb->prepare(
			"INSERT INTO {$table} (stat_date, {$column}) VALUES (%s, %d)
			 ON DUPLICATE KEY UPDATE {$column} = {$column} + %d",
			$today,
			$amount,
			$amount
		)
	);
}

/**
 * 统一解析 CS Framework switcher / 默认值，避免字符串 "false" 被 (bool) 误判为 true。
 */
function nav_stats_is_truthy( $value ) {
	if ( is_bool( $value ) ) {
		return $value;
	}
	return in_array( strtolower( trim( (string) $value ) ), array( '1', 'true', 'on', 'yes' ), true );
}

/**
 * 统计页脚组件是否显示（受主题设置开关控制）
 */
function nav_stats_is_enabled() {
	return nav_stats_is_truthy( io_get_option( 'nav_stats_enable', false ) );
}

/**
 * 页脚显示位置是否满足（默认仅首页）
 */
function nav_stats_should_render() {
	if ( ! nav_stats_is_enabled() ) {
		return false;
	}
	$scope = io_get_option( 'nav_stats_scope', 'home' );
	if ( 'all' === $scope ) {
		return true;
	}
	return ( is_home() || is_front_page() );
}

/**
 * 在线判定窗口（分钟），主题设置可调，默认 5 分钟
 */
function nav_stats_online_window() {
	$minutes = (int) io_get_option( 'nav_stats_window', 5 );
	return max( 1, min( 60, $minutes ) );
}

/**
 * 在线人数缓存（transient 30 秒，避免每次心跳重复查询）
 */
function nav_stats_online_count() {
	$count = get_transient( 'nav_stats_online_count' );
	if ( false === $count ) {
		global $wpdb;
		$table  = $wpdb->prefix . 'nav_online_users';
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( nav_stats_online_window() * MINUTE_IN_SECONDS ) );

		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE last_seen >= %s",
				$cutoff
			)
		);
		set_transient( 'nav_stats_online_count', $count, 30 );
	}
	return $count;
}

/**
 * 今日访问量（PV）
 */
function nav_stats_today_views() {
	global $wpdb;
	$table = $wpdb->prefix . 'nav_visit_stats';
	return (int) $wpdb->get_var(
		$wpdb->prepare( "SELECT page_views FROM {$table} WHERE stat_date = %s", nav_stats_today() )
	);
}

/**
 * 今日独立访客数（UV）
 */
function nav_stats_today_visitors() {
	global $wpdb;
	$table = $wpdb->prefix . 'nav_visit_stats';
	return (int) $wpdb->get_var(
		$wpdb->prepare( "SELECT unique_visitors FROM {$table} WHERE stat_date = %s", nav_stats_today() )
	);
}

/**
 * 总访问量（所有页面访问 PV 累计）
 */
function nav_stats_total_views() {
	global $wpdb;
	$table = $wpdb->prefix . 'nav_visit_stats';
	return (int) $wpdb->get_var( "SELECT SUM(page_views) FROM {$table}" );
}

/**
 * 前端页脚组件输出（跟随主题明暗自动适配）
 */
function nav_stats_footer_widget() {
	if ( ! nav_stats_should_render() ) {
		return;
	}

	$show_online = nav_stats_is_truthy( io_get_option( 'nav_stats_show_online', false ) );
	$show_today  = nav_stats_is_truthy( io_get_option( 'nav_stats_show_today', false ) );
	$show_total  = nav_stats_is_truthy( io_get_option( 'nav_stats_show_total', false ) );

	if ( ! $show_online && ! $show_today && ! $show_total ) {
		return;
	}
	?>
	<span class="nav-live-widget" role="status" aria-live="polite" aria-label="<?php echo esc_attr__( '网站访客统计', 'i_theme' ); ?>">
		<?php if ( $show_online ) : ?>
		<span class="nav-live-item">
			<span class="nav-live-dot" aria-hidden="true"></span>
			<span class="nav-live-value" id="nav-live-online"><?php echo esc_html( number_format_i18n( nav_stats_online_count() ) ); ?></span>
			<span class="nav-live-label"><?php esc_html_e( '实时在线', 'i_theme' ); ?></span>
		</span>
		<?php endif; ?>
		<?php if ( $show_today ) : ?>
		<span class="nav-live-item">
			<span class="nav-live-icon" aria-hidden="true">今日</span>
			<span class="nav-live-value" id="nav-live-today"><?php echo esc_html( number_format_i18n( nav_stats_today_views() ) ); ?></span>
			<span class="nav-live-label"><?php esc_html_e( '今日访问', 'i_theme' ); ?></span>
		</span>
		<?php endif; ?>
		<?php if ( $show_total ) : ?>
		<span class="nav-live-item">
			<span class="nav-live-icon" aria-hidden="true">总计</span>
			<span class="nav-live-value" id="nav-live-total"><?php echo esc_html( number_format_i18n( nav_stats_total_views() ) ); ?></span>
			<span class="nav-live-label"><?php esc_html_e( '总访问量', 'i_theme' ); ?></span>
		</span>
		<?php endif; ?>
	</span>
	<?php
}

/**
 * 心跳：记录在线状态，返回在线人数、今日访问、总访问
 */
function nav_stats_heartbeat_ajax() {
	check_ajax_referer( 'nav_stats_nonce', 'nonce' );

	if ( ! nav_stats_is_enabled() ) {
		wp_send_json_error( array( 'msg' => 'disabled' ) );
	}

	$visitor_id = isset( $_POST['visitor_id'] ) ? sanitize_text_field( wp_unslash( $_POST['visitor_id'] ) ) : '';

	if ( ! preg_match( '/^[a-f0-9]{40}$/', $visitor_id ) ) {
		wp_send_json_error( array( 'msg' => 'bad visitor id' ) );
	}

	global $wpdb;
	$online_table = $wpdb->prefix . 'nav_online_users';
	$ip_hash      = nav_stats_get_ip_hash();
	$now          = gmdate( 'Y-m-d H:i:s' );
	$min_interval = max( 30, (int) apply_filters( 'nav_stats_min_heartbeat', 60 ) );

	// 频率限制：同一访客至少间隔 min_interval 秒更新一次
	$last = $wpdb->get_var(
		$wpdb->prepare( "SELECT last_seen FROM {$online_table} WHERE visitor_id = %s", $visitor_id )
	);
	if ( $last ) {
		$last_ts = strtotime( $last . ' UTC' );
		if ( $last_ts && ( time() - $last_ts ) < $min_interval ) {
			// 未到更新时间，不写入，直接返回缓存数据，避免刷库
			wp_send_json_success( array(
				'online' => nav_stats_online_count(),
				'today'  => nav_stats_today_views(),
				'total'  => nav_stats_total_views(),
			) );
		}
	}

	// IP 级别防刷：同一 IP 哈希在在线窗口内最多允许创建有限数量 visitor_id。
	if ( ! $last ) {
		$cutoff          = gmdate( 'Y-m-d H:i:s', time() - ( nav_stats_online_window() * MINUTE_IN_SECONDS ) );
		$max_per_ip      = max( 1, (int) apply_filters( 'nav_stats_max_online_per_ip', 20 ) );
		$active_same_ip  = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$online_table} WHERE ip_hash = %s AND last_seen >= %s",
				$ip_hash,
				$cutoff
			)
		);
		if ( $active_same_ip >= $max_per_ip ) {
			wp_send_json_success( array(
				'online' => nav_stats_online_count(),
				'today'  => nav_stats_today_views(),
				'total'  => nav_stats_total_views(),
			) );
		}
	}

	$wpdb->query(
		$wpdb->prepare(
			"INSERT INTO {$online_table} (visitor_id, last_seen, ip_hash) VALUES (%s, %s, %s)
			 ON DUPLICATE KEY UPDATE last_seen = VALUES(last_seen), ip_hash = VALUES(ip_hash)",
			$visitor_id,
			$now,
			$ip_hash
		)
	);

	// 在线人数缓存刷新
	delete_transient( 'nav_stats_online_count' );

	wp_send_json_success( array(
		'online' => nav_stats_online_count(),
		'today'  => nav_stats_today_views(),
		'total'  => nav_stats_total_views(),
	) );
}
add_action( 'wp_ajax_nav_stats_heartbeat', 'nav_stats_heartbeat_ajax' );
add_action( 'wp_ajax_nopriv_nav_stats_heartbeat', 'nav_stats_heartbeat_ajax' );

/**
 * 页面访问统计（短时间去重后 PV+1，同一天同访客 UV+1）
 * 挂在 template_redirect 上，保证条件标签可用且 setcookie 仍在输出前生效
 */
function nav_stats_count_page_view() {
	if ( is_admin() || is_preview() || is_feed() || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
		return;
	}
	if ( ! nav_stats_is_enabled() ) {
		return;
	}
	// 跳过 AJAX / REST，避免心跳请求被计入页面访问
	if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
		return;
	}
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return;
	}
	if ( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( wp_unslash( $_SERVER['HTTP_X_REQUESTED_WITH'] ) ) === 'xmlhttprequest' ) {
		return;
	}

	// 简单排除搜索引擎爬虫
	if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
		$ua   = strtolower( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
		$bots = array( 'bot', 'spider', 'crawler', 'slurp', 'bingpreview' );
		foreach ( $bots as $keyword ) {
			if ( false !== strpos( $ua, $keyword ) ) {
				return;
			}
		}
	}

	$cookie_name = 'nav_stats_vid';
	$today       = nav_stats_today();

	if ( nav_stats_page_view_throttle_enabled() ) {
		$pv_key = 'nav_stats_pv_' . md5( nav_stats_page_view_key( $cookie_name ) );

		// 同一访客 / 同一 IP+UA 短时间内重复刷新，只记录一次 PV。
		if ( false === get_transient( $pv_key ) ) {
			nav_stats_update_daily( 'page_views', 1 );
			set_transient( $pv_key, 1, nav_stats_page_view_window() );
		}
	} else {
		// 关闭限流时，每次正常页面加载都计入一次 PV。
		nav_stats_update_daily( 'page_views', 1 );
	}

	// 同一天同一访客只记一次 UV

	if ( isset( $_COOKIE[ $cookie_name ] ) ) {
		$cookie = sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) );
		$parts  = explode( '|', $cookie );
		if ( isset( $parts[1] ) && $parts[1] === $today ) {
			return; // 今天已记录过
		}
	}

	$visitor_id = nav_stats_generate_visitor_id();
	$days       = 30;
	setcookie( $cookie_name, $visitor_id . '|' . $today, time() + ( $days * DAY_IN_SECONDS ), COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
	nav_stats_update_daily( 'unique_visitors', 1 );
}
add_action( 'template_redirect', 'nav_stats_count_page_view' );

/**
 * 生成服务端访客 ID（基于 IP 哈希 + 随机串）
 */
function nav_stats_generate_visitor_id() {
	return hash( 'sha1', nav_stats_get_ip_hash() . '|' . wp_generate_password( 12, true, true ) );
}

/**
 * 定期清理过期在线记录（wp-cron，每小时）
 */
function nav_stats_schedule_cleanup() {
	if ( ! nav_stats_is_enabled() ) {
		wp_clear_scheduled_hook( 'nav_stats_cleanup_event' );
		return;
	}
	if ( ! wp_next_scheduled( 'nav_stats_cleanup_event' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'nav_stats_cleanup_event' );
	}
}
add_action( 'init', 'nav_stats_schedule_cleanup' );

function nav_stats_cleanup() {
	global $wpdb;
	$table  = $wpdb->prefix . 'nav_online_users';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
		return;
	}
	$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( nav_stats_online_window() + 5 ) * MINUTE_IN_SECONDS );
	$wpdb->query(
		$wpdb->prepare( "DELETE FROM {$table} WHERE last_seen < %s", $cutoff )
	);
	delete_transient( 'nav_stats_online_count' );
}
add_action( 'nav_stats_cleanup_event', 'nav_stats_cleanup' );

