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
            <!-- 应用网格容器 - 两列布局 -->
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

<style>
/* ===== 归档页核心样式 ===== */
.yyk-archive-container {
    max-width: 1200px;
    margin: 30px auto;
    padding: 20px;
}

/* 搜索框样式 */
.yyk-archive-search-section {
    margin-bottom: 30px;
}

.yyk-search-container {
    position: relative;
    max-width: 800px;
    margin: 0 auto;
}

.yyk-search-box {
    display: flex;
    align-items: center;
    background: white;
    border-radius: 50px;
    padding: 5px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
    border: 2px solid #e8e8e8;
    transition: all 0.3s ease;
    margin-bottom: 15px;
}

.yyk-search-box:focus-within {
    border-color: #0073aa;
    box-shadow: 0 8px 25px rgba(0, 115, 170, 0.2);
}

#yyk-realtime-search {
    flex: 1;
    border: none;
    padding: 15px 25px;
    font-size: 16px;
    background: transparent;
    outline: none;
    color: #333;
    transition: all 0.3s ease;
}

#yyk-realtime-search::placeholder {
    color: #999;
}

#yyk-realtime-search:focus {
    border-color: #0073aa;
    box-shadow: 0 0 0 3px rgba(0, 115, 170, 0.1);
}

.yyk-search-submit {
    background: #0073aa;
    color: white;
    border: none;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-right: 5px;
}

.yyk-search-submit:hover {
    background: #005a87;
    transform: scale(1.05);
}

.yyk-search-submit svg {
    width: 20px;
    height: 20px;
    stroke-width: 2.5;
}

.yyk-search-info {
    text-align: center;
    color: #666;
    font-size: 14px;
    padding: 10px 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.yyk-search-info .yyk-count {
    color: #0073aa;
    font-weight: 600;
    font-size: 16px;
}

.yyk-search-keyword {
    margin-left: 15px;
    color: #e74c3c;
    font-weight: 500;
}

/* 分类标题样式 */
.yyk-category-header-section {
    position: relative;
    border-radius: 20px;
    margin-bottom: 30px;
    overflow: hidden;
    background-size: cover;
    background-position: center center;
    background-repeat: no-repeat;
    min-height: 300px;
    display: flex;
    align-items: center;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.yyk-header-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, 
                rgba(0, 0, 0, 0.7) 0%,
                rgba(0, 0, 0, 0.5) 50%,
                rgba(0, 0, 0, 0.3) 100%);
    z-index: 1;
}

.yyk-header-content {
    position: relative;
    z-index: 2;
    color: white;
    padding: 40px;
    width: 100%;
}

.yyk-category-badge,
.yyk-all-apps-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 20px;
}

.yyk-category-title {
    font-size: 48px;
    font-weight: 800;
    line-height: 1.2;
    margin: 0 0 20px 0;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
    letter-spacing: -0.5px;
}

.yyk-category-description {
    font-size: 18px;
    line-height: 1.6;
    margin-bottom: 30px;
    max-width: 600px;
    opacity: 0.9;
}

.yyk-category-description strong {
    color: #00d4ff;
    font-weight: 600;
}

.yyk-category-stats {
    display: flex;
    gap: 30px;
    flex-wrap: wrap;
}

.yyk-stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 15px 25px;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: 15px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
    min-width: 120px;
}

.yyk-stat-item:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-5px);
}

.yyk-stat-number {
    font-size: 36px;
    font-weight: 800;
    line-height: 1;
    margin-bottom: 5px;
    background: linear-gradient(45deg, #00d4ff, #0073aa);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.yyk-stat-icon {
    font-size: 32px;
    margin-bottom: 5px;
    display: block;
}

.yyk-stat-label {
    font-size: 14px;
    opacity: 0.9;
    text-align: center;
}

/* ===== 应用网格布局 - 两列 ===== */
#yyk-archive-app-grid {
    display: grid !important;
    grid-template-columns: repeat(1, 1fr) !important;
    gap: 10px !important;
    margin-bottom: 10px !important;
}

/* 无结果提示样式 */
.yyk-no-results-message {
    grid-column: 1 / -1;
    text-align: center;
    padding: 10px 10px;
    background: #f8f9fa;
    border-radius: 12px;
    border: 2px dashed #e8e8e8;
    margin: 20px 0;
}

.yyk-no-results-message p {
    margin: 10px 0;
    color: #666;
    font-size: 16px;
}

.yyk-no-results-message strong {
    color: #e74c3c;
}

.yyk-no-results-message .yyk-clear-search {
    color: #0073aa;
    text-decoration: none;
    font-weight: 500;
    cursor: pointer;
}

.yyk-no-results-message .yyk-clear-search:hover {
    text-decoration: underline;
}

/* ===== 热门应用样式 ===== */
.yyk-hot-apps {
    margin-top: 25px;
    padding-top: 25px;
    border-top: 2px solid #f0f0f0;
}

.yyk-hot-apps-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.yyk-hot-apps-header h3 {
    font-size: 16px;
    color: #333;
    margin: 0;
    font-weight: 600;
}

.yyk-view-more {
    font-size: 13px;
    color: #0073aa;
    text-decoration: none;
    font-weight: 500;
}

.yyk-view-more:hover {
    text-decoration: underline;
}

.yyk-hot-apps-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.yyk-hot-app-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px;
    border-radius: 8px;
    background: #f8f9fa;
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.yyk-hot-app-item:hover {
    background: white;
    border-color: #0073aa;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.yyk-hot-app-left {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    flex: 1;
    min-width: 0;
}

.yyk-hot-app-icon {
    flex: 0 0 80px;
    width: 80px;
    height: 80px;
    border-radius: 10px;
    overflow: hidden;
    background: white;
    border: 1px solid #e8e8e8;
}

.yyk-hot-app-icon img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.yyk-hot-app-info {
    flex: 1;
    min-width: 0;
}

.yyk-hot-app-title {
    margin: 0 0 4px 0;
    font-size: 14px;
    line-height: 1.3;
}

.yyk-hot-app-title a {
    color: #333;
    text-decoration: none;
    font-weight: 500;
}

.yyk-hot-app-title a:hover {
    color: #0073aa;
}

.yyk-hot-app-excerpt {
    margin: 0;
    font-size: 12px;
    color: #666;
    line-height: 1.4;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

/* 热门应用下载按钮样式 */
.yyk-hot-app-download,
.yyk-hot-app-detail {
    flex: 0 0 auto;
}

.yyk-download-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: #0073aa;
    color: white;
    border-radius: 6px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 500;
    transition: all 0.3s ease;
    white-space: nowrap;
    border: 1px solid #0073aa;
}

.yyk-download-btn:hover {
    background: #005a87;
    border-color: #005a87;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 115, 170, 0.3);
}

.yyk-download-btn svg {
    width: 14px;
    height: 14px;
}

.yyk-detail-btn {
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    background: #f0f0f0;
    color: #666;
    border-radius: 6px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 500;
    transition: all 0.3s ease;
    white-space: nowrap;
    border: 1px solid #ddd;
}

.yyk-detail-btn:hover {
    background: #e5e5e5;
    color: #333;
}

/* ===== 数字分页样式 ===== */
.yyk-pagination {
    margin-top: 40px;
    text-align: center;
}

.yyk-pagination ul {
    display: inline-flex;
    list-style: none;
    padding: 0;
    margin: 0;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: center;
}

.yyk-pagination li {
    margin: 0;
}

.yyk-pagination a,
.yyk-pagination span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 42px;
    height: 42px;
    padding: 0 8px;
    border-radius: 8px;
    background: #f5f7fa;
    color: #555;
    text-decoration: none;
    font-weight: 500;
    border: 2px solid transparent;
    transition: all 0.3s ease;
    font-size: 14px;
}

.yyk-pagination a:hover {
    background: #0073aa;
    color: white;
    border-color: #0073aa;
    transform: translateY(-2px);
}

.yyk-pagination span.current {
    background: #0073aa;
    color: white;
    border-color: #0073aa;
    box-shadow: 0 4px 12px rgba(0, 115, 170, 0.3);
}

.yyk-pagination .page-numbers.dots {
    background: transparent;
    border: none;
    color: #999;
    min-width: auto;
}

/* 归档内容布局 */
.yyk-archive-content {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 40px;
}

.yyk-archive-sidebar {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    border: 1px solid #e8e8e8;
    height: fit-content;
    position: sticky;
    top: 20px;
}

.yyk-archive-main {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    border: 1px solid #e8e8e8;
}

/* ===== 响应式设计 ===== */
@media (max-width: 1100px) {
    .yyk-archive-content {
        grid-template-columns: 280px 1fr;
        gap: 30px;
    }
    
    #yyk-archive-app-grid {
        gap: 20px !important;
    }
}

@media (max-width: 992px) {
    .yyk-archive-content {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    
    .yyk-archive-sidebar {
        position: static;
    }
}

@media (max-width: 768px) {
    .yyk-archive-container {
        padding: 15px;
        margin: 15px auto;
    }
    
    #yyk-archive-app-grid {
        grid-template-columns: 1fr !important;
        gap: 15px !important;
    }
    
    .yyk-category-header-section {
        min-height: 250px;
        margin-bottom: 25px;
        border-radius: 15px;
    }
    
    .yyk-header-content {
        padding: 30px 20px;
    }
    
    .yyk-category-title {
        font-size: 32px;
    }
    
    .yyk-category-description {
        font-size: 16px;
    }
    
    .yyk-search-box {
        flex-direction: row;
    }
    
    #yyk-realtime-search {
        padding: 12px 20px;
        font-size: 15px;
    }
    
    .yyk-search-submit {
        width: 46px;
        height: 46px;
    }
    
    .yyk-pagination ul {
        gap: 5px;
    }
    
    .yyk-pagination a,
    .yyk-pagination span {
        min-width: 38px;
        height: 38px;
        font-size: 13px;
    }
    
    /* 热门应用响应式 */
    .yyk-hot-app-item {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
    }
    
    .yyk-hot-app-left {
        flex-direction: column;
        text-align: center;
    }
    
    .yyk-hot-app-icon {
        margin: 0 auto;
    }
    
    .yyk-hot-app-download,
    .yyk-hot-app-detail {
        text-align: center;
    }
    
    .yyk-download-btn,
    .yyk-detail-btn {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 576px) {
    .yyk-category-header-section {
        min-height: 220px;
    }
    
    .yyk-category-title {
        font-size: 28px;
    }
    
    .yyk-category-stats {
        flex-direction: column;
        gap: 15px;
    }
    
    .yyk-stat-item {
        min-width: auto;
        padding: 12px 20px;
    }
}
</style>

<script>
// ===== 实时搜索筛选功能 =====
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('yyk-realtime-search');
    const appGrid = document.getElementById('yyk-archive-app-grid');
    
    if (!searchInput || !appGrid) return;
    
    // 获取所有应用卡片
    const appCards = appGrid.querySelectorAll('.yyk-template-card');
    
    // 实时输入监听
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        
        if (searchTerm) {
            filterApps(searchTerm);
        } else {
            showAllApps();
        }
    });
    
    // 搜索按钮点击事件
    const searchBtn = document.querySelector('.yyk-search-submit');
    if (searchBtn) {
        searchBtn.addEventListener('click', function() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            if (searchTerm) {
                filterApps(searchTerm);
            }
        });
    }
    
    // 筛选函数
    function filterApps(searchTerm) {
        let hasVisibleResults = false;
        let visibleCount = 0;
        
        appCards.forEach(card => {
            // 获取应用标题
            const titleElement = card.querySelector('.yyk-card-title');
            let appTitle = '';
            
            if (titleElement) {
                const titleLink = titleElement.querySelector('a');
                appTitle = titleLink ? titleLink.textContent : titleElement.textContent;
            }
            
            // 检查是否匹配
            const isVisible = appTitle.toLowerCase().includes(searchTerm);
            
            // 显示/隐藏卡片
            if (isVisible) {
                card.style.display = 'flex';
                hasVisibleResults = true;
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        // 更新搜索结果信息
        updateSearchInfo(searchTerm, visibleCount);
        
        // 显示无结果提示
        showNoResultsMessage(hasVisibleResults, searchTerm);
    }
    
    // 显示所有应用
    function showAllApps() {
        let totalCount = 0;
        
        appCards.forEach(card => {
            card.style.display = 'flex';
            totalCount++;
        });
        
        // 恢复原始计数
        updateSearchInfo('', totalCount);
        
        // 移除无结果提示
        removeNoResultsMessage();
    }
    
    // 更新搜索信息
    function updateSearchInfo(searchTerm, count) {
        const searchInfo = document.querySelector('.yyk-search-info');
        if (searchInfo) {
            if (searchTerm) {
                searchInfo.innerHTML = `找到 <span class="yyk-count">${count}</span> 个匹配 "<strong>${searchTerm}</strong>" 的应用`;
            } else {
                const totalApps = <?php echo $total_apps; ?>;
                searchInfo.innerHTML = `共 <span class="yyk-count">${totalApps}</span> 个应用`;
            }
        }
    }
    
    // 显示无结果提示
    function showNoResultsMessage(hasResults, searchTerm) {
        removeNoResultsMessage();
        
        if (!hasResults && searchTerm) {
            const noResultsMsg = document.createElement('div');
            noResultsMsg.className = 'yyk-no-results-message';
            noResultsMsg.innerHTML = `
                <p>没有找到包含 "<strong>${searchTerm}</strong>" 的应用</p>
                <p>请尝试其他关键词，或<a href="javascript:void(0)" class="yyk-clear-search">清空搜索</a>查看所有应用</p>
            `;
            
            appGrid.appendChild(noResultsMsg);
            
            // 清空搜索功能
            const clearBtn = noResultsMsg.querySelector('.yyk-clear-search');
            if (clearBtn) {
                clearBtn.addEventListener('click', function() {
                    searchInput.value = '';
                    showAllApps();
                    searchInput.focus();
                });
            }
        }
    }
    
    // 移除无结果提示
    function removeNoResultsMessage() {
        const existingMsg = appGrid.querySelector('.yyk-no-results-message');
        if (existingMsg) {
            existingMsg.remove();
        }
    }
    
    // 页面加载时显示总数
    updateSearchInfo('', appCards.length);
});
</script>

<?php get_footer(); ?>