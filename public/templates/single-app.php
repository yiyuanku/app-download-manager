<?php
/**
 * 应用详情页模板
 */
if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<div class="yyk-single-app-container">
    <div class="yyk-app-header">
        <!-- 应用图标 -->
        <div class="yyk-app-icon">
            <?php 
            // 获取应用图标
            $app_icon_id = get_post_meta(get_the_ID(), '_yyk_app_icon_id', true);
            if ($app_icon_id) {
                echo wp_get_attachment_image($app_icon_id, 'full', false, ['style' => 'width:100%;height:100%;object-fit:contain;padding:15px;box-sizing:border-box;']);
            } else {
                // 使用默认图标
                $default_icon_url = plugins_url('assets/images/default-icon.png', dirname(__FILE__, 2) . '/app-download-manager.php');
                echo '<img src="' . esc_url($default_icon_url) . '" alt="' . esc_attr(get_the_title()) . '" style="width:100%;height:100%;object-fit:contain;padding:15px;box-sizing:border-box;">';
            }
            ?>
        </div>
        
        <!-- 应用信息 -->
        <div class="yyk-app-info">
            <h1 class="yyk-app-title"><?php the_title(); ?></h1>
            
            <div class="yyk-app-meta">
                <?php
                $version = get_post_meta(get_the_ID(), '_yyk_app_version', true);
                $size = get_post_meta(get_the_ID(), '_yyk_app_size', true);
                $developer = get_post_meta(get_the_ID(), '_yyk_app_developer', true);
                $compatibility = get_post_meta(get_the_ID(), '_yyk_app_compatibility', true);
                $update_date = get_post_meta(get_the_ID(), '_yyk_app_update_date', true);
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
        
        <?php if (!empty($qr_code)): ?>
            <div class="yyk-qr-code">
                <img src="<?php echo esc_url($qr_code); ?>" 
                     alt="<?php _e('下载二维码', 'yyk-app-download'); ?>"
                     width="150" height="150">
                <p><?php _e('扫码下载', 'yyk-app-download'); ?></p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="yyk-app-content">
        <h2><?php _e('应用介绍', 'yyk-app-download'); ?></h2>
        <div class="yyk-app-description">
            <?php the_content(); ?>
        </div>
        
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
                    $app_icon_id = get_post_meta(get_the_ID(), '_yyk_app_icon_id', true);
                    $app_icon_url = $app_icon_id ? wp_get_attachment_url($app_icon_id) : '';
                    ?>
                    <div class="yyk-related-item">
                        <div class="yyk-related-icon">
                            <a href="<?php the_permalink(); ?>">
                                <?php if ($app_icon_url): ?>
                                    <img src="<?php echo esc_url($app_icon_url); ?>" 
                                         alt="<?php the_title_attribute(); ?>">
                                <?php else: ?>
                                    <?php 
                                    $default_icon_url = plugins_url('assets/images/default-icon.png', dirname(__FILE__, 2) . '/app-download-manager.php');
                                    ?>
                                    <img src="<?php echo esc_url($default_icon_url); ?>" 
                                         alt="<?php the_title_attribute(); ?>">
                                <?php endif; ?>
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

<style>
/* 详情页专用样式，内联避免被覆盖 */
.yyk-single-app-container {
    max-width: 1200px;
    margin: 30px auto;
    padding: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

/* 应用头部 */
.yyk-app-header {
    display: grid;
    grid-template-columns: 200px 1fr auto;
    gap: 30px;
    align-items: start;
    padding: 30px;
    border-bottom: 2px solid #f0f0f0;
    margin-bottom: 30px;
}

/* 应用图标 */
.yyk-app-icon {
    width: 200px;
    height: 200px;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
    background: white;
}

.yyk-app-icon img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 15px;
    box-sizing: border-box;
}

/* 应用信息 */
.yyk-app-info {
    flex: 1;
    min-width: 0;
}

.yyk-app-title {
    margin: 0 0 20px 0;
    font-size: 32px;
    font-weight: 700;
    color: #333;
    line-height: 1.2;
}

/* 应用元数据 */
.yyk-app-meta {
    margin-bottom: 25px;
}

.yyk-meta-item {
    display: flex;
    margin-bottom: 10px;
    align-items: center;
}

.yyk-meta-label {
    font-weight: 600;
    color: #666;
    min-width: 100px;
    margin-right: 15px;
}

.yyk-meta-value {
    color: #333;
    font-size: 16px;
}

/* 应用操作按钮 */
.yyk-app-actions {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    margin-top: 25px;
}

.yyk-download-btn {
    padding: 14px 28px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 16px;
    font-weight: 600;
    text-align: center;
    min-width: 140px;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 2px solid transparent;
}

.yyk-download-btn.yyk-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.yyk-download-btn.yyk-android {
    background: #3ddc84;
    color: #333;
}

.yyk-download-btn.yyk-ios {
    background: #000;
    color: white;
}

/* 二维码 */
.yyk-qr-code {
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 10px;
    border: 1px solid #e9ecef;
    width: 180px;
}

.yyk-qr-code img {
    width: 150px;
    height: 150px;
    display: block;
    margin: 0 auto 10px;
}

.yyk-qr-code p {
    margin: 0;
    font-size: 14px;
    color: #666;
    font-weight: 500;
}

/* 应用内容区域 */
.yyk-app-content {
    padding: 0 30px 30px;
}

.yyk-app-content h2 {
    margin: 0 0 20px 0;
    font-size: 28px;
    color: #333;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.yyk-app-description {
    font-size: 16px;
    line-height: 1.8;
    color: #444;
    margin-bottom: 40px;
}

/* 应用分类 */
.yyk-app-categories {
    margin: 40px 0;
    padding: 25px;
    background: #f8f9fa;
    border-radius: 10px;
}

.yyk-category-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.yyk-category-tag {
    background: white;
    color: #0073aa;
    padding: 8px 16px;
    border-radius: 20px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    border: 1px solid #0073aa;
    transition: all 0.3s ease;
}

/* 相关应用 */
.yyk-related-apps {
    margin-top: 50px;
    padding-top: 30px;
    border-top: 2px solid #f0f0f0;
}

.yyk-related-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 20px;
}

.yyk-related-item {
    background: white;
    border-radius: 10px;
    padding: 15px;
    text-align: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.yyk-related-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 15px;
    border-radius: 16px;
    overflow: hidden;
    background: white;
}

.yyk-related-icon img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 10px;
    box-sizing: border-box;
}

.yyk-related-title {
    margin: 0;
    font-size: 14px;
    line-height: 1.4;
}

/* 响应式设计 */
@media (max-width: 992px) {
    .yyk-app-header {
        grid-template-columns: 150px 1fr;
    }
    
    .yyk-app-icon {
        width: 150px;
        height: 150px;
    }
}

@media (max-width: 768px) {
    .yyk-single-app-container {
        margin: 15px auto;
        padding: 15px;
    }
    
    .yyk-app-header {
        grid-template-columns: 1fr;
        gap: 20px;
        text-align: center;
    }
    
    .yyk-app-icon {
        width: 120px;
        height: 120px;
        margin: 0 auto;
    }
    
    .yyk-app-title {
        font-size: 24px;
        text-align: center;
        margin-bottom: 15px;
    }
    
    .yyk-app-meta {
        text-align: center;
    }
    
    .yyk-meta-item {
        justify-content: center;
        flex-direction: column;
    }
    
    .yyk-meta-label {
        margin-right: 0;
        margin-bottom: 5px;
    }
    
    .yyk-app-actions {
        justify-content: center;
    }
    
    .yyk-download-btn {
        min-width: 120px;
        padding: 12px 20px;
        font-size: 14px;
    }
    
    .yyk-qr-code {
        grid-column: 1 / -1;
        width: auto;
        margin-top: 20px;
    }
    
    .yyk-app-content {
        padding: 0 15px 20px;
    }
}
</style>

<?php get_footer(); ?>