<?php
/**
 * 应用下载文章类型类
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('YYK_App_Post_Type')) {

    class YYK_App_Post_Type {
        
        private static $instance = null;
        
        public static function get_instance() {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        
        private function __construct() {}
        
        public function init() {
            add_action('init', [$this, 'register_post_type'], 5); // 优先级5，尽早注册
            add_action('init', [$this, 'register_taxonomy'], 5);
            add_filter('manage_yyk_app_download_posts_columns', [$this, 'add_custom_columns']);
            add_action('manage_yyk_app_download_posts_custom_column', [$this, 'display_custom_columns'], 10, 2);
            
            // 强制重新注册的钩子
            add_action('yyk_force_register_post_type', [$this, 'register_post_type']);
            add_action('yyk_force_register_taxonomy', [$this, 'register_taxonomy']);
        }
        
        public function register_post_type() {
            $labels = [
                'name' => _x('应用下载', '应用下载通用名称', 'yyk-app-download'),
                'singular_name' => _x('应用', '应用下载单数名称', 'yyk-app-download'),
                'menu_name' => __('应用下载', 'yyk-app-download'),
                'add_new' => __('添加应用', 'yyk-app-download'),
                'add_new_item' => __('添加新应用', 'yyk-app-download'),
                'edit_item' => __('编辑应用', 'yyk-app-download'),
                'view_item' => __('查看应用', 'yyk-app-download'),
                'search_items' => __('搜索应用', 'yyk-app-download'),
                'not_found' => __('没有找到应用', 'yyk-app-download'),
                'all_items' => __('所有应用', 'yyk-app-download'),
                'featured_image' => __('应用图标', 'yyk-app-download'),
                'set_featured_image' => __('设置应用图标', 'yyk-app-download'),
                'remove_featured_image' => __('移除应用图标', 'yyk-app-download'),
            ];
            
            $args = [
                'labels' => $labels,
                'public' => true,
                'publicly_queryable' => true,
                'show_ui' => true,
                'show_in_menu' => true, // 确保显示在菜单中
                'show_in_nav_menus' => true,
                'show_in_admin_bar' => true,
                'query_var' => true,
                'rewrite' => ['slug' => 'app-download', 'with_front' => false],
                'capability_type' => 'post',
                'capabilities' => [
                    'edit_post' => 'edit_post',
                    'read_post' => 'read_post',
                    'delete_post' => 'delete_post',
                    'edit_posts' => 'edit_posts',
                    'edit_others_posts' => 'edit_others_posts',
                    'publish_posts' => 'publish_posts',
                    'read_private_posts' => 'read_private_posts',
                ],
                'map_meta_cap' => true, // 重要：启用元能力映射
                'has_archive' => true,
                'hierarchical' => false,
                'menu_position' => 26, // 在"页面"(20)和"评论"(25)之后
                'menu_icon' => 'dashicons-download',
                'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'author'],
                'show_in_rest' => true, // 支持古腾堡编辑器
                'rest_base' => 'yyk-app-download',
            ];
            
            register_post_type('yyk_app_download', $args);
            
            // 记录日志
            if (current_user_can('manage_options')) {
                error_log('YYK应用下载管理器: 文章类型已注册 - yyk_app_download');
            }
        }
        
        public function register_taxonomy() {
            $labels = [
                'name' => _x('应用分类', '应用分类通用名称', 'yyk-app-download'),
                'singular_name' => _x('应用分类', '应用分类单数名称', 'yyk-app-download'),
                'search_items' => __('搜索分类', 'yyk-app-download'),
                'all_items' => __('所有分类', 'yyk-app-download'),
                'parent_item' => __('父分类', 'yyk-app-download'),
                'parent_item_colon' => __('父分类:', 'yyk-app-download'),
                'edit_item' => __('编辑分类', 'yyk-app-download'),
                'update_item' => __('更新分类', 'yyk-app-download'),
                'add_new_item' => __('添加新分类', 'yyk-app-download'),
                'new_item_name' => __('新分类名称', 'yyk-app-download'),
                'menu_name' => __('应用分类', 'yyk-app-download'),
            ];
            
            $args = [
                'labels' => $labels,
                'hierarchical' => true,
                'public' => true,
                'show_ui' => true,
                'show_admin_column' => true,
                'show_in_nav_menus' => true,
                'show_tagcloud' => false,
                'show_in_rest' => true,
                'rewrite' => ['slug' => 'app-category', 'with_front' => false],
                'capabilities' => [
                    'manage_terms' => 'manage_categories',
                    'edit_terms' => 'manage_categories',
                    'delete_terms' => 'manage_categories',
                    'assign_terms' => 'edit_posts',
                ],
            ];
            
            register_taxonomy('yyk_app_category', ['yyk_app_download'], $args);
            
            // 记录日志
            if (current_user_can('manage_options')) {
                error_log('YYK应用下载管理器: 分类法已注册 - yyk_app_category');
            }
        }
        
        public function add_custom_columns($columns) {
            $new_columns = [];
            
            foreach ($columns as $key => $value) {
                $new_columns[$key] = $value;
                
                if ('title' === $key) {
                    $new_columns['yyk_app_icon'] = __('图标', 'yyk-app-download');
                    $new_columns['yyk_app_version'] = __('版本', 'yyk-app-download');
                    $new_columns['yyk_app_status'] = __('状态', 'yyk-app-download');
                }
            }
            
            return $new_columns;
        }
        
        public function display_custom_columns($column, $post_id) {
            switch ($column) {
                case 'yyk_app_icon':
                    if (has_post_thumbnail($post_id)) {
                        echo get_the_post_thumbnail($post_id, [50, 50], ['style' => 'border-radius: 8px;']);
                    } else {
                        echo '<span class="dashicons dashicons-smartphone" style="font-size: 40px; color: #ddd;"></span>';
                    }
                    break;
                    
                case 'yyk_app_version':
                    $version = get_post_meta($post_id, '_yyk_app_version', true);
                    echo $version ? esc_html($version) : '<em>' . __('未设置', 'yyk-app-download') . '</em>';
                    break;
                    
                case 'yyk_app_status':
                    $is_hot = get_post_meta($post_id, '_yyk_app_is_hot', true);
                    $is_recommend = get_post_meta($post_id, '_yyk_app_is_recommend', true);
                    
                    if ($is_hot) {
                        echo '<span class="yyk-badge yyk-hot">' . __('热门', 'yyk-app-download') . '</span> ';
                    }
                    if ($is_recommend) {
                        echo '<span class="yyk-badge yyk-recommend">' . __('推荐', 'yyk-app-download') . '</span>';
                    }
                    if (!$is_hot && !$is_recommend) {
                        echo '<span class="yyk-badge yyk-normal">' . __('普通', 'yyk-app-download') . '</span>';
                    }
                    break;
            }
        }
    }
}