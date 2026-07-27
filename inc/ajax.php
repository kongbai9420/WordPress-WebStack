<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

//图片上传
add_action('wp_ajax_nopriv_img_upload', 'io_img_upload');  
add_action('wp_ajax_img_upload', 'io_img_upload');
function io_img_upload(){
	check_ajax_referer( 'io_img_upload_nonce', 'nonce' );
	$allowed_mimes = array(
		'jpg|jpeg' => 'image/jpeg',
		'png'      => 'image/png',
	);
	$max_size = 1024 * 1024; // 1MB

	if ( empty( $_FILES['files'] ) || ! is_array( $_FILES['files'] ) ) {
		wp_send_json( array( 'status' => 3, 'msg' => __( '没有上传图像！', 'i_theme' ) ) );
	}

	$file = $_FILES['files'];

	if ( ! empty( $file['error'] ) ) {
		wp_send_json( array( 'status' => 4, 'msg' => __( '图片上传失败！', 'i_theme' ) ) );
	}

	if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
		wp_send_json( array( 'status' => 4, 'msg' => __( '非法上传请求！', 'i_theme' ) ) );
	}

	if ( ! empty( $file['size'] ) && $file['size'] > $max_size ) {
		wp_send_json( array( 'status' => 3, 'msg' => __( '图片大小不能超过1M。', 'i_theme' ) ) );
	}

	$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $allowed_mimes );
	if ( empty( $check['ext'] ) || empty( $check['type'] ) ) {
		wp_send_json( array( 'status' => 3, 'msg' => __( '图片类型只能是 jpeg、jpg、png。', 'i_theme' ) ) );
	}

	$wp_upload_dir = wp_upload_dir();
	if ( ! empty( $wp_upload_dir['error'] ) ) {
		wp_send_json( array( 'status' => 4, 'msg' => esc_html( $wp_upload_dir['error'] ) ) );
	}

	$basename = sanitize_file_name( wp_basename( $file['name'] ) );
	$dataname = date( 'YmdHis_' ) . wp_generate_password( 8, false, false ) . '.' . $check['ext'];
	$filename = trailingslashit( $wp_upload_dir['path'] ) . $dataname;

	if ( ! move_uploaded_file( $file['tmp_name'], $filename ) ) {
		wp_send_json( array( 'status' => 4, 'msg' => __( '图片上传失败！', 'i_theme' ) ) );
	}

	$attachment = array(
		'guid'           => trailingslashit( $wp_upload_dir['url'] ) . $dataname,
		'post_mime_type' => $check['type'],
		'post_title'     => preg_replace( '/\.[^.]+$/', '', $basename ),
		'post_content'   => '',
		'post_status'    => 'inherit',
	);

	$attach_id = wp_insert_attachment( $attachment, $filename );
	if ( $attach_id != 0 ) {
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
		wp_update_attachment_metadata( $attach_id, $attach_data );
		wp_send_json( array(
			'status' => 1,
			'msg'    => __( '图片添加成功', 'i_theme' ),
			'data'   => array(
				'id'    => $attach_id,
				'src'   => wp_get_attachment_url( $attach_id ),
				'title' => $basename,
			),
		) );
	}

	@unlink( $filename );
	wp_send_json( array( 'status' => 4, 'msg' => __( '图片上传失败！', 'i_theme' ) ) );
}

//删除图片：仅允许已登录且具备删除权限的用户执行，避免游客删除媒体库文件。
add_action('wp_ajax_img_remove', 'io_img_remove');
function io_img_remove(){
	check_ajax_referer( 'io_img_remove_nonce', 'nonce' );
	$attach_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

	if ( empty( $attach_id ) ) {
		wp_send_json( array( 'status' => 3, 'msg' => __( '没有上传图像！', 'i_theme' ) ) );
	}

	if ( ! current_user_can( 'delete_post', $attach_id ) ) {
		wp_send_json( array( 'status' => 403, 'msg' => __( '没有权限删除该图片。', 'i_theme' ) ) );
	}

	if ( false === wp_delete_attachment( $attach_id ) ) {
		wp_send_json( array( 'status' => 4, 'msg' => sprintf( __( '图片 %s 删除失败！', 'i_theme' ), $attach_id ) ) );
	}

	wp_send_json( array( 'status' => 1, 'msg' => __( '删除成功！', 'i_theme' ) ) );
}

//提交文章
add_action('wp_ajax_nopriv_contribute_post', 'io_contribute');  
add_action('wp_ajax_contribute_post', 'io_contribute');
function io_contribute(){  
	$delay = 40; 
	if( isset($_COOKIE["tougao"]) && ( time() - $_COOKIE["tougao"] ) < $delay ){
		error('{"status":2,"msg":"'.sprintf(__('您投稿也太勤快了吧，%s秒后再试！','i_theme'), ($delay - ( time() - $_COOKIE["tougao"] )) ).'"}');
	} 
	
	//表单变量初始化
	$sites_link = isset( $_POST['tougao_sites_link'] ) ? trim(htmlspecialchars($_POST['tougao_sites_link'], ENT_QUOTES)) : '';
	$sites_sescribe = isset( $_POST['tougao_sites_sescribe'] ) ? trim(htmlspecialchars($_POST['tougao_sites_sescribe'], ENT_QUOTES)) : '';
	$title = isset( $_POST['tougao_title'] ) ? trim(htmlspecialchars($_POST['tougao_title'], ENT_QUOTES)) : '';
	$category = isset( $_POST['tougao_cat'] ) ? $_POST['tougao_cat'] : '0';
	$sites_ico = isset( $_POST['tougao_sites_ico'] ) ? trim(htmlspecialchars($_POST['tougao_sites_ico'], ENT_QUOTES)) : '';
	$wechat_qr = isset( $_POST['tougao_wechat_qr'] ) ? trim(htmlspecialchars($_POST['tougao_wechat_qr'], ENT_QUOTES)) : '';
	$content = isset( $_POST['tougao_content'] ) ? trim(htmlspecialchars($_POST['tougao_content'], ENT_QUOTES)) : '';
	
	// 表单项数据验证
	if ( $category == "0" ){
		error('{"status":4,"msg":"'.__('请选择分类。','i_theme').'"}');
	}
	if ( !empty(get_term_children($category, 'favorites'))){
		error('{"status":4,"msg":"'.__('不能选用父级分类目录。','i_theme').'"}');
	}
	if ( empty($sites_sescribe) || mb_strlen($sites_sescribe) > 50 ) {
		error('{"status":4,"msg":"'.__('网站描叙必须填写，且长度不得超过50字。','i_theme').'"}');
	}
	if ( empty($sites_link) && empty($wechat_qr) ){
		error('{"status":3,"msg":"'.__('网站链接和公众号二维码至少填一项。','i_theme').'"}');
	}
	elseif ( !empty($sites_link) && !preg_match('/http(s)?:\/\/[\w.]+[\w\/]*[\w.]*\??[\w=&\+\%]*/is', $sites_link)) {
		error('{"status":4,"msg":"'.__('网站链接必须符合URL格式。','i_theme').'"}');
	}
	if ( empty($title) || mb_strlen($title) > 30 ) {
		error('{"status":4,"msg":"'.__('网站名称必须填写，且长度不得超过30字。','i_theme').'"}');
	}
	//if ( empty($content) || mb_strlen($content) > 10000 || mb_strlen($content) < 6) {
	//	error('{"status":4,"msg":"内容必须填写，且长度不得超过10000字，不得少于6字。"}');
	//}
	
	$tougao = array(
		'comment_status'   => 'closed',
		'ping_status'      => 'closed',
		//'post_author'      => 1,//用于投稿的用户ID
		'post_title'       => $title,
		'post_content'     => $content,
		'post_status'      => 'pending',
		'post_type'        => 'sites',
		//'tax_input'        => array( 'favorites' => array($category) ) //游客不可用
	);
	
	// 将文章插入数据库
	$status = wp_insert_post( $tougao );
	if ($status != 0){
		global $wpdb;
		add_post_meta($status, '_sites_sescribe', $sites_sescribe);
		add_post_meta($status, '_sites_link', $sites_link);
		add_post_meta($status, '_sites_order', '0');
		if( !empty($sites_ico))
			add_post_meta($status, '_thumbnail', $sites_ico); 
		if( !empty($wechat_qr))
			add_post_meta($status, '_wechat_qr', $wechat_qr); 
		wp_set_post_terms( $status, array($category), 'favorites'); //设置文章分类
		setcookie("tougao", time(), time()+$delay+10);
		error('{"status":1,"msg":"'.__('投稿成功！','i_theme').'"}');
	}else{
		error('{"status":4,"msg":"'.__('投稿失败！','i_theme').'"}');
	}
}
function error($ErrMsg) {
	echo $ErrMsg;
	exit;
} 
