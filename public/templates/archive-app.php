<?php
/*============================================================
 =  🚀 项目名称：壹元库应用下载插件
 =  📦 模块名称：归档页模板模块
 =  📄 文件：archive-app.php
 =  👤 作者：壹元库 <815116566@qq.com>
 =  🌐 官网：https://yiyuanku.cn
 =  🔢 版本：1.0.0
 =  📅 日期：2026-04-15
 =  📝 说明：应用归档页模板，包含热门应用轮播、应用列表、筛选器、分页等功能
 =  © 版权：2026 壹元库. All Rights Reserved.
 ============================================================*/

if (!defined('ABSPATH')) {
    exit;
}

get_header();

// 获取当前分类信息
$current_category = null;
if (is_tax('yyk_app_category')) {
    $current_category = get_queried_object();
}

// 为分类标题设置图片背景
$category_header_bg = '';

// 方法1：使用分类自定义图片
if ($current_category) {
    $category_bg_image = get_term_meta($current_category->term_id, 'category_header_image', true);
    if ($category_bg_image) {
        $category_header_bg = wp_get_attachment_url($category_bg_image);
    }
}

// 方法2：根据分类名称使用不同背景图片
if (!$category_header_bg && $current_category) {
    $category_name = strtolower($current_category->name);
    $category_slug = $current_category->slug;
    
    $category_images = [
        'action' => 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80',
        '动作' => 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80',
        'rpg' => 'https://images.unsplash.com/photo-1511512578047-dfb367046420?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80',
        '角色扮演' => 'https://images.unsplash.com/photo-1511512578047-dfb367046420?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80',
        'strategy' => 'https://images.unsplash.com/photo-1538481199705-c710c4e965fc?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80',
        '策略' => 'https://images.unsplash.com/photo-1538481199705-c710c4e965fc?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80',
        'adventure' => 'https://images.unsplash.com/photo-1534423861386-85a16f5d13fd?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80',
        '冒险' => 'https://images.unsplash.com/photo-1534423861386-85a16f5d13fd?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80',
        'sports' => 'https://images.unsplash.com/photo-1565992441121-4367c2967103?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80',
        '体育' => 'https://images.unsplash.com/photo-1565992441121-4367c2967103?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80',
    ];
    
    foreach ($category_images as $key => $image_url) {
        if (strpos($category_name, $key) !== false || strpos($category_slug, $key) !== false) {
            $category_header_bg = $image_url;
            break;
        }
    }
}

// 方法3：使用随机游戏背景图片
if (!$category_header_bg) {
    $game_backgrounds = [
        'https://images.unsplash.com/photo-1550745165-9bc0b252726f?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80',
        'https://images.unsplash.com/photo-1511512578047-dfb367046420?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80',
        'https://images.unsplash.com/photo-1538481199705-c710c4e965fc?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80',
        'https://images.unsplash.com/photo-1534423861386-85a16f5d13fd?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80',
    ];
    
    $random_index = array_rand($game_backgrounds);
    $category_header_bg = $game_backgrounds[$random_index];
}

// 获取所有应用分类，按层级结构组织
$all_categories = get_terms([
    'taxonomy' => 'yyk_app_category',
    'hide_empty' => true,
    'orderby' => 'name',
    'order' => 'ASC',
    'hierarchical' => true,
]);

// 构建分类层级树
$category_tree = [];
$categories_by_id = [];

foreach ($all_categories as $category) {
    $categories_by_id[$category->term_id] = $category;
    if ($category->parent == 0) {
        $category_tree[$category->term_id] = [
            'category' => $category,
            'children' => []
        ];
    }
}

// 添加子分类
foreach ($all_categories as $category) {
    if ($category->parent != 0 && isset($categories_by_id[$category->parent])) {
        $parent_id = $category->parent;
        if (isset($category_tree[$parent_id])) {
            $category_tree[$parent_id]['children'][] = $category;
        } else {
            // 父分类不在树中（可能为空的父分类），直接添加到根级
            $category_tree[$category->term_id] = [
                'category' => $category,
                'children' => []
            ];
        }
    }
}

// 显示热门应用
$hot_apps = get_posts([
    'post_type' => 'yyk_app_download',
    'posts_per_page' => 6, // 显示6个热门应用
    'meta_key' => '_yyk_app_is_hot',
    'meta_value' => '1',
    'orderby' => 'date',
    'order' => 'DESC',
]);

// 如果没有热门应用，显示最近更新的
if (empty($hot_apps)) {
    $hot_apps = get_posts([
        'post_type' => 'yyk_app_download',
        'posts_per_page' => 6,
        'orderby' => 'date',
        'order' => 'DESC',
    ]);
}

// 设置每页显示20个应用
$apps_per_page = 20;

// 获取当前页码
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

// 自定义查询：每页20个应用
$args = array(
    'post_type'      => 'yyk_app_download',
    'posts_per_page' => $apps_per_page,
    'paged'          => $paged,
    'orderby'        => 'date',
    'order'          => 'DESC',
);

// 如果是分类页面，添加分类筛选
if ($current_category) {
    $args['tax_query'] = array(
        array(
            'taxonomy' => 'yyk_app_category',
            'field'    => 'term_id',
            'terms'    => $current_category->term_id,
        )
    );
}

// 如果是搜索页面，添加搜索关键词
if (isset($_GET['s']) && !empty($_GET['s'])) {
    $args['s'] = sanitize_text_field($_GET['s']);
}

$app_query = new WP_Query($args);
$total_apps = $app_query->found_posts;
?>

<div class="yyk-archive-container">
    <!-- 搜索框区域 - 实时筛选 -->
    <div class="yyk-archive-search-section">
        <div class="yyk-search-container">
            <div class="yyk-search-box">
                <input type="search" 
                       id="yyk-realtime-search" 
                       class="yyk-search-input" 
                       placeholder="<?php esc_attr_e('输入应用名称进行筛选...', 'yyk-app-download'); ?>"
                       title="<?php esc_attr_e('实时筛选', 'yyk-app-download'); ?>">
                <button type="button" class="yyk-search-submit">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 19C15.4183 19 19 15.4183 19 11C19 6.58172 15.4183 3 11 3C6.58172 3 3 6.58172 3 11C3 15.4183 6.58172 19 11 19Z"></path>
                        <path d="M21 21L16.65 16.65"></path>
                    </svg>
                </button>
            </div>
            <div class="yyk-search-info">
                <?php 
                printf(__('共 <span class="yyk-count">%s</span> 个应用', 'yyk-app-download'), $total_apps);
                
                if (isset($_GET['s']) && !empty($_GET['s'])) {
                    echo '<span class="yyk-search-keyword">' . 
                         sprintf(__('搜索词: %s', 'yyk-app-download'), '<strong>' . esc_html($_GET['s']) . '</strong>') . 
                         '</span>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- 分类标题区域 -->
    <div class="yyk-category-header-section <?php echo $current_category ? 'yyk-has-category' : ''; ?>"
         style="background-image: url('<?php echo esc_url($category_header_bg); ?>');">
        <div class="yyk-header-overlay"></div>
        
        <div class="yyk-header-content">
            <?php if ($current_category): ?>
                <div class="yyk-category-header-content">
                    <div class="yyk-category-badge">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="white">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                        <span><?php _e('游戏分类', 'yyk-app-download'); ?></span>
                    </div>
                    
                    <h1 class="yyk-category-title"><?php echo esc_html($current_category->name); ?></h1>
                    
                    <?php if ($current_category->description): ?>
                        <div class="yyk-category-description">
                            <?php echo wpautop($current_category->description); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="yyk-category-stats">
                        <div class="yyk-stat-item">
                            <span class="yyk-stat-number"><?php echo $current_category->count; ?></span>
                            <span class="yyk-stat-label"><?php _e('个应用', 'yyk-app-download'); ?></span>
                        </div>
                        <div class="yyk-stat-item">
                            <span class="yyk-stat-icon">🔥</span>
                            <span class="yyk-stat-label"><?php _e('热门精选', 'yyk-app-download'); ?></span>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="yyk-all-apps-header-content">
                    <div class="yyk-all-apps-badge">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="white">
                            <path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/>
                        </svg>
                        <span><?php _e('所有应用', 'yyk-app-download'); ?></span>
                    </div>
                    
                    <h1 class="yyk-category-title"><?php _e('游戏应用中心', 'yyk-app-download'); ?></h1>
                    
                    <?php if ($total_apps > 0): ?>
                        <div class="yyk-category-description">
                            <?php printf(__('探索 %s 款精彩游戏应用，发现您的新最爱', 'yyk-app-download'), '<strong>' . $total_apps . '</strong>'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- 热门展示紧凑轮播 -->
    <div class="yyk-partner-carousel-wrapper" style="background: white; border-radius: 16px; padding: 20px; margin-bottom: 30px; position: relative; box-shadow: 0 4px 12px rgba(0,0,0,0.08); border: 1px solid #e8e8e8;">
        <div style="text-align: center; margin-bottom: 15px; position: relative; display: flex; align-items: center; justify-content: center;">
            <div class="yyk-hot-title-line yyk-hot-title-line-left"></div>
            <h3 style="color: #333; font-size: 16px; font-weight: 600; margin: 0 15px; white-space: nowrap;">热门展示</h3>
            <div class="yyk-hot-title-line yyk-hot-title-line-right"></div>
        </div>
        <?php 
        if (class_exists('YYK_App_Frontend')) {
            echo YYK_App_Frontend::render_app_list([
                'style' => 'compact',
                'layout' => 'carousel',
                'count' => 12,
                'orderby' => 'date',
                'order' => 'DESC'
            ]);
        }
        ?>
        <div class="yyk-partner-left-fade"></div>
        <div class="yyk-partner-right-fade"></div>
        <a href="<?php echo get_post_type_archive_link('yyk_app_download'); ?>" class="yyk-partner-view-more">
            查看全部
        </a>
    </div>
    
    <div class="yyk-archive-content">
        <!-- 左侧边栏 - 包含分类和热门应用 -->
        <div class="yyk-archive-sidebar">
            <h3>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: -3px; margin-right: 8px;">
                    <path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/>
                </svg>
                <?php _e('应用分类', 'yyk-app-download'); ?>
            </h3>
            <?php if ($category_tree && !is_wp_error($category_tree)): ?>
                <ul class="yyk-category-menu">
                    <li>
                        <a href="<?php echo get_post_type_archive_link('yyk_app_download'); ?>" 
                           <?php if (!$current_category): ?>class="yyk-active"<?php endif; ?>>
                            <span class="yyk-category-name"><?php _e('所有应用', 'yyk-app-download'); ?></span>
                            <?php 
                            $all_count = wp_count_posts('yyk_app_download')->publish;
                            if ($all_count > 0):
                            ?>
                                <span class="yyk-count">(<?php echo $all_count; ?>)</span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php foreach ($category_tree as $item): ?>
                        <?php 
                        $category = $item['category'];
                        $children = $item['children'];
                        $has_children = !empty($children);
                        $is_current_parent = false;
                        
                        if ($current_category) {
                            // 检查当前分类是否是这个父分类的子分类
                            $ancestors = get_ancestors($current_category->term_id, 'yyk_app_category');
                            $is_current_parent = in_array($category->term_id, $ancestors);
                        }
                        
                        $is_expanded = $is_current_parent || ($current_category && $current_category->term_id == $category->term_id);
                        ?>
                        <li class="yyk-category-parent <?php echo $has_children ? 'yyk-has-children' : ''; ?>">
                            <?php if ($has_children): ?>
                                <div class="yyk-category-clickable" data-toggle="yyk-category-<?php echo $category->term_id; ?>">
                                    <span class="yyk-category-name"><?php echo esc_html($category->name); ?></span>
                                    <span class="yyk-count">(<?php echo $category->count; ?>)</span>
                                </div>
                                
                                <ul class="yyk-category-children <?php echo $is_expanded ? 'yyk-show' : ''; ?>" id="yyk-category-<?php echo $category->term_id; ?>">
                                    <?php foreach ($children as $child): ?>
                                        <?php if ($child->count > 0): ?>
                                            <li class="yyk-category-child">
                                                <a href="<?php echo get_term_link($child); ?>" 
                                                   <?php if ($current_category && $current_category->term_id == $child->term_id): ?>class="yyk-active"<?php endif; ?>>
                                                    <span class="yyk-category-name"><?php echo esc_html($child->name); ?></span>
                                                    <span class="yyk-count">(<?php echo $child->count; ?>)</span>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <a href="<?php echo get_term_link($category); ?>" 
                                   <?php if ($current_category && $current_category->term_id == $category->term_id): ?>class="yyk-active"<?php endif; ?>>
                                    <span class="yyk-category-name"><?php echo esc_html($category->name); ?></span>
                                    <span class="yyk-count">(<?php echo $category->count; ?>)</span>
                                </a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            
            <!-- 热门应用区域 - 带下载按钮 -->
            <?php if ($hot_apps): ?>
                <div class="yyk-hot-apps">
                    <div class="yyk-hot-apps-header">
                        <h3>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: -3px; margin-right: 8px;">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                            </svg>
                            <?php _e('热门应用', 'yyk-app-download'); ?>
                        </h3>
                        <a href="<?php echo get_post_type_archive_link('yyk_app_download'); ?>?sort=hot" class="yyk-widget-more">
                            <?php _e('更多', 'yyk-app-download'); ?>
                        </a>
                    </div>
                    <ul class="yyk-hot-apps-list">
                        <?php foreach ($hot_apps as $app): ?>
                            <?php
                            // 使用正确的方法获取图标URL
                            $app_icon_url = YYK_App_Frontend::get_app_icon_url($app->ID, 'small');
                            
                            // 获取应用下载链接
                            $primary_download_link = get_post_meta($app->ID, '_yyk_app_download_url', true);
                            
                            // 获取版本和大小
                            $version = get_post_meta($app->ID, '_yyk_app_version', true);
                            $size = get_post_meta($app->ID, '_yyk_app_size', true);
                            ?>
                            <li class="yyk-hot-app-item yyk-list-item">
                                <div class="yyk-list-icon">
                                    <a href="<?php echo get_permalink($app->ID); ?>">
                                        <img src="<?php echo esc_url($app_icon_url); ?>" 
                                             alt="<?php echo esc_attr($app->post_title); ?>">
                                    </a>
                                </div>
                                
                                <div class="yyk-list-content">
                                    <h4 class="yyk-list-title">
                                        <a href="<?php echo get_permalink($app->ID); ?>"><?php echo esc_html($app->post_title); ?></a>
                                    </h4>
                                    
                                    <div class="yyk-list-meta">
                                        <?php if ($version): ?>
                                            <span class="yyk-list-version">v<?php echo esc_html($version); ?></span>
                                        <?php endif; ?>
                                        
                                        <?php if ($size): ?>
                                            <span class="yyk-list-size"><?php echo esc_html($size); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if ($primary_download_link): ?>
                                    <div class="yyk-list-actions">
                                        <a href="<?php echo esc_url($primary_download_link); ?>" 
                                           class="yyk-list-download" 
                                           target="_blank" 
                                           rel="nofollow">
                                            <?php _e('下载', 'yyk-app-download'); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- 主内容区域 -->
        <div class="yyk-archive-main yyk-app-archive-main">
            <!-- 应用列表容器 - 手机端显示 -->
            <div id="yyk-archive-app-list" class="yyk-archive-app-list">
                <?php if ($app_query->have_posts()): ?>
                    <?php while ($app_query->have_posts()): $app_query->the_post(); ?>
                        <?php
                        $post_id = get_the_ID();
                        $post = get_post($post_id);
                        
                        // 获取应用图标
                        $app_icon_id = get_post_meta($post_id, '_yyk_app_icon_id', true);
                        $icon_url = '';
                        if ($app_icon_id) {
                            $icon_url = wp_get_attachment_image_url($app_icon_id, 'full');
                        }
                        if (!$icon_url) {
                            $icon_url = get_post_meta($post_id, '_yyk_app_icon_url', true);
                        }
                        if (!$icon_url) {
                            $icon_url = plugins_url('../../assets/images/default-icon.png', __FILE__);
                        }
                        
                        $version = get_post_meta($post_id, '_yyk_app_version', true);
                        $size = get_post_meta($post_id, '_yyk_app_size', true);
                        $is_hot = get_post_meta($post_id, '_yyk_app_is_hot', true);
                        $is_recommend = get_post_meta($post_id, '_yyk_app_is_recommend', true);
                        $download_url = get_post_meta($post_id, '_yyk_app_download_url', true);
                        ?>
                        <div class="yyk-list-item">
                            <div class="yyk-list-icon">
                                <a href="<?php echo get_permalink($post_id); ?>">
                                    <img src="<?php echo esc_url($icon_url); ?>" 
                                         alt="<?php echo esc_attr($post->post_title); ?>">
                                </a>
                            </div>
                            
                            <div class="yyk-list-content">
                                <h4 class="yyk-list-title">
                                    <a href="<?php echo get_permalink($post_id); ?>"><?php echo esc_html($post->post_title); ?></a>
                                </h4>
                                
                                <div class="yyk-list-meta">
                                    <?php if ($version): ?>
                                        <span class="yyk-list-version">v<?php echo esc_html($version); ?></span>
                                    <?php endif; ?>
                                    
                                    <?php if ($size): ?>
                                        <span class="yyk-list-size"><?php echo esc_html($size); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="yyk-list-badges">
                                <?php if ($is_hot): ?>
                                    <span class="yyk-badge yyk-hot">热</span>
                                <?php endif; ?>
                                <?php if ($is_recommend): ?>
                                    <span class="yyk-badge yyk-recommend">荐</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($download_url): ?>
                                <div class="yyk-list-actions">
                                    <a href="<?php echo esc_url($download_url); ?>" 
                                       class="yyk-list-download" 
                                       target="_blank" 
                                       rel="nofollow">
                                        下载
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                <?php else: ?>
                    <div class="yyk-no-apps">
                        <h3><?php _e('暂无应用', 'yyk-app-download'); ?></h3>
                        <p><?php _e('当前没有找到应用，请稍后再来查看。', 'yyk-app-download'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php 
            // 重置查询指针，用于第二次循环
            $app_query->rewind_posts(); 
            ?>
            
            <!-- 应用网格容器 - 两列布局 - 桌面端显示 -->
            <div id="yyk-archive-app-grid" class="yyk-archive-app-grid">
                <?php if ($app_query->have_posts()): ?>
                    <?php while ($app_query->have_posts()): $app_query->the_post(); ?>
                        <?php echo YYK_App_Frontend::render_app_card(get_the_ID(), 'card'); ?>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                <?php else: ?>
                    <div class="yyk-no-apps">
                        <h3><?php _e('暂无应用', 'yyk-app-download'); ?></h3>
                        <p><?php _e('当前没有找到应用，请稍后再来查看。', 'yyk-app-download'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- 数字分页 - 超过20个应用时显示 -->
            <?php if ($total_apps > $apps_per_page): ?>
                <div class="yyk-pagination">
                    <nav class="yyk-nav-links">
                        <?php
                        $total_pages = $app_query->max_num_pages;
                        $current_page = max(1, $paged);
                        
                        $pagination_args = array(
                            'base'      => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                            'format'    => '?paged=%#%',
                            'current'   => $current_page,
                            'total'     => $total_pages,
                            'prev_text' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg> 上一页',
                            'next_text' => '下一页 <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>',
                            'mid_size'  => 2,
                            'type'      => 'array',
                        );
                        
                        $pagination_links = paginate_links($pagination_args);
                        
                        if ($pagination_links):
                            echo '<ul class="yyk-nav-list">';
                            foreach ($pagination_links as $link):
                                echo '<li class="yyk-nav-item">';
                                if (strpos($link, 'current') !== false):
                                    echo str_replace('class="page-numbers current"', 'class="yyk-nav-page yyk-nav-current"', $link);
                                elseif (strpos($link, 'dots') !== false):
                                    echo str_replace('class="page-numbers dots"', 'class="yyk-nav-page yyk-nav-dots"', $link);
                                elseif (strpos($link, 'prev') !== false):
                                    echo str_replace('class="prev page-numbers"', 'class="yyk-nav-page yyk-nav-prev"', $link);
                                elseif (strpos($link, 'next') !== false):
                                    echo str_replace('class="next page-numbers"', 'class="yyk-nav-page yyk-nav-next"', $link);
                                else:
                                    echo str_replace('class="page-numbers"', 'class="yyk-nav-page yyk-nav-link"', $link);
                                endif;
                                echo '</li>';
                            endforeach;
                            echo '</ul>';
                        endif;
                        ?>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // 分类展开/收起功能 - 直接点击父分类
    $('.yyk-category-clickable').on('click', function(e) {
        e.preventDefault();
        
        var $toggle = $(this);
        var targetId = $toggle.data('toggle');
        var $children = $('#' + targetId);
        
        // 切换展开状态
        $toggle.toggleClass('yyk-expanded');
        $children.toggleClass('yyk-show');
    });
});
</script>