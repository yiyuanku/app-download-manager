<?php
/*============================================================
 =  🚀 项目名称：壹元库应用下载插件
 =  📦 模块名称：详情页模板模块
 =  📄 文件：single-app.php
 =  👤 作者：壹元库 <815116566@qq.com>
 =  🌐 官网：https://yiyuanku.cn
 =  🔢 版本：1.0.0
 =  📅 日期：2026-04-15
 =  📝 说明：应用详情页模板，包含应用信息、截图展示、下载按钮、相关应用等功能
 =  © 版权：2026 壹元库. All Rights Reserved.
 ============================================================*/

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<div class="yyk-single-app-container">
    <div class="yyk-app-header">
        <!-- 应用图标 -->
        <div class="yyk-app-icon-wrapper">
            <div class="yyk-app-icon">
                <?php 
                $app_icon_url = get_post_meta(get_the_ID(), '_yyk_app_icon_url', true);
                $app_icon_id = get_post_meta(get_the_ID(), '_yyk_app_icon_id', true);
                $default_icon_url = plugins_url('assets/images/default-icon.png', dirname(__FILE__, 2) . '/app-download-manager.php');
                
                if (!empty($app_icon_url)) {
                    echo '<img src="' . esc_url($app_icon_url) . '" alt="' . esc_attr(get_the_title()) . '" style="width:100%;height:100%;object-fit:contain;padding:15px;box-sizing:border-box;">';
                } elseif ($app_icon_id) {
                    echo wp_get_attachment_image($app_icon_id, 'full', false, ['style' => 'width:100%;height:100%;object-fit:contain;padding:15px;box-sizing:border-box;']);
                } else {
                    echo '<img src="' . esc_url($default_icon_url) . '" alt="' . esc_attr(get_the_title()) . '" style="width:100%;height:100%;object-fit:contain;padding:15px;box-sizing:border-box;">';
                }
                ?>
            </div>
            <?php
            $short_intro = get_post_meta(get_the_ID(), '_yyk_st_short_intro', true);
            if (!empty($short_intro)):
            ?>
            <p class="yyk-app-short-intro"><?php echo esc_html($short_intro); ?></p>
            <?php endif; ?>
        </div>
        
        <!-- 应用信息 -->
        <div class="yyk-app-info">
            <?php
            $discount = get_post_meta(get_the_ID(), '_yyk_st_discount', true);
            $welfare_tags = get_post_meta(get_the_ID(), '_yyk_st_welfare_tags', true);
            
            // 处理折扣格式
            if (is_numeric($discount)) {
                $discount = $discount . '折';
            }
            ?>
            <div class="yyk-app-title-wrapper">
                <h1 class="yyk-app-title"><?php the_title(); ?></h1>
                <?php if (!empty($discount)): ?>
                    <span class="yyk-tag yyk-tag-discount">折扣: <?php echo esc_html($discount); ?></span>
                <?php endif; ?>
            </div>
            
            <?php 
            if (!empty($welfare_tags)):
                $welfare_tags_array = maybe_unserialize($welfare_tags);
                if (is_array($welfare_tags_array)):
            ?>
                <div class="yyk-app-tags">
                    <?php foreach ($welfare_tags_array as $tag): ?>
                        <span class="yyk-tag yyk-tag-fuli"><?php echo esc_html($tag); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php 
                endif;
            endif;
            ?>
            
            <div class="yyk-app-meta">
                <?php
                $version = get_post_meta(get_the_ID(), '_yyk_app_version', true);
                $size = get_post_meta(get_the_ID(), '_yyk_app_size', true);
                $developer = get_post_meta(get_the_ID(), '_yyk_app_developer', true);
                $compatibility = get_post_meta(get_the_ID(), '_yyk_app_compatibility', true);
                $update_date = get_post_meta(get_the_ID(), '_yyk_app_update_date', true);
                $download_count = get_post_meta(get_the_ID(), '_yyk_app_download_count', true);
                $platform = get_post_meta(get_the_ID(), '_yyk_app_platform', true);
                ?>
                
                <?php if (!empty($developer)): ?>
                    <div class="yyk-meta-item">
                        <span class="yyk-meta-label"><?php _e('开发商:', 'yyk-app-download'); ?></span>
                        <span class="yyk-meta-value"><?php echo esc_html($developer); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($version)): ?>
                    <div class="yyk-meta-item">
                        <span class="yyk-meta-label"><?php _e('版本:', 'yyk-app-download'); ?></span>
                        <span class="yyk-meta-value"><?php echo esc_html($version); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($size)): ?>
                    <div class="yyk-meta-item">
                        <span class="yyk-meta-label"><?php _e('大小:', 'yyk-app-download'); ?></span>
                        <span class="yyk-meta-value"><?php echo esc_html($size); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($compatibility)): ?>
                    <div class="yyk-meta-item">
                        <span class="yyk-meta-label"><?php _e('兼容性:', 'yyk-app-download'); ?></span>
                        <span class="yyk-meta-value"><?php echo esc_html($compatibility); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($update_date)): ?>
                    <div class="yyk-meta-item">
                        <span class="yyk-meta-label"><?php _e('更新日期:', 'yyk-app-download'); ?></span>
                        <span class="yyk-meta-value"><?php echo esc_html($update_date); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($download_count)): ?>
                    <div class="yyk-meta-item">
                        <span class="yyk-meta-label"><?php _e('下载次数:', 'yyk-app-download'); ?></span>
                        <span class="yyk-meta-value"><?php echo esc_html($download_count); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($platform)): ?>
                    <div class="yyk-meta-item">
                        <span class="yyk-meta-label"><?php _e('平台:', 'yyk-app-download'); ?></span>
                        <span class="yyk-meta-value yyk-platform-icon <?php echo esc_attr($platform); ?>">
                            <?php 
                            switch($platform) {
                                case 'android': _e('安卓', 'yyk-app-download'); break;
                                case 'ios': _e('苹果', 'yyk-app-download'); break;
                                case 'pc': _e('PC', 'yyk-app-download'); break;
                                default: _e('全平台', 'yyk-app-download');
                            }
                            ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="yyk-app-actions">
                <?php
                $download_url = get_post_meta(get_the_ID(), '_yyk_app_download_url', true);
                $android_url = get_post_meta(get_the_ID(), '_yyk_app_android_url', true);
                $ios_url = get_post_meta(get_the_ID(), '_yyk_app_ios_url', true);
                $qr_code = get_post_meta(get_the_ID(), '_yyk_app_qr_code', true);
                ?>
                
                <?php if (!empty($download_url)): ?>
                    <a href="<?php echo esc_url($download_url); ?>" 
                       class="yyk-download-btn yyk-primary" 
                       target="_blank" 
                       rel="nofollow"
                       data-app-id="<?php the_ID(); ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:8px;">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        <?php _e('立即下载', 'yyk-app-download'); ?>
                    </a>
                <?php endif; ?>
                
                <?php if (!empty($android_url)): ?>
                    <a href="<?php echo esc_url($android_url); ?>" 
                       class="yyk-download-btn yyk-android" 
                       target="_blank" 
                       rel="nofollow">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="margin-right:8px;">
    <path d="M17.6 9.48l1.84-3.18c.16-.31.04-.69-.26-.85-.3-.15-.68-.04-.83.26l-1.88 3.24c-2.86-1.21-6.08-1.21-8.94 0L5.65 5.71c-.16-.3-.54-.41-.83-.26-.3.16-.41.54-.26.85L6.4 9.48C3.3 11.25 1.28 14.44 1 18h22c-.28-3.56-2.3-6.75-5.4-8.52zM7 15.25c-.69 0-1.25-.56-1.25-1.25S6.31 13 7 13s1.25.56 1.25 1.25S7.69 15.25 7 15.25zm10 0c-.69 0-1.25-.56-1.25-1.25S16.31 13 17 13s1.25.56 1.25 1.25S17.69 15.25 17 15.25z"/>
</svg>
                        <?php _e('Android版', 'yyk-app-download'); ?>
                    </a>
                <?php endif; ?>
                
                <?php if (!empty($ios_url)): ?>
                    <a href="<?php echo esc_url($ios_url); ?>" 
                       class="yyk-download-btn yyk-ios" 
                       target="_blank" 
                       rel="nofollow">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="margin-right:8px;">
                            <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.31-2.33 1.05-3.11z"/>
                        </svg>
                        <?php _e('iOS版', 'yyk-app-download'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="yyk-qr-code">
            <?php
            $default_qr_url = content_url('/plugins/app-download-manager/assets/images/st2wm.png');
            ?>
            <img src="<?php echo esc_url($default_qr_url); ?>" 
                 alt="<?php _e('下载二维码', 'yyk-app-download'); ?>"
                 width="150" height="150">
            <p><?php _e('扫码下载', 'yyk-app-download'); ?></p>
        </div>
    </div>
    
    <div class="yyk-app-content">
        <?php
        $excerpt = get_the_excerpt();
        if (!empty($excerpt)):
        ?>
        <div class="yyk-app-excerpt">
            <div class="yyk-excerpt-icon">✨</div>
            <div class="yyk-excerpt-content">
                <h3><?php _e('福利简介', 'yyk-app-download'); ?></h3>
                <p><?php echo wp_kses_post($excerpt); ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <?php
        $has_description = true;
        $has_gifts = !empty(get_post_meta(get_the_ID(), '_yyk_st_gifts', true));
        $fanli = get_post_meta(get_the_ID(), '_yyk_st_fanli', true);
        $vip_intro = get_post_meta(get_the_ID(), '_yyk_st_vip_intro', true);
        $has_benefits = !empty($fanli) || !empty($vip_intro);
        $video = get_post_meta(get_the_ID(), '_yyk_st_video', true);
        $game_bbs = get_post_meta(get_the_ID(), '_yyk_st_game_bbs', true);
        if (empty($video) && !empty($game_bbs)) {
            $video = $game_bbs;
        }
        $has_video = !empty($video);
        $gamenotice = get_post_meta(get_the_ID(), '_yyk_st_gamenotice', true);
        $has_gamenotice = !empty($gamenotice);
        ?>
        
        <div class="yyk-tabs">
            <div class="yyk-tabs-nav">
                <button class="yyk-tab-btn active" data-tab="description">
                    <?php _e('应用介绍', 'yyk-app-download'); ?>
                </button>
                <?php if ($has_video): ?>
                <button class="yyk-tab-btn" data-tab="video">
                    <?php _e('游戏视频', 'yyk-app-download'); ?>
                </button>
                <?php endif; ?>
                <?php if ($has_gamenotice): ?>
                <button class="yyk-tab-btn" data-tab="notice">
                    <?php _e('游戏公告', 'yyk-app-download'); ?>
                </button>
                <?php endif; ?>
                <?php if ($has_benefits): ?>
                <button class="yyk-tab-btn" data-tab="benefits">
                    <?php _e('福利福利', 'yyk-app-download'); ?>
                </button>
                <?php endif; ?>
                <?php if ($has_gifts): ?>
                <button class="yyk-tab-btn" data-tab="gifts">
                    <?php _e('游戏礼包', 'yyk-app-download'); ?>
                </button>
                <?php endif; ?>
            </div>
            
            <div class="yyk-tabs-content">
                <div class="yyk-tab-panel active" id="tab-description">
                    <div class="yyk-app-description">
                        <?php the_content(); ?>
                    </div>
                </div>
                
                <?php if ($has_video): ?>
                <div class="yyk-tab-panel" id="tab-video">
                    <div class="yyk-app-video">
                        <video src="<?php echo esc_url($video); ?>" controls style="width:100%;border-radius:8px;">
                            您的浏览器不支持视频播放。
                        </video>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($has_gamenotice): ?>
                <div class="yyk-tab-panel" id="tab-notice">
                    <div class="yyk-app-notice">
                        <div class="yyk-notice-content">
                            <?php echo wp_kses_post($gamenotice); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($has_benefits): ?>
                <div class="yyk-tab-panel" id="tab-benefits">
                    <div class="yyk-app-benefits">
                        <?php if (!empty($fanli)): ?>
                        <div class="yyk-benefit-item yyk-benefit-fanli">
                            <h3><?php _e('返利介绍', 'yyk-app-download'); ?></h3>
                            <div class="yyk-benefit-content">
                                <?php echo wp_kses_post($fanli); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="yyk-benefit-item yyk-benefit-vip-table">
                            <h3><?php _e('VIP等级表', 'yyk-app-download'); ?></h3>
                            <div class="yyk-vip-table-wrapper">
                                <table class="yyk-vip-table">
                                    <thead>
                                        <tr>
                                            <th>VIP等级</th>
                                            <th>充值金额</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td>SVIP1</td><td>0.1</td></tr>
                                        <tr><td>SVIP2</td><td>1</td></tr>
                                        <tr><td>SVIP3</td><td>10</td></tr>
                                        <tr><td>SVIP4</td><td>20</td></tr>
                                        <tr><td>SVIP5</td><td>40</td></tr>
                                        <tr><td>SVIP6</td><td>70</td></tr>
                                        <tr><td>SVIP7</td><td>130</td></tr>
                                        <tr><td>SVIP8</td><td>230</td></tr>
                                        <tr><td>SVIP9</td><td>370</td></tr>
                                        <tr><td>SVIP10</td><td>550</td></tr>
                                        <tr><td>SVIP11</td><td>780</td></tr>
                                        <tr><td>SVIP12</td><td>1060</td></tr>
                                        <tr><td>SVIP13</td><td>1390</td></tr>
                                        <tr><td>SVIP14</td><td>1780</td></tr>
                                        <tr><td>SVIP15</td><td>2230</td></tr>
                                        <tr><td>SVIP16</td><td>3130</td></tr>
                                        <tr><td>SVIP17</td><td>4930</td></tr>
                                        <tr><td>SVIP18</td><td>8530</td></tr>
                                        <tr><td>SVIP19</td><td>15730</td></tr>
                                        <tr><td>SVIP20</td><td>30130</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($has_gifts): ?>
                <div class="yyk-tab-panel" id="tab-gifts">
                    <?php
                    $gifts = get_post_meta(get_the_ID(), '_yyk_st_gifts', true);
                    $gifts_array = json_decode($gifts, true);
                    if (!is_array($gifts_array)) {
                        $gifts_array = [];
                    }
                    $gifts_array = array_filter($gifts_array);
                    ?>
                    <div class="yyk-gifts-list">
                        <?php foreach ($gifts_array as $gift): ?>
                            <div class="yyk-gift-item">
                                <div class="yyk-gift-icon">🎁</div>
                                <div class="yyk-gift-info">
                                    <h4 class="yyk-gift-name"><?php echo esc_html($gift['name'] ?? '礼包'); ?></h4>
                                    <?php if (!empty($gift['content'])): ?>
                                        <p class="yyk-gift-content" style="margin:8px 0;font-size:13px;color:#666;"><?php echo esc_html($gift['content']); ?></p>
                                    <?php endif; ?>
                                    <div class="yyk-gift-meta">
                                        <span class="yyk-gift-tag">剩余: <?php echo esc_html($gift['remain'] ?? $gift['part_num'] ?? '0'); ?></span>
                                        <span class="yyk-gift-time">
                                            <?php if (!empty($gift['start_time']) && !empty($gift['end_time'])): ?>
                                                <?php echo esc_html($gift['start_time']); ?> - <?php echo esc_html($gift['end_time']); ?>
                                            <?php else: ?>
                                                长期有效
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>
                                <?php if (!empty($gift['code'] ?? $gift['card'])): ?>
                                    <button class="yyk-gift-btn" 
                                            data-gift-code="<?php echo esc_attr($gift['code'] ?? $gift['card']); ?>"
                                            onclick="copyGiftCode(this)">
                                        领取礼包
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php
        $screenshots = get_post_meta(get_the_ID(), '_yyk_st_photos', true);
        if (!empty($screenshots)):
            $screenshot_array = json_decode($screenshots, true);
            if (!is_array($screenshot_array)) {
                $screenshot_array = [];
            }
            $screenshot_array = array_filter($screenshot_array);
        ?>
            <div class="yyk-app-screenshots">
                <h2><?php _e('游戏截图', 'yyk-app-download'); ?></h2>
                <div class="yyk-screenshot-wrapper">
                    <button class="yyk-screenshot-btn yyk-screenshot-prev" onclick="scrollScreenshots(-1)">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </button>
                    <div class="yyk-screenshot-list" id="yyk-screenshot-list">
                        <?php foreach ($screenshot_array as $screenshot): ?>
                            <div class="yyk-screenshot-item">
                                <img src="<?php echo esc_url(trim($screenshot)); ?>" 
                                     alt="<?php _e('游戏截图', 'yyk-app-download'); ?>"
                                     loading="lazy">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="yyk-screenshot-btn yyk-screenshot-next" onclick="scrollScreenshots(1)">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </button>
                </div>
            </div>
        <?php endif; ?>
        
        <?php
        $categories = get_the_terms(get_the_ID(), 'yyk_app_category');
        if ($categories && !is_wp_error($categories)):
        ?>
            <div class="yyk-app-categories">
                <h3><?php _e('所属分类:', 'yyk-app-download'); ?></h3>
                <div class="yyk-category-list">
                    <?php foreach ($categories as $category): ?>
                        <a href="<?php echo get_term_link($category); ?>" class="yyk-category-tag">
                            <?php echo esc_html($category->name); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php
    // 相关应用 - 使用分类筛选
    $related_args = [
        'post_type' => 'yyk_app_download',
        'posts_per_page' => 4,
        'post__not_in' => [get_the_ID()],
        'orderby' => 'rand',
    ];
    
    // 如果有分类，优先使用同分类的应用
    if ($categories && !is_wp_error($categories)) {
        $related_args['tax_query'] = [
            [
                'taxonomy' => 'yyk_app_category',
                'field' => 'term_id',
                'terms' => array_map(function($cat) { return $cat->term_id; }, $categories),
            ]
        ];
    }
    
    $related_query = new WP_Query($related_args);
    
    if ($related_query->have_posts()):
    ?>
        <div class="yyk-related-apps">
            <h2><?php _e('相关应用', 'yyk-app-download'); ?></h2>
            <div class="yyk-related-list">
                <?php while ($related_query->have_posts()): $related_query->the_post(); ?>
                    <?php
                    $app_icon_url = get_post_meta(get_the_ID(), '_yyk_app_icon_url', true);
                    $app_icon_id = get_post_meta(get_the_ID(), '_yyk_app_icon_id', true);
                    $default_icon_url = plugins_url('assets/images/default-icon.png', dirname(__FILE__, 2) . '/app-download-manager.php');
                    
                    $final_icon_url = $default_icon_url;
                    if (!empty($app_icon_url)) {
                        $final_icon_url = $app_icon_url;
                    } elseif ($app_icon_id) {
                        $final_icon_url = wp_get_attachment_url($app_icon_id);
                    }
                    ?>
                    <div class="yyk-related-item">
                        <div class="yyk-related-icon">
                            <a href="<?php the_permalink(); ?>">
                                <img src="<?php echo esc_url($final_icon_url); ?>" 
                                     alt="<?php the_title_attribute(); ?>">
                            </a>
                        </div>
                        
                        <h4 class="yyk-related-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h4>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php
    endif;
    wp_reset_postdata();
    ?>
</div>

<?php get_footer(); ?>