<?php
/*
 * @Theme Name:WebStack
 * @Theme URI:https://www.iotheme.cn/
 * @Author: iowen
 * @Author URI: https://www.iowen.cn/
 * @Date: 2019-02-22 21:26:02
 * @LastEditors: iowen
 * @LastEditTime: 2024-07-30 21:03:08
 * @FilePath: /WebStack/templates/site-card.php
 * @Description: 
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }  ?>

            <?php
            $title = $link_url;
            $is_html = '';
            $tooltip = 'data-toggle="tooltip" data-placement="bottom"';
            if(get_post_meta($post->ID, '_wechat_qr', true)){
                $title="<img src='" . get_post_meta(get_the_ID(), '_wechat_qr', true) . "' width='128'>";
                $is_html = 'data-html="true"';
            } else {
                switch(io_get_option('po_prompt')) {
                    case 'null':  
                        $title = get_the_title();
                        $tooltip = '';
                        break;
                    case 'url': 
                        if($link_url=="")
                            $title = __('地址错误！','i_theme');
                        break;
                    case 'summary':
                        $title = get_post_meta($post->ID, '_sites_sescribe', true);
                        break;
                    case 'qr':
                        if($link_url=="")
                            $title = __('地址错误！','i_theme');
                        else{
                            $title = "<img src='//api.qrserver.com/v1/create-qr-code/?size=150x150&margin=10&data=" . $link_url . "' width='128'>";
                            $is_html = 'data-html="true"';
                        }
                        break;
                    default: 
                } 
            }
            $url = '';
            $blank = '_blank';
            if(io_get_option('details_page')){ 
                $url=get_permalink();
            }else{ 
                if($link_url==""){
                    $url = 'javascript:';
                    $blank = '';
                }else{
                    if(io_get_option('is_go'))
                        $url = home_url().'/go/?url='.rawurlencode($link_url);
                    else
                        $url = $link_url;
                }
            }
            $ico = io_theme_get_thumb();
            $default_ico_fallback = isset($default_ico) ? $default_ico : get_theme_file_uri('/images/favicon.png');
            //判断是不是文章 post
            if(get_post_type() == 'post'){
                $title = '';
                $url = get_permalink();
            }else{
                if($ico){
                    // 有手动上传的缩略图，直接用，onerror 降级到默认图标
                    $onerror_chain = "this.onerror=null;this.src='" . esc_js($default_ico_fallback) . "'";
                }else{
                    // 无缩略图，使用多源 favicon API 备用方案
                    $ico_urls = io_get_favicon_urls($link_url, $default_ico_fallback);
                    $ico = array_shift($ico_urls); // 第一个 URL 作为 src
                    // 构建 onerror 降级链：每个 URL 失败后自动尝试下一个
                    if (!empty($ico_urls)) {
                        $onerror_chain = build_onerror_chain($ico_urls);
                    } else {
                        $onerror_chain = "this.onerror=null;this.src='" . esc_js($default_ico_fallback) . "'";
                    }
                }
            }
            ?>
            <a href="<?php echo $url ?>" target="<?php echo $blank ?>" class="xe-widget xe-conversations box2 label-info" <?php echo $tooltip . ' ' . $is_html ?> title="<?php echo $title ?>">
                <div class="xe-comment-entry">
                    <div class="xe-user-img">
                        <?php if(io_get_option('lazyload')): ?>
                        <img class="img-circle lazy" src="<?php echo $default_ico_fallback; ?>" data-src="<?php echo $ico ?>" onerror="<?php echo $onerror_chain; ?>" width="40" height="40">
                        <?php else: ?>
                        <img class="img-circle lazy" src="<?php echo $ico ?>" onerror="<?php echo $onerror_chain; ?>" width="40" height="40">
                        <?php endif ?>
                    </div>
                    <div class="xe-comment">
                        <div class="xe-user-name overflowClip_1">
                            <strong><?php the_title() ?></strong>
                        </div>
                        <p class="overflowClip_2"><?php echo get_post_meta($post->ID, '_sites_sescribe', true) ?: preg_replace("/(\s|\&nbsp\;|　|\xc2\xa0)/","",get_the_excerpt($post->ID)); ?></p>
                    </div>
                </div>
            </a>
            
