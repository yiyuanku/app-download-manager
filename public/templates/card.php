<?php
/*============================================================
 =  🚀 项目名称：壹元库应用下载插件
 =  📦 模块名称：卡片模板模块
 =  📄 文件：card.php
 =  👤 作者：壹元库 <815116566@qq.com>
 =  🌐 官网：https://yiyuanku.cn
 =  🔢 版本：1.0.0
 =  📅 日期：2026-04-15
 =  📝 说明：应用卡片样式模板，用于显示单个应用的卡片式展示
 =  © 版权：2026 壹元库. All Rights Reserved.
 ============================================================*/

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
            <!-- 第一排：标题 -->
            <h3 class="yyk-card-title">
                <a href="<?php echo get_permalink($post->ID); ?>"><?php echo esc_html($post->post_title); ?></a>
            </h3>
            
            <!-- 第二排：平台类型 -->
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
            
            <!-- 第三排：元信息 -->
            <div class="yyk-card-meta">
                <span class="yyk-meta-text"><?php _e('版本', 'yyk-app-download'); ?>: <?php echo esc_html($version); ?> | <?php _e('大小', 'yyk-app-download'); ?>: <?php echo esc_html($size); ?> | <?php _e('类型', 'yyk-app-download'); ?>: <?php echo esc_html($game_type); ?></span>
            </div>
            
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
            
            <!-- 按钮（永远在底部） -->
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