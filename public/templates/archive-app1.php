
<?php
/**
 * 应用归档页模板
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
?>

<div class="yyk-archive-container">
    <div class="yyk-archive-header">
        <?php if ($current_category): ?>
            <h1 class="yyk-archive-title"><?php echo esc_html($current_category->name); ?></h1>
            <?php if ($current_category->description): ?>
                <div class="yyk-archive-description">
                    <?php echo wpautop($current_category->description); ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <h1 class="yyk-archive-title"><?php _e('所有应用', 'yyk-app-download'); ?></h1>
        <?php endif; ?>
    </div>
    
    <div class="yyk-archive-content">
        <div class="yyk-archive-sidebar">
            <h3><?php _e('应用分类', 'yyk-app-download'); ?></h3>
            <?php
            $categories = get_terms([
                'taxonomy' => 'yyk_app_category',
                'hide_empty' => true,
                'orderby' => 'name',
                'order' => 'ASC',
            ]);
            
            if ($categories && !is_wp_error($categories)):
            ?>
                <ul class="yyk-category-menu">
                    <li>
                        <a href="<?php echo get_post_type_archive_link('yyk_app_download'); ?>" 
                           <?php if (!is_tax('yyk_app_category')): ?>class="yyk-active"<?php endif; ?>>
                            <?php _e('所有应用', 'yyk-app-download'); ?>
                            <?php 
                            $all_count = wp_count_posts('yyk_app_download')->publish;
                            if ($all_count > 0):
                            ?>
                                <span class="yyk-count">(<?php echo $all_count; ?>)</span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php foreach ($categories as $category): ?>
                        <?php if ($category->count > 0): ?>
                            <li>
                                <a href="<?php echo get_term_link($category); ?>" 
                                   <?php if ($current_category && $current_category->term_id == $category->term_id): ?>class="yyk-active"<?php endif; ?>>
                                    <?php echo esc_html($category->name); ?>
                                    <span class="yyk-count">(<?php echo $category->count; ?>)</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            
            <?php
            // 显示热门应用
            $hot_apps = get_posts([
                'post_type' => 'yyk_app_download',
                'posts_per_page' => 5,
                'meta_key' => '_yyk_app_is_hot',
                'meta_value' => '1',
                'orderby' => 'date',
                'order' => 'DESC',
            ]);
            
            if ($hot_apps):
            ?>
                <div class="yyk-hot-apps">
                    <h3><?php _e('热门应用', 'yyk-app-download'); ?></h3>
                    <div class="yyk-hot-apps-list">
                        <?php foreach ($hot_apps as $app): ?>
                            <?php
                            $app_icon_id = get_post_meta($app->ID, '_yyk_app_icon_id', true);
                            $app_icon_url = $app_icon_id ? wp_get_attachment_url($app_icon_id) : '';
                            
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
                            ?>
                            <div class="yyk-hot-app-item">
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
                                    <?php
                                    $developer = get_post_meta($app->ID, '_yyk_app_developer', true);
                                    if ($developer):
                                    ?>
                                        <p class="yyk-hot-app-developer"><?php echo esc_html($developer); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="yyk-archive-main yyk-app-archive-main">
            <?php if (have_posts()): ?>
                <div class="yyk-archive-app-grid">
                    <?php while (have_posts()): the_post(); ?>
                        <?php echo YYK_App_Frontend::render_app_card(get_the_ID(), 'card'); ?>
                    <?php endwhile; ?>
                </div>
                
                <div class="yyk-pagination">
                    <?php
                    the_posts_pagination([
                        'mid_size' => 2,
                        'prev_text' => __('上一页', 'yyk-app-download'),
                        'next_text' => __('下一页', 'yyk-app-download'),
                    ]);
                    ?>
                </div>
            <?php else: ?>
                <div class="yyk-no-apps">
                    <h3><?php _e('暂无应用', 'yyk-app-download'); ?></h3>
                    <p><?php _e('当前分类下还没有应用，请稍后再来查看。', 'yyk-app-download'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* 归档页专用样式，防止被覆盖 */
.yyk-archive-container {
    max-width: 1200px;
    margin: 30px auto;
    padding: 20px;
}

.yyk-archive-content {
    display: grid;
    grid-template-columns: 250px 1fr;
    gap: 40px;
}

@media (max-width: 992px) {
    .yyk-archive-content {
        grid-template-columns: 1fr;
        gap: 30px;
    }
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

.yyk-archive-app-grid {
    display: grid !important;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)) !important;
    gap: 25px !important;
    margin-bottom: 40px !important;
}

@media (max-width: 768px) {
    .yyk-archive-container {
        padding: 15px;
        margin: 15px auto;
    }
    
    .yyk-archive-main {
        padding: 20px;
    }
    
    .yyk-archive-app-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)) !important;
        gap: 15px !important;
    }
}

@media (max-width: 576px) {
    .yyk-archive-app-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php get_footer(); ?>
