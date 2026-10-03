<?php 
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header(); ?>



<style type="text/css">
.data-null {
    text-align: center;
    padding: var(--apple-section-gap-lg, 120px) 0;
}
.data-null h1 {
    font-family: var(--apple-font-display, -apple-system, BlinkMacSystemFont, sans-serif);
    font-size: 7rem;
    font-weight: var(--apple-weight-title-bold, 700);
    letter-spacing: -0.03em;
    padding: 0;
    margin: 0 0 16px 0;
    background: var(--apple-gradient-text-blue, linear-gradient(90deg, #2997FF, #5AC8FA));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.data-null p {
    font-size: var(--apple-font-size-subtitle, 24px);
    color: var(--apple-text-secondary, #6E6E73);
    margin-bottom: 32px;
}
.data-null .btn-home {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #FFFFFF;
    padding: 12px 36px;
    background: var(--apple-link-blue, #0066CC);
    font-size: var(--apple-font-size-body, 17px);
    font-weight: var(--apple-weight-body-medium, 500);
    border-radius: var(--apple-radius-pill, 980px);
    transition: all 0.25s cubic-bezier(0.25, 1, 0.5, 1);
    box-shadow: 0 2px 8px rgba(0, 102, 204, 0.25);
    text-decoration: none;
}
.data-null .btn-home:hover {
    background: var(--apple-link-blue-hover, #0077ED);
    box-shadow: 0 4px 16px rgba(0, 102, 204, 0.35);
    transform: translateY(-2px);
    color: #FFFFFF;
}
.screen-reader-text { display: none; }
</style>

		<div class="main-content">
			<div class="row">
				<div class="col-12 col-lg-12">
					<section class="data-null">
			            <h1 class="font-theme">404</h1>
			            <p><?php _e('抱歉，没有你要找的内容...','i_theme') ?></p>
                      	<div style="margin-top: 30px">
							<a class="btn-home" href="<?php bloginfo('url'); ?>"><?php _e('返回首页','i_theme') ?></a>
                        </div>
			        </section>
				</div>
			</div>
<?php get_footer(); ?>