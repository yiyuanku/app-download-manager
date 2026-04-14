<?php
/**
 * 应用下载小工具类
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('YYK_App_Widget')) {

    class YYK_App_Widget extends WP_Widget {
        
        public function __construct() {
            parent::__construct(
                'yyk_app_widget',
                __('应用展示卡片', 'yyk-app-download'),
                [
                    'description' => __('在前台显示应用下载卡片，支持多种排列方式', 'yyk-app-download'),
                    'classname' => 'yyk-app-widget',
                ]
            );
            
            if (current_user_can('manage_options')) {
                error_log('YYK应用下载管理器: 小工具类已初始化');
            }
        }
        
        public static function register() {
            register_widget(__CLASS__);
        }
        
public function widget($args, $instance) {
    echo $args['before_widget'];
    
    $title = !empty($instance['title']) ? apply_filters('widget_title', $instance['title']) : '';
    $show_more_button = !empty($instance['show_more_button']) ? true : false;
    
    // 输出标题和更多按钮
    if (!empty($title) || $show_more_button) {
        echo '<div class="yyk-widget-header">';
        
        echo '<span class="yyk-shape shape-1"></span>';
        echo '<span class="yyk-shape shape-2"></span>';
        echo '<span class="yyk-shape shape-3"></span>';
        echo '<span class="yyk-shape shape-4"></span>';
        echo '<span class="yyk-shape shape-5"></span>';
        echo '<span class="yyk-shape shape-6"></span>';
        echo '<span class="yyk-shape shape-7"></span>';
        echo '<span class="yyk-shape shape-8"></span>';
        
        if (!empty($title)) {
            echo $args['before_title'] . esc_html($title) . $args['after_title'];
        }
        
        // 更多按钮
        if ($show_more_button) {
            $more_link = '';
            $category = !empty($instance['category']) ? intval($instance['category']) : 0;
            
            if ($category > 0) {
                // 如果有选择特定分类，链接到该分类页面
                $term_link = get_term_link($category, 'yyk_app_category');
                if (!is_wp_error($term_link)) {
                    $more_link = $term_link;
                }
            }
            
            // 如果分类链接无效，使用归档页链接
            if (!$more_link) {
                $more_link = get_post_type_archive_link('yyk_app_download');
            }
            
            if ($more_link) {
                echo '<a href="' . esc_url($more_link) . '" class="yyk-widget-more">' . __('更多', 'yyk-app-download') . '</a>';
            }
        }
        
        echo '</div>';
    }
    
    // 获取小工具设置
    $style = !empty($instance['style']) ? $instance['style'] : 'card';
    $category = !empty($instance['category']) ? intval($instance['category']) : 0;
    $num_apps = !empty($instance['num_apps']) ? intval($instance['num_apps']) : 6;
    $layout = !empty($instance['layout']) ? $instance['layout'] : 'grid';
    $columns = !empty($instance['columns']) ? intval($instance['columns']) : 3;
    $show_hot = !empty($instance['show_hot']) ? true : false;
    $show_recommend = !empty($instance['show_recommend']) ? true : false;
    $show_new = !empty($instance['show_new']) ? true : false;
    $icon_size = !empty($instance['icon_size']) ? $instance['icon_size'] : 'medium';
    
    // 根据列数设置不同的网格样式
    $columns_class = 'yyk-columns-' . min(20, max(1, $columns));
    
    // 构建查询参数
    $query_args = [
        'post_type' => 'yyk_app_download',
        'posts_per_page' => $num_apps,
        'post_status' => 'publish',
        'ignore_sticky_posts' => true,
    ];
    
    // 分类筛选
    if ($category > 0) {
        $query_args['tax_query'] = [
            [
                'taxonomy' => 'yyk_app_category',
                'field' => 'term_id',
                'terms' => $category,
            ]
        ];
    }
    
    // 状态筛选
    $meta_query = [];
    if ($show_hot) {
        $meta_query[] = [
            'key' => '_yyk_app_is_hot',
            'value' => '1',
            'compare' => '='
        ];
    }
    if ($show_recommend) {
        $meta_query[] = [
            'key' => '_yyk_app_is_recommend',
            'value' => '1',
            'compare' => '='
        ];
    }
    if ($show_new) {
        $meta_query[] = [
            'key' => '_yyk_app_is_new',
            'value' => '1',
            'compare' => '='
        ];
    }
    
    if (!empty($meta_query)) {
        if (count($meta_query) > 1) {
            $meta_query['relation'] = 'OR';
        }
        $query_args['meta_query'] = $meta_query;
    }
    
    // 排序方式
    $orderby = 'date';
    if ($show_hot) {
        $orderby = 'meta_value_num';
        $query_args['meta_key'] = '_yyk_app_download_count';
    }
    $query_args['orderby'] = $orderby;
    $query_args['order'] = 'DESC';
    
    // 执行查询
    $apps_query = new WP_Query($query_args);
    
    // 调试信息：输出查询到的文章数量
    if (current_user_can('manage_options')) {
        echo '<div style="display:none;">找到 ' . $apps_query->found_posts . ' 个应用</div>';
    }
    
    if ($apps_query->have_posts()) {
        // 根据布局类型输出不同的容器
        echo '<div class="yyk-widget-container yyk-layout-' . esc_attr($layout) . ' ' . esc_attr($columns_class) . ' yyk-style-' . esc_attr($style) . ' yyk-icon-' . esc_attr($icon_size) . '">';
        
        $total_posts = $apps_query->found_posts;
        $items_per_page = 3 * $columns; // 3排 × 列数
        $total_pages = ceil($total_posts / $items_per_page);
        $is_pagination = $layout !== 'list' && $layout !== 'carousel' && $total_pages > 1;
        
        // 同时输出列表模式和其他模式，手机端CSS会自动选择显示哪个
        echo '<div class="yyk-widget-list">';
        while ($apps_query->have_posts()) {
            $apps_query->the_post();
            $this->render_list_item(get_the_ID(), $style, $icon_size);
        }
        echo '</div>';
        
        // 重置查询指针，用于第二次循环
        $apps_query->rewind_posts();
        
        if ($layout !== 'list') {
            // 轮播布局添加左右按钮
            if ($layout === 'carousel') {
                echo '<button class="yyk-carousel-btn yyk-carousel-btn-prev" type="button" aria-label="' . esc_attr__('上一页', 'yyk-app-download') . '">‹</button>';
            }
            
            echo '<div class="yyk-widget-grid" data-total-pages="' . esc_attr($total_pages) . '" data-items-per-page="' . esc_attr($items_per_page) . '">';
            $index = 0;
            while ($apps_query->have_posts()) {
                $apps_query->the_post();
                
                if ($layout === 'carousel') {
                    // 轮播模式不添加分页类
                    if (class_exists('YYK_App_Frontend')) {
                        echo YYK_App_Frontend::render_app_card(get_the_ID(), $style, $icon_size);
                    } else {
                        $this->render_card_item(get_the_ID(), $style, $icon_size);
                    }
                } else {
                    // 网格模式添加分页类
                    $current_page = floor($index / $items_per_page) + 1;
                    $page_class = 'yyk-page-' . $current_page;
                    $hidden_class = $current_page > 1 ? 'yyk-hidden' : '';
                    
                    if (class_exists('YYK_App_Frontend')) {
                        $card_html = YYK_App_Frontend::render_app_card(get_the_ID(), $style, $icon_size);
                        // 给卡片添加分页类
                        $card_html = preg_replace('/class="([^"]*)"/', 'class="$1 ' . $page_class . ' ' . $hidden_class . '"', $card_html, 1);
                        echo $card_html;
                    } else {
                        $this->render_card_item(get_the_ID(), $style, $icon_size, $page_class . ' ' . $hidden_class);
                    }
                }
                $index++;
            }
            echo '</div>';
            
            // 轮播布局添加右按钮
            if ($layout === 'carousel') {
                echo '<button class="yyk-carousel-btn yyk-carousel-btn-next" type="button" aria-label="' . esc_attr__('下一页', 'yyk-app-download') . '">›</button>';
            }
            
            // 分页布局添加分页按钮
            if ($is_pagination) {
                echo '<div class="yyk-pagination">';
                echo '<button class="yyk-pagination-prev" type="button" disabled>' . __('上一页', 'yyk-app-download') . '</button>';
                echo '<span class="yyk-pagination-info">1 / ' . $total_pages . '</span>';
                echo '<button class="yyk-pagination-next" type="button">' . __('下一页', 'yyk-app-download') . '</button>';
                echo '</div>';
            }
        }
        
        echo '</div>'; // 结束容器
    } else {
        echo '<p class="yyk-no-apps">' . __('暂无应用', 'yyk-app-download') . '</p>';
    }
    
    wp_reset_postdata();
    echo $args['after_widget'];
}
        
        private function render_list_item($post_id, $style, $icon_size) {
            $post = get_post($post_id);
            
            // 获取应用图标
            $app_icon_id = get_post_meta($post_id, '_yyk_app_icon_id', true);
            $icon_url = $this->get_app_icon_url($post_id, $app_icon_id, $icon_size);
            
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
                             alt="<?php echo esc_attr($post->post_title); ?>"
                             class="yyk-icon-<?php echo esc_attr($icon_size); ?>">
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
                        <span class="yyk-badge yyk-hot"><?php _e('热', 'yyk-app-download'); ?></span>
                    <?php endif; ?>
                    <?php if ($is_recommend): ?>
                        <span class="yyk-badge yyk-recommend"><?php _e('荐', 'yyk-app-download'); ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if ($download_url): ?>
                    <div class="yyk-list-actions">
                        <a href="<?php echo esc_url($download_url); ?>" 
                           class="yyk-list-download" 
                           target="_blank" 
                           rel="nofollow"
                           data-app-id="<?php echo $post_id; ?>">
                            <?php _e('下载', 'yyk-app-download'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <?php
        }
        
        private function render_card_item($post_id, $style, $icon_size, $extra_class = '') {
            $post = get_post($post_id);
            
            // 获取应用图标
            $app_icon_id = get_post_meta($post_id, '_yyk_app_icon_id', true);
            $icon_url = $this->get_app_icon_url($post_id, $app_icon_id, $icon_size);
            
            $version = get_post_meta($post_id, '_yyk_app_version', true);
            $size = get_post_meta($post_id, '_yyk_app_size', true);
            $developer = get_post_meta($post_id, '_yyk_app_developer', true);
            $download_url = get_post_meta($post_id, '_yyk_app_download_url', true);
            $is_hot = get_post_meta($post_id, '_yyk_app_is_hot', true);
            $is_recommend = get_post_meta($post_id, '_yyk_app_is_recommend', true);
            $is_new = get_post_meta($post_id, '_yyk_app_is_new', true);
            
            if ($style === 'gamebox') {
                ?>
                <div class="yyk-gamebox yyk-icon-<?php echo esc_attr($icon_size); ?> <?php echo esc_attr($extra_class); ?>">
                    <div class="yyk-gamebox-icon">
                        <a href="<?php echo get_permalink($post_id); ?>">
                            <img src="<?php echo esc_url($icon_url); ?>" 
                                 alt="<?php echo esc_attr($post->post_title); ?>"
                                 class="yyk-icon-<?php echo esc_attr($icon_size); ?>">
                        </a>
                        <?php if ($is_hot): ?>
                            <span class="yyk-hot-tag">HOT</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="yyk-gamebox-content">
                        <h4 class="yyk-gamebox-title">
                            <a href="<?php echo get_permalink($post_id); ?>"><?php echo esc_html($post->post_title); ?></a>
                        </h4>
                        
                        <div class="yyk-gamebox-meta">
                            <?php if ($version): ?>
                                <span class="yyk-gamebox-version">v<?php echo esc_html($version); ?></span>
                            <?php endif; ?>
                            
                            <?php if ($size): ?>
                                <span class="yyk-gamebox-size"><?php echo esc_html($size); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="yyk-gamebox-actions">
                            <a href="<?php echo get_permalink($post_id); ?>" class="yyk-gamebox-detail">
                                <?php _e('详情', 'yyk-app-download'); ?>
                            </a>
                            
                            <?php if ($download_url): ?>
                                <a href="<?php echo esc_url($download_url); ?>" 
                                   class="yyk-gamebox-download" 
                                   target="_blank" 
                                   rel="nofollow"
                                   data-app-id="<?php echo $post_id; ?>">
                                    <?php _e('下载', 'yyk-app-download'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php
            } else {
                ?>
                <div class="yyk-template-card yyk-icon-<?php echo esc_attr($icon_size); ?> <?php echo esc_attr($extra_class); ?>">
                    <div class="yyk-card-header">
                        <div class="yyk-card-icon">
                            <a href="<?php echo get_permalink($post_id); ?>">
                                <img src="<?php echo esc_url($icon_url); ?>" 
                                     alt="<?php echo esc_attr($post->post_title); ?>"
                                     class="yyk-icon-<?php echo esc_attr($icon_size); ?>">
                            </a>
                        </div>
                        
                        <div class="yyk-card-info">
                            <h4 class="yyk-card-title">
                                <a href="<?php echo get_permalink($post_id); ?>"><?php echo esc_html($post->post_title); ?></a>
                            </h4>
                            <?php if ($developer): ?>
                                <p class="yyk-card-developer"><?php echo esc_html($developer); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($is_hot || $is_recommend || $is_new): ?>
                            <div class="yyk-card-badges">
                                <?php if ($is_hot): ?>
                                    <span class="yyk-badge yyk-hot"><?php _e('热门', 'yyk-app-download'); ?></span>
                                <?php endif; ?>
                                <?php if ($is_recommend): ?>
                                    <span class="yyk-badge yyk-recommend"><?php _e('推荐', 'yyk-app-download'); ?></span>
                                <?php endif; ?>
                                <?php if ($is_new): ?>
                                    <span class="yyk-badge yyk-new"><?php _e('新', 'yyk-app-download'); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="yyk-card-footer">
                        <a href="<?php echo get_permalink($post_id); ?>" class="yyk-card-detail">
                            <?php _e('查看详情', 'yyk-app-download'); ?>
                        </a>
                        
                        <?php if ($download_url): ?>
                            <a href="<?php echo esc_url($download_url); ?>" 
                               class="yyk-card-download" 
                               target="_blank" 
                               rel="nofollow"
                               data-app-id="<?php echo $post_id; ?>">
                                <?php _e('下载', 'yyk-app-download'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
            }
        }
        
        private function get_app_icon_url($post_id, $app_icon_id, $size = 'medium') {
            $app_icon_url = get_post_meta($post_id, '_yyk_app_icon_url', true);
            $icon_url = '';
            
            if ($app_icon_url) {
                // 使用远程图标URL
                $icon_url = $app_icon_url;
            } elseif ($app_icon_id) {
                // 根据图标尺寸获取不同大小的图片
                switch ($size) {
                    case 'small':
                        $icon_url = wp_get_attachment_image_url($app_icon_id, 'thumbnail');
                        break;
                    case 'large':
                        $icon_url = wp_get_attachment_image_url($app_icon_id, 'large');
                        break;
                    case 'medium':
                    default:
                        $icon_url = wp_get_attachment_image_url($app_icon_id, 'medium');
                        break;
                }
            }
            
            if (!$icon_url) {
                // 如果没有图标，使用默认图标
                $default_icon_path = plugin_dir_path(__FILE__) . '../assets/images/default-icon.png';
                $default_icon_url = plugins_url('../assets/images/default-icon.png', __FILE__);
                
                if (file_exists($default_icon_path)) {
                    $icon_url = $default_icon_url;
                } else {
                    $icon_url = 'https://via.placeholder.com/150/0073aa/ffffff?text=APP';
                }
            }
            
            return $icon_url;
        }
        
public function form($instance) {
    $defaults = [
        'title' => __('应用下载', 'yyk-app-download'),
        'style' => 'card',
        'layout' => 'grid',
        'columns' => 3,
        'category' => 0,
        'num_apps' => 6,
        'icon_size' => 'medium',
        'show_hot' => false,
        'show_recommend' => false,
        'show_new' => false,
        'show_more_button' => true, // 新增：显示更多按钮
    ];
    
    $instance = wp_parse_args((array) $instance, $defaults);
    ?>
    
    <div class="yyk-widget-settings">
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('标题:', 'yyk-app-download'); ?></label>
            <input type="text" class="widefat" 
                   id="<?php echo $this->get_field_id('title'); ?>" 
                   name="<?php echo $this->get_field_name('title'); ?>" 
                   value="<?php echo esc_attr($instance['title']); ?>">
        </p>
        
        <p>
            <input type="checkbox" 
                   id="<?php echo $this->get_field_id('show_more_button'); ?>" 
                   name="<?php echo $this->get_field_name('show_more_button'); ?>" 
                   value="1" <?php checked($instance['show_more_button'], 1); ?>>
            <label for="<?php echo $this->get_field_id('show_more_button'); ?>">
                <?php _e('显示"更多"按钮', 'yyk-app-download'); ?>
            </label>
        </p>
        
        <div class="yyk-settings-row">
            <div class="yyk-settings-col">
                <p>
                    <label for="<?php echo $this->get_field_id('style'); ?>"><?php _e('卡片样式:', 'yyk-app-download'); ?></label>
                    <select class="widefat" 
                            id="<?php echo $this->get_field_id('style'); ?>" 
                            name="<?php echo $this->get_field_name('style'); ?>">
                        <option value="card" <?php selected($instance['style'], 'card'); ?>>
                            <?php _e('独立卡片样式', 'yyk-app-download'); ?>
                        </option>
                        <option value="gamebox" <?php selected($instance['style'], 'gamebox'); ?>>
                            <?php _e('游戏盒子样式', 'yyk-app-download'); ?>
                        </option>
                    </select>
                </p>
            </div>
            
            <div class="yyk-settings-col">
                <p>
                    <label for="<?php echo $this->get_field_id('layout'); ?>"><?php _e('排列方式:', 'yyk-app-download'); ?></label>
                    <select class="widefat" 
                            id="<?php echo $this->get_field_id('layout'); ?>" 
                            name="<?php echo $this->get_field_name('layout'); ?>">
                        <option value="grid" <?php selected($instance['layout'], 'grid'); ?>>
                            <?php _e('网格排列', 'yyk-app-download'); ?>
                        </option>
                        <option value="list" <?php selected($instance['layout'], 'list'); ?>>
                            <?php _e('列表排列', 'yyk-app-download'); ?>
                        </option>
                        <option value="carousel" <?php selected($instance['layout'], 'carousel'); ?>>
                            <?php _e('轮播排列', 'yyk-app-download'); ?>
                        </option>
                    </select>
                </p>
            </div>
        </div>
                
                <div class="yyk-settings-row">
                    <div class="yyk-settings-col">
                        <p>
                            <label for="<?php echo $this->get_field_id('columns'); ?>"><?php _e('每行列数:', 'yyk-app-download'); ?></label>
                            <input type="number" class="widefat" 
                                   id="<?php echo $this->get_field_id('columns'); ?>" 
                                   name="<?php echo $this->get_field_name('columns'); ?>" 
                                   value="<?php echo esc_attr($instance['columns']); ?>" 
                                   min="1" max="20" step="1">
                        </p>
                    </div>
                    
                    <div class="yyk-settings-col">
                        <p>
                            <label for="<?php echo $this->get_field_id('icon_size'); ?>"><?php _e('图标尺寸:', 'yyk-app-download'); ?></label>
                            <select class="widefat" 
                                    id="<?php echo $this->get_field_id('icon_size'); ?>" 
                                    name="<?php echo $this->get_field_name('icon_size'); ?>">
                                <option value="small" <?php selected($instance['icon_size'], 'small'); ?>><?php _e('小图标', 'yyk-app-download'); ?></option>
                                <option value="medium" <?php selected($instance['icon_size'], 'medium'); ?>><?php _e('中图标', 'yyk-app-download'); ?></option>
                                <option value="large" <?php selected($instance['icon_size'], 'large'); ?>><?php _e('大图标', 'yyk-app-download'); ?></option>
                            </select>
                        </p>
                    </div>
                </div>
                
                <p>
                    <label for="<?php echo $this->get_field_id('category'); ?>"><?php _e('应用分类:', 'yyk-app-download'); ?></label>
                    <?php
                    wp_dropdown_categories([
                        'show_option_all' => __('全部分类', 'yyk-app-download'),
                        'taxonomy' => 'yyk_app_category',
                        'id' => $this->get_field_id('category'),
                        'name' => $this->get_field_name('category'),
                        'selected' => $instance['category'],
                        'class' => 'widefat',
                        'hide_empty' => false,
                    ]);
                    ?>
                </p>
                
                <div class="yyk-settings-row">
                    <div class="yyk-settings-col">
                        <p>
                            <label for="<?php echo $this->get_field_id('num_apps'); ?>"><?php _e('显示数量:', 'yyk-app-download'); ?></label>
                            <input type="number" class="widefat" 
                                   id="<?php echo $this->get_field_id('num_apps'); ?>" 
                                   name="<?php echo $this->get_field_name('num_apps'); ?>" 
                                   value="<?php echo esc_attr($instance['num_apps']); ?>" 
                                   min="1" max="50" step="1">
                        </p>
                    </div>
                </div>
                
                <div class="yyk-settings-row">
                    <div class="yyk-settings-col">
                        <p>
                            <input type="checkbox" 
                                   id="<?php echo $this->get_field_id('show_hot'); ?>" 
                                   name="<?php echo $this->get_field_name('show_hot'); ?>" 
                                   value="1" <?php checked($instance['show_hot'], 1); ?>>
                            <label for="<?php echo $this->get_field_id('show_hot'); ?>">
                                <?php _e('只显示热门', 'yyk-app-download'); ?>
                            </label>
                        </p>
                    </div>
                    
                    <div class="yyk-settings-col">
                        <p>
                            <input type="checkbox" 
                                   id="<?php echo $this->get_field_id('show_recommend'); ?>" 
                                   name="<?php echo $this->get_field_name('show_recommend'); ?>" 
                                   value="1" <?php checked($instance['show_recommend'], 1); ?>>
                            <label for="<?php echo $this->get_field_id('show_recommend'); ?>">
                                <?php _e('只显示推荐', 'yyk-app-download'); ?>
                            </label>
                        </p>
                    </div>
                    
                    <div class="yyk-settings-col">
                        <p>
                            <input type="checkbox" 
                                   id="<?php echo $this->get_field_id('show_new'); ?>" 
                                   name="<?php echo $this->get_field_name('show_new'); ?>" 
                                   value="1" <?php checked($instance['show_new'], 1); ?>>
                            <label for="<?php echo $this->get_field_id('show_new'); ?>">
                                <?php _e('只显示最新', 'yyk-app-download'); ?>
                            </label>
                        </p>
                    </div>
                </div>
            </div>
            
            <style>
            .yyk-widget-settings {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                margin-top: 10px;
            }
            .yyk-settings-row {
                display: flex;
                gap: 15px;
                margin-bottom: 10px;
            }
            .yyk-settings-col {
                flex: 1;
            }
            .yyk-settings-col p {
                margin-top: 0;
            }
            .yyk-widget-settings input[type="number"] {
                width: 100%;
            }
            </style>
            <?php
        }
        
public function update($new_instance, $old_instance) {
    $instance = $old_instance;
    
    $instance['title'] = sanitize_text_field($new_instance['title']);
    $instance['style'] = sanitize_text_field($new_instance['style']);
    $instance['layout'] = sanitize_text_field($new_instance['layout']);
    $instance['columns'] = intval($new_instance['columns']);
    $instance['category'] = intval($new_instance['category']);
    $instance['num_apps'] = intval($new_instance['num_apps']);
    $instance['icon_size'] = sanitize_text_field($new_instance['icon_size']);
    $instance['show_hot'] = !empty($new_instance['show_hot']) ? 1 : 0;
    $instance['show_recommend'] = !empty($new_instance['show_recommend']) ? 1 : 0;
    $instance['show_new'] = !empty($new_instance['show_new']) ? 1 : 0;
    $instance['show_more_button'] = !empty($new_instance['show_more_button']) ? 1 : 0; // 新增
    
    return $instance;
}
    }
}