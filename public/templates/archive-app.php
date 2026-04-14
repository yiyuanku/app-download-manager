<?php
/**
 * 应用归档页模板 - 完整版（含热门应用下载按钮）
 * 功能：热门应用带下载按钮 + 实时筛选 + 两列布局 + 数字分页
 */
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

// 获取所有应用分类
$all_categories = get_terms([
    'taxonomy' => 'yyk_app_category',
    'hide_empty' => true,
    'orderby' => 'name',
    'order' => 'ASC',
]);

// 显示热门应用
$hot_apps = get_posts([
    'post_type' => 'yyk_app_download',
    'posts_per_page' => 6, // 显示6个热门应用
    'meta_key' => '_yyk_app_is_hot',
    'meta_value' => '1',
    'orderby' => 'date',
    'order' => 'DESC',
]);

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
    
    <div class="yyk-archive-content">
        <!-- 左侧边栏 - 包含分类和热门应用 -->
        <div class="yyk-archive-sidebar">
            <h3><?php _e('应用分类', 'yyk-app-download'); ?></h3>
            <?php if ($all_categories && !is_wp_error($all_categories)): ?>
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
                    <?php foreach ($all_categories as $category): ?>
                        <?php if ($category->count > 0): ?>
                            <li>
                                <a href="<?php echo get_term_link($category); ?>" 
                                   <?php if ($current_category && $current_category->term_id == $category->term_id): ?>class="yyk-active"<?php endif; ?>>
                                    <span class="yyk-category-name"><?php echo esc_html($category->name); ?></span>
                                    <span class="yyk-count">(<?php echo $category->count; ?>)</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            
            <!-- 热门应用区域 - 带下载按钮 -->
            <?php if ($hot_apps): ?>
                <div class="yyk-hot-apps">
                    <div class="yyk-hot-apps-header">
                        <h3><?php _e('热门应用', 'yyk-app-download'); ?></h3>
                        <a href="<?php echo get_post_type_archive_link('yyk_app_download'); ?>?sort=hot" class="yyk-view-more">
                            <?php _e('更多', 'yyk-app-download'); ?> →
                        </a>
                    </div>
                    <div class="yyk-hot-apps-list">
                        <?php foreach ($hot_apps as $app): ?>
                            <?php
                            $app_icon_id = get_post_meta($app->ID, '_yyk_app_icon_id', true);
                            $app_icon_url = $app_icon_id ? wp_get_attachment_url($app_icon_id) : '';
                            
                            // 获取应用下载链接
                            $download_links = get_post_meta($app->ID, '_yyk_app_download_links', true);
                            $primary_download_link = '';
                            
                            if (is_array($download_links) && !empty($download_links)) {
                                // 取第一个下载链接作为主要下载链接
                                $first_link = reset($download_links);
                                $primary_download_link = isset($first_link['url']) ? $first_link['url'] : '';
                            }
                            
                            // 如果没有图标，使用默认图标
                            if (!$app_icon_url) {
                                $default_icon_path = plugin_dir_path(__FILE__) . '../../../assets/images/default-icon.png';
                                $default_icon_url = plugins_url('../../assets/images/default-icon.png', __FILE__);
                                
                                if (file_exists($default_icon_path)) {
                                    $app_icon_url = $default_icon_url;
                                } else {
                                    $app_icon_url = 'https://via.placeholder.com/40/0073aa/ffffff?text=APP';
                                }
                            }
                            
                            // 获取应用简介
                            $app_excerpt = get_the_excerpt($app->ID);
                            if (empty($app_excerpt)) {
                                $app_excerpt = wp_trim_words(get_post_field('post_content', $app->ID), 12);
                            }
                            ?>
                            <div class="yyk-hot-app-item">
                                <div class="yyk-hot-app-left">
                                    <div class="yyk-hot-app-icon">
                                        <a href="<?php echo get_permalink($app->ID); ?>">
                                            <img src="<?php echo esc_url($app_icon_url); ?>" 
                                                 alt="<?php echo esc_attr($app->post_title); ?>"
                                                 width="40" height="40">
                                        </a>
                                    </div>
                                    
                                    <div class="yyk-hot-app-info">
                                        <h4 class="yyk-hot-app-title">
                                            <a href="<?php echo get_permalink($app->ID); ?>"><?php echo esc_html($app->post_title); ?></a>
                                        </h4>
                                        <?php if ($app_excerpt): ?>
                                            <p class="yyk-hot-app-excerpt"><?php echo esc_html($app_excerpt); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- 下载按钮 - 右侧 -->
                                <?php if ($primary_download_link): ?>
                                    <div class="yyk-hot-app-download">
                                        <a href="<?php echo esc_url($primary_download_link); ?>" 
                                           class="yyk-download-btn" 
                                           target="_blank"
                                           title="<?php printf(__('下载 %s', 'yyk-app-download'), esc_attr($app->post_title)); ?>">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="white">
                                                <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/>
                                            </svg>
                                            <span><?php _e('下载', 'yyk-app-download'); ?></span>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="yyk-hot-app-detail">
                                        <a href="<?php echo get_permalink($app->ID); ?>" 
                                           class="yyk-detail-btn">
                                            <?php _e('详情', 'yyk-app-download'); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
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
                    <?php
                    $total_pages = $app_query->max_num_pages;
                    $current_page = max(1, $paged);
                    
                    echo paginate_links(array(
                        'base'      => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                        'format'    => '?paged=%#%',
                        'current'   => $current_page,
                        'total'     => $total_pages,
                        'prev_text' => __('« 上一页', 'yyk-app-download'),
                        'next_text' => __('下一页 »', 'yyk-app-download'),
                        'mid_size'  => 2,
                        'type'      => 'list',
                    ));
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>