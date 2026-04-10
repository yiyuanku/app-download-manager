<?php
/**
 * 独立卡片样式模板
 */
if (!defined('ABSPATH')) {
    exit;
}

// 获取图标信息
$icon_url = isset($default_icon_url) ? $default_icon_url : '';
$icon_size = isset($icon_size) ? $icon_size : 'medium';

// 获取平台类型
$platform = get_post_meta($post->ID, '_yyk_app_platform', true);
if (!$platform) $platform = 'all';

// 获取游戏类型
$game_type = get_post_meta($post->ID, '_yyk_app_game_type', true);
if (!$game_type) $game_type = '应用';

// 获取文件大小和版本号
$size = isset($size) ? $size : '未知';
$version = isset($version) ? $version : '1.0.0';
?>

<div class="yyk-template-card">
    <div class="yyk-card-inner">
        <!-- 左侧图标区域 -->
        <div class="yyk-card-icon">
            <a href="<?php echo get_permalink($post->ID); ?>">
                <img src="<?php echo esc_url($icon_url); ?>" 
                     alt="<?php echo esc_attr($post->post_title); ?>">
            </a>
        </div>
        
        <!-- 右侧内容区域 -->
        <div class="yyk-card-content">
            <!-- 第一行：应用标题 + 热门标签 -->
            <div class="yyk-card-header">
                <h3 class="yyk-card-title">
                    <a href="<?php echo get_permalink($post->ID); ?>"><?php echo esc_html($post->post_title); ?></a>
                </h3>
                
                <!-- 热门标签 -->
                <div class="yyk-card-badges">
                    <?php if (!empty($is_hot)): ?>
                        <span class="yyk-badge yyk-hot"><?php _e('热', 'yyk-app-download'); ?></span>
                    <?php endif; ?>
                    
                    <?php if (!empty($is_recommend)): ?>
                        <span class="yyk-badge yyk-recommend"><?php _e('荐', 'yyk-app-download'); ?></span>
                    <?php endif; ?>
                    
                    <?php if (!empty($is_new)): ?>
                        <span class="yyk-badge yyk-new"><?php _e('新', 'yyk-app-download'); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- 第二行：平台类型 -->
            <div class="yyk-platform-icons">
                <span class="yyk-platform-icon <?php echo esc_attr($platform); ?>">
                    <?php 
                    switch($platform) {
                        case 'android': echo __('安卓', 'yyk-app-download'); break;
                        case 'ios': echo __('苹果', 'yyk-app-download'); break;
                        case 'pc': echo __('PC', 'yyk-app-download'); break;
                        default: echo __('全平台', 'yyk-app-download');
                    }
                    ?>
                </span>
            </div>
            
            <!-- 第三行：版本号、文件大小、游戏类型 -->
            <div class="yyk-card-meta">
                <div class="yyk-meta-item">
                    <span class="yyk-meta-label"><?php _e('版本', 'yyk-app-download'); ?>:</span>
                    <span class="yyk-meta-value"><?php echo esc_html($version); ?></span>
                </div>
                
                <div class="yyk-meta-item">
                    <span class="yyk-meta-label"><?php _e('大小', 'yyk-app-download'); ?>:</span>
                    <span class="yyk-meta-value"><?php echo esc_html($size); ?></span>
                </div>
                
                <div class="yyk-meta-item">
                    <span class="yyk-meta-label"><?php _e('类型', 'yyk-app-download'); ?>:</span>
                    <span class="yyk-meta-value"><?php echo esc_html($game_type); ?></span>
                </div>
            </div>
            
            <!-- 第四行：按钮 -->
            <div class="yyk-card-actions">
                <a href="<?php echo get_permalink($post->ID); ?>" class="yyk-card-detail">
                    <?php _e('查看详情', 'yyk-app-download'); ?>
                </a>
                
                <?php if (!empty($download_url)): ?>
                    <a href="<?php echo esc_url($download_url); ?>" 
                       class="yyk-card-download" 
                       target="_blank" 
                       rel="nofollow"
                       data-app-id="<?php echo $post->ID; ?>">
                        <?php _e('立即下载', 'yyk-app-download'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>