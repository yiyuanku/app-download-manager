<?php
/*============================================================
 =  🚀 项目名称：壹元库应用下载插件
 =  📦 模块名称：紧凑卡片模板模块
 =  📄 文件：compact.php
 =  👤 作者：壹元库 <815116566@qq.com>
 =  🌐 官网：https://yiyuanku.cn
 =  🔢 版本：1.0.0
 =  📅 日期：2026-04-15
 =  📝 说明：紧凑卡片样式模板，用于轮播展示，显示单个应用的紧凑样式
 =  © 版权：2026 壹元库. All Rights Reserved.
 ============================================================*/

if (!defined('ABSPATH')) {
    exit;
}

$icon_url = isset($default_icon_url) ? $default_icon_url : '';
$icon_size = isset($icon_size) ? $icon_size : 'medium';

$platform = get_post_meta($post->ID, '_yyk_app_platform', true);
if (!$platform) $platform = 'all';

$game_type = get_post_meta($post->ID, '_yyk_app_game_type', true);
if (!$game_type) $game_type = '联运';
?>

<div class="yyk-compact-card">
    <a href="<?php echo get_permalink($post->ID); ?>" class="yyk-compact-link">
        <div class="yyk-compact-icon">
            <img src="<?php echo esc_url($icon_url); ?>" 
                 alt="<?php echo esc_attr($post->post_title); ?>">
        </div>
        <div class="yyk-compact-title">
            <?php echo esc_html($post->post_title); ?>
        </div>
        <div class="yyk-compact-tag">
            <?php echo esc_html($game_type); ?>
        </div>
    </a>
</div>
