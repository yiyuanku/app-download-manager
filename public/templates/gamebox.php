<?php
/*============================================================
 =  🚀 项目名称：壹元库应用下载插件
 =  📦 模块名称：游戏盒子模板模块
 =  📄 文件：gamebox.php
 =  👤 作者：壹元库 <815116566@qq.com>
 =  🌐 官网：https://yiyuanku.cn
 =  🔢 版本：1.0.0
 =  📅 日期：2026-04-15
 =  📝 说明：游戏盒子样式模板，用于显示单个应用的游戏盒子样式展示
 =  © 版权：2026 壹元库. All Rights Reserved.
 ============================================================*/

if (!defined('ABSPATH')) {
    exit;
}

// 获取图标信息
$icon_url = isset($default_icon_url) ? $default_icon_url : '';
$icon_size = isset($icon_size) ? $icon_size : 'medium';
?>

<div class="yyk-gamebox">
    <!-- 图标区域 -->
    <div class="yyk-gamebox-icon">
        <a href="<?php echo get_permalink($post->ID); ?>">
            <img src="<?php echo esc_url($icon_url); ?>" 
                 alt="<?php echo esc_attr($post->post_title); ?>">
        </a>
        
        <!-- 热门标签 -->
        <?php if (!empty($is_hot)): ?>
            <span class="yyk-hot-tag"><?php _e('热', 'yyk-app-download'); ?></span>
        <?php endif; ?>
    </div>
    
    <!-- 内容区域 -->
    <div class="yyk-gamebox-content">
        <!-- 应用标题 -->
        <h4 class="yyk-gamebox-title">
            <a href="<?php echo get_permalink($post->ID); ?>"><?php echo esc_html($post->post_title); ?></a>
        </h4>
        
        <!-- 下载按钮 -->
        <?php if (!empty($download_url)): ?>
            <a href="<?php echo esc_url($download_url); ?>" 
               class="yyk-gamebox-download" 
               target="_blank" 
               rel="nofollow"
               data-app-id="<?php echo $post->ID; ?>">
                <?php _e('立即下载', 'yyk-app-download'); ?>
            </a>
        <?php endif; ?>
    </div>
</div>